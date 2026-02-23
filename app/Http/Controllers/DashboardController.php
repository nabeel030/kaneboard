<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Ticket;
use App\Services\ProjectHealthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Adjust these if your app considers "done" differently
        $doneStatuses = ['done', 'tested', 'completed'];

        $today = Carbon::today();
        $dueSoonDays = 3; // change if you want (e.g. 7)
        $dueSoonUntil = Carbon::today()->addDays($dueSoonDays);

        // ----------------------------
        // Accessible projects (owner OR member)
        // ----------------------------
        $accessibleProjectIds = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $projectsCount = $accessibleProjectIds->count();

        // ----------------------------
        // KPI: totals
        // ----------------------------
        $ticketsCount = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->count();

        $openTicketsCount = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNotIn('status', $doneStatuses)
            ->count();

        $overdueCount = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNotIn('status', $doneStatuses)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today)
            ->count();

        $dueSoonCount = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNotIn('status', $doneStatuses)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', $today)
            ->whereDate('deadline', '<=', $dueSoonUntil)
            ->count();

        // Completed last 7 days (prefers completed_at, falls back to updated_at)
        $window7 = Carbon::now()->subDays(7);
        $completedLast7Days = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereIn('status', $doneStatuses)
            ->where(function ($q) use ($window7) {
                $q->whereNotNull('completed_at')->where('completed_at', '>=', $window7)
                  ->orWhere(function ($q2) use ($window7) {
                      $q2->whereNull('completed_at')->where('updated_at', '>=', $window7);
                  });
            })
            ->count();

        $myOpenAssigned = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', $doneStatuses)
            ->count();

        $myAssignedOpen = $myOpenAssigned;

        $myAssignedDone = Ticket::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->where('assigned_to', $user->id)
            ->whereIn('status', $doneStatuses)
            ->count();

        // ----------------------------
        // Trend: 14-day completion trend
        // returns 14 points [{date, count}]
        // ----------------------------
        $days = 14;
        $window14 = Carbon::today()->subDays($days - 1); // inclusive start for 14 days

        // Query aggregated counts per date
        // Using COALESCE(completed_at, updated_at) so older records still show as "done" activity
        $rawTrend = Ticket::query()
            ->selectRaw("DATE(COALESCE(completed_at, updated_at)) as d, COUNT(*) as c")
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereIn('status', $doneStatuses)
            ->whereRaw("COALESCE(completed_at, updated_at) >= ?", [$window14->startOfDay()->toDateTimeString()])
            ->groupBy('d')
            ->pluck('c', 'd') // [date => count]
            ->toArray();

        $trend14 = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($days - 1 - $i)->format('Y-m-d');
            $trend14[] = [
                'date' => $date,
                'count' => (int)($rawTrend[$date] ?? 0),
            ];
        }

        // Status distribution (counts per status)
        $statuses = defined(Ticket::class . '::STATUSES')
            ? Ticket::STATUSES
            : ['backlog', 'todo', 'in_progress', 'done', 'tested', 'completed'];

        $statusCounts = Ticket::query()
            ->selectRaw('status, COUNT(*) as c')
            ->whereIn('project_id', $accessibleProjectIds)
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        // Keep ordering stable for the UI
        $statusDistribution = collect($statuses)->map(function ($s) use ($statusCounts) {
            return ['status' => $s, 'count' => (int)($statusCounts[$s] ?? 0)];
        })->values()->all();

        // Risk tickets list (overdue + due soon)
        $riskTickets = Ticket::query()
            ->with('project:id,name')
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereNotIn('status', $doneStatuses)
            ->whereNotNull('deadline')
            ->where(function ($q) use ($today, $dueSoonUntil) {
                $q->whereDate('deadline', '<', $today)
                ->orWhereBetween('deadline', [$today, $dueSoonUntil]);
            })
            ->orderByRaw("CASE WHEN deadline < ? THEN 0 ELSE 1 END ASC", [$today])
            ->orderBy('deadline', 'asc')
            ->select(['id', 'title', 'status', 'deadline', 'project_id'])
            ->paginate(5)
            ->withQueryString();

        // ----------------------------
        // Risky projects (uses ProjectHealthService)
        // Cache to avoid recalculating frequently
        // ----------------------------
        $cacheKey = "dashboard:risky-projects:user:{$user->id}";
        $riskyProjects = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($accessibleProjectIds) {
            $service = app(ProjectHealthService::class);

            $projects = Project::query()
                ->whereIn('id', $accessibleProjectIds)
                ->get(['id', 'name', 'start_date', 'end_date', 'baseline_start_date', 'baseline_end_date']);

            $rows = [];

            foreach ($projects as $p) {
                // Ensure tickets are present for calculation
                $p->loadMissing('tickets');

                $health = $service->calculate($p);
                $status = $health['status'] ?? 'UNKNOWN';

                $rows[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'status' => $status,
                    'expected_progress' => $health['expected_progress'] ?? null,
                    'actual_progress' => $health['actual_progress'] ?? null,
                    'end_date' => $health['end_date'] ?? optional($p->end_date)->format('Y-m-d'),
                    'forecast_end' => $health['forecast_end'] ?? null,
                    'confidence' => $health['confidence'] ?? null,
                    'risk_signals' => $health['risk_signals'] ?? [],
                ];
            }

            // Only show projects needing attention (feel free to tweak)
            $keep = ['LATE', 'AT_RISK', 'NO_SCHEDULE', 'INVALID_SCHEDULE'];
            $rows = array_values(array_filter($rows, fn ($r) => in_array($r['status'], $keep, true)));

            // Sort by severity then confidence
            $rank = [
                'LATE' => 1,
                'AT_RISK' => 2,
                'INVALID_SCHEDULE' => 3,
                'NO_SCHEDULE' => 4,
            ];

            usort($rows, function ($a, $b) use ($rank) {
                $ra = $rank[$a['status']] ?? 99;
                $rb = $rank[$b['status']] ?? 99;

                if ($ra !== $rb) return $ra <=> $rb;

                // Lower confidence first (riskier)
                $ca = $a['confidence'] ?? 999;
                $cb = $b['confidence'] ?? 999;
                return $ca <=> $cb;
            });

            return array_slice($rows, 0, 6);
        });

        // ----------------------------
        // Return Inertia payload (matches the new Dashboard.vue)
        // ----------------------------
        return inertia('Dashboard', [
            'kpis' => [
                'projects' => $projectsCount,
                'tickets' => $ticketsCount,
                'openTickets' => $openTicketsCount,
                'overdue' => $overdueCount,
                'dueSoon' => $dueSoonCount,
                'completed7' => $completedLast7Days,
                'myOpen' => $myOpenAssigned,
            ],
            'completionTrend14' => $trend14,
            'statusDistribution' => $statusDistribution,
            'myWorkload' => [
                'open' => $myAssignedOpen,
                'done' => $myAssignedDone,
            ],
            'riskTickets' => $riskTickets,
            'riskyProjects' => $riskyProjects,
        ]);
    }
}
