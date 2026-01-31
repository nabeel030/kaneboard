<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectHealthService
{
    public function calculate(Project $project): array
    {
        $today = Carbon::today();

        // Basic guards
        if (!$project->start_date || !$project->end_date) {
            return [
                'status' => 'NO_SCHEDULE',
                'message' => 'Project start_date/end_date not set',
            ];
        }

        $start = Carbon::parse($project->start_date)->startOfDay();
        $end = Carbon::parse($project->end_date)->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            return [
                'status' => 'INVALID_SCHEDULE',
                'message' => 'end_date must be after start_date',
            ];
        }

        // Expected progress
        $totalDays = max(1, $start->diffInDays($end));
        $elapsedDays = $start->greaterThan($today) ? 0 : min($totalDays, $start->diffInDays($today));
        $expected = $this->clamp($elapsedDays / $totalDays, 0, 1);

        // Ticket stats
        $stats = $project->tickets()
            ->selectRaw('
                COUNT(*) as total,
                SUM(status IN ("done","tested","completed")) as done_count,
                SUM(status NOT IN ("done","tested","completed")) as open_count,
                SUM(deadline IS NOT NULL AND deadline < CURDATE() AND status NOT IN ("done","tested","completed")) as overdue_count,
                SUM(deadline IS NOT NULL AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status NOT IN ("done","tested","completed")) as due_soon_count,
                SUM(COALESCE(estimate,0)) as total_points,
                SUM(CASE WHEN status IN ("done","tested","completed") THEN COALESCE(estimate,0) ELSE 0 END) as done_points
            ')
            ->first();

        $total = (int) ($stats->total ?? 0);
        $doneCount = (int) ($stats->done_count ?? 0);

        // Actual progress (prefer estimates if meaningful)
        $totalPoints = (int) ($stats->total_points ?? 0);
        $donePoints = (int) ($stats->done_points ?? 0);

        if ($totalPoints > 0) {
            $actual = $donePoints / $totalPoints;
        } elseif ($total > 0) {
            $actual = $doneCount / $total;
        } else {
            $actual = 0.0;
        }

        // Forecast + confidence
        [$forecastEnd, $confidence, $throughputPerDay] = $this->forecast($project);

        // Scope creep
        $scopeCreepPct = $this->scopeCreepPct($project);

        // Determine status
        $status = $this->statusFor($project, $today, $expected, $actual, $forecastEnd);

        return [
            'status' => $status,
            'expected_progress' => round($expected, 4),
            'actual_progress' => round($actual, 4),

            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),

            'forecast_end' => $forecastEnd?->toDateString(),
            'confidence' => $confidence,
            'throughput_per_day' => $throughputPerDay,

            'tickets' => [
                'total' => $total,
                'done' => $doneCount,
                'open' => (int) ($stats->open_count ?? 0),
                'overdue' => (int) ($stats->overdue_count ?? 0),
                'due_soon' => (int) ($stats->due_soon_count ?? 0),
            ],

            'scope_creep_pct' => $scopeCreepPct,

            // Optional “risk reasons” for PM clarity
            'risk_signals' => $this->riskSignals(
                expected: $expected,
                actual: $actual,
                overdue: (int) ($stats->overdue_count ?? 0),
                dueSoon: (int) ($stats->due_soon_count ?? 0),
                scopeCreepPct: $scopeCreepPct,
                forecastEnd: $forecastEnd,
                end: $end
            ),
        ];
    }

    private function forecast(Project $project): array
    {
        $daysWindow = 14;

        // Group daily completions for last N days
        $rows = $project->tickets()
            ->done()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', Carbon::now()->subDays($daysWindow))
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->get();

        // Build a day-by-day array to compute stddev (including zeros)
        $daily = [];
        for ($i = $daysWindow - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i)->toDateString();
            $daily[$day] = 0;
        }

        foreach ($rows as $r) {
            $daily[$r->d] = (int) $r->c;
        }

        $sum = array_sum($daily);
        $throughputPerDay = $sum / $daysWindow;

        // Remaining work (count based; you can swap to estimate-based later)
        $remaining = $project->tickets()->open()->count();

        if ($throughputPerDay <= 0.0001 || $remaining <= 0) {
            return [null, null, round($throughputPerDay, 4)];
        }

        $forecastDays = $remaining / $throughputPerDay;
        $forecastEnd = Carbon::today()->addDays((int) ceil($forecastDays));

        // Confidence based on stddev
        $values = array_values($daily);
        $mean = $throughputPerDay;
        $variance = 0.0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= max(1, count($values));
        $stddev = sqrt($variance);

        $confidence = (int) round($this->clamp(90 - ($stddev * 8), 30, 90));

        return [$forecastEnd, $confidence, round($throughputPerDay, 4)];
    }

    private function scopeCreepPct(Project $project): int
    {
        $baselineStart = $project->baseline_start_date ?: $project->start_date;
        if (!$baselineStart) {
            return 0;
        }

        $baselineStart = Carbon::parse($baselineStart)->startOfDay();

        $total = $project->tickets()->count();
        if ($total <= 0) {
            return 0;
        }

        $added = $project->tickets()
            ->where('created_at', '>=', $baselineStart)
            ->count();

        // This is a simple proxy. For a true "original scope", we’d store baseline ticket count.
        $pct = (int) round(($added / $total) * 100);

        return max(0, min(100, $pct));
    }

    private function statusFor(Project $project, Carbon $today, float $expected, float $actual, ?Carbon $forecastEnd): string
    {
        $start = Carbon::parse($project->start_date);
        $end = Carbon::parse($project->end_date);

        if ($today->lt($start)) {
            return 'NOT_STARTED';
        }

        $total = $project->tickets()->count();
        $done = $project->tickets()->done()->count();
        if ($total > 0 && $done === $total) {
            return 'COMPLETED';
        }

        // Forecast-based override (enterprise feel)
        if ($forecastEnd && $forecastEnd->gt($end)) {
            return 'LATE';
        }

        $gap = $expected - $actual;

        if ($gap <= 0.05) {
            return 'ON_TRACK';
        }
        if ($gap <= 0.15) {
            return 'AT_RISK';
        }
        return 'LATE';
    }

    private function riskSignals(
        float $expected,
        float $actual,
        int $overdue,
        int $dueSoon,
        int $scopeCreepPct,
        ?Carbon $forecastEnd,
        Carbon $end
    ): array {
        $signals = [];

        $gap = $expected - $actual;
        if ($gap > 0.15) $signals[] = 'Progress significantly behind plan';
        elseif ($gap > 0.05) $signals[] = 'Progress slightly behind plan';

        if ($overdue > 0) $signals[] = "Overdue tickets: {$overdue}";
        if ($dueSoon > 5) $signals[] = "Many tickets due soon: {$dueSoon}";
        if ($scopeCreepPct >= 20) $signals[] = "Scope creep detected (~{$scopeCreepPct}%)";

        if ($forecastEnd && $forecastEnd->gt($end)) {
            $signals[] = "Forecast end ({$forecastEnd->toDateString()}) exceeds planned end ({$end->toDateString()})";
        }

        return $signals;
    }

    private function clamp(float $v, float $min, float $max): float
    {
        return max($min, min($max, $v));
    }
}
