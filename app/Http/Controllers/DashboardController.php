<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projectIds = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $projectsCount = $projectIds->count();

        $ticketsCount = Ticket::query()
            ->whereIn('project_id', $projectIds)
            ->count();

        $myAssignedCount = Ticket::query()
            ->whereIn('project_id', $projectIds)
            ->where('assigned_to', $user->id)
            ->count();

        $statusCountsRaw = Ticket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('project_id', $projectIds)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = Ticket::STATUSES;
        $statusCounts = [];
        foreach ($statuses as $s) {
            $statusCounts[$s] = (int) ($statusCountsRaw[$s] ?? 0);
        }

        $recentTickets = Ticket::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project:id,name'])
            ->latest()
            ->limit(6)
            ->get(['id','project_id','title','status','assigned_to','created_by','created_at'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'created_at' => $t->created_at?->toDateTimeString(),
                'project' => $t->project?->only(['id','name']),
            ]);

        return inertia('Dashboard', [
            'stats' => [
                'projects' => $projectsCount,
                'tickets' => $ticketsCount,
                'assignedToMe' => $myAssignedCount,
            ],
            'statuses' => $statuses,
            'statusCounts' => $statusCounts,
            'recentTickets' => $recentTickets,
        ]);
    }
}
