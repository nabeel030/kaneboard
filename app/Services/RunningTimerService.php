<?php

namespace App\Services;

use App\Models\TicketTimeLog;
use Illuminate\Support\Facades\DB;

class RunningTimerService
{
    public function forUser(int $userId): ?array
    {
        // Find the user's latest running log across ALL tickets
        $runningLog = TicketTimeLog::query()
            ->with('ticket:id,title,status')
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$runningLog) {
            return null;
        }

        $ticketId = (int) $runningLog->ticket_id;

        // ✅ Sum ENDED time for this ticket + this user
        $endedSeconds = (int) TicketTimeLog::query()
            ->where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->sum(DB::raw('COALESCE(duration_seconds, TIMESTAMPDIFF(SECOND, started_at, ended_at))'));

        // ✅ Running seconds for current segment
        $runningSeconds = now()->diffInSeconds($runningLog->started_at);
        $runningSeconds = max(0, (int) $runningSeconds);

        // ✅ TOTAL elapsed for display (ended + running)
        $totalSeconds = max(0, $endedSeconds + $runningSeconds);

        return [
            'log_id' => $runningLog->id,
            'ticket_id' => $ticketId,
            'ticket_title' => $runningLog->ticket?->title,
            'ticket_status' => $runningLog->ticket?->status,
            'started_at' => $runningLog->started_at?->toISOString(),
            'elapsed_seconds' => $totalSeconds, // ✅ IMPORTANT FIX
        ];
    }
}