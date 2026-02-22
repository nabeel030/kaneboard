<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketTimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketTimerController extends Controller
{
    /**
     * Start or resume a timer for the authenticated user on this ticket.
     * Rule: user can only have ONE running timer across all tickets.
     */
    public function start(Request $request, Ticket $ticket)
    {
        // authorize if you have policies
        // $this->authorize('update', $ticket->project);

        $this->ensureTrackable($ticket);

        $userId = $request->user()->id;

        DB::transaction(function () use ($userId, $ticket) {
            // Lock user's running logs to prevent race conditions
            $running = TicketTimeLog::query()
                ->where('user_id', $userId)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($running) {
                // If already running on this same ticket, do nothing
                if ((int) $running->ticket_id === (int) $ticket->id) {
                    return;
                }

                $this->stopLog($running);
            }

            // Start new session
            TicketTimeLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'started_at' => now(),
                'ended_at' => null,
            ]);
        });

        return back()->with('success', 'Timer started.');
    }

    /**
     * Pause timer (ends current running session for this ticket + user).
     */
    public function pause(Request $request, Ticket $ticket)
    {
        // $this->authorize('update', $ticket->project);
        $this->ensureTrackable($ticket);

        $userId = $request->user()->id;

        $log = TicketTimeLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$log) {
            return back()->with('error', 'No running timer found to pause.');
        }

        $this->stopLog($log);

        return back()->with('success', 'Timer paused.');
    }

    public function resume(Request $request, Ticket $ticket)
    {
        $this->ensureTrackable($ticket);
        return $this->start($request, $ticket);
    }

    /**
     * Stop is same as pause in this model (ends session).
     */
    public function stop(Request $request, Ticket $ticket)
    {
        $this->ensureTrackable($ticket);
        return $this->pause($request, $ticket);
    }

    public function status(Request $request, Ticket $ticket)
    {
        $userId = $request->user()->id;
        $this->ensureTrackable($ticket);

        $running = TicketTimeLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return response()->json([
            'is_running' => (bool) $running,
            'started_at' => $running?->started_at?->toISOString(),
            'live_seconds' => $running ? now()->diffInSeconds($running->started_at) : 0,
        ]);
    }

    private function stopLog(TicketTimeLog $log): void
    {
        $end = now();
        $start = $log->started_at;

        $seconds = $start->diffInSeconds($end);

        $log->forceFill([
            'ended_at' => $end,
            'duration_seconds' => $seconds,
        ])->save();
    }

    private function ensureTrackable(Ticket $ticket): void
    {
        if ($ticket->status !== 'in_progress') {
            abort(403, 'Timer is only allowed when ticket is In progress.');
        }
    }

    public function update(Request $request, TicketTimeLog $ticketTimeLog)
    {
        $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        // Optional: authorize properly
        // $this->authorize('update', $timeLog);

        DB::transaction(function () use ($ticketTimeLog, $request) {

            $newSeconds = (int) $request->duration_seconds;

            $ticketTimeLog->duration_seconds = $newSeconds;

            if ($ticketTimeLog->started_at) {
                $ticketTimeLog->ended_at = $ticketTimeLog->started_at->copy()->addSeconds($newSeconds);
            }

            $ticketTimeLog->save();
        });

        return back()->with('success', 'Time log updated successfully.');
    }

    public function destroy(TicketTimeLog $timeLog)
    {
        // Optional: authorize
        // $this->authorize('delete', $timeLog);

        $timeLog->delete();

        return back()->with('success', 'Time log deleted successfully.');
    }
}
