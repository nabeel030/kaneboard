<?php

namespace App\Http\Controllers;

use App\Models\TicketTimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketTimeLogController extends Controller
{
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

            // If log has ended_at, adjust it accordingly
            if ($ticketTimeLog->started_at) {
                $ticketTimeLog->ended_at = $ticketTimeLog->started_at->copy()->addSeconds($newSeconds);
            }

            $ticketTimeLog->save();
        });

        return back()->with('success', 'Time log updated successfully.');
    }

    public function destroy(TicketTimeLog $ticketTimeLog)
    {
        // Optional: authorize
        // $this->authorize('delete', $ticketTimeLog);

        $ticketTimeLog->delete();

        return back()->with('success', 'Time log deleted successfully.');
    }
}