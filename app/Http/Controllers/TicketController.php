<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'], // validate below
        ]);

        abort_unless(in_array($data['status'], Ticket::STATUSES, true), 422);

        $nextPos = (int) $project->tickets()
            ->where('status', $data['status'])
            ->max('position');

        Ticket::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'position' => $nextPos + 1,
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'],
        ]);

        abort_unless(in_array($data['status'], Ticket::STATUSES, true), 422);

        // If status changes, put it at the end of the new column
        if ($ticket->status !== $data['status']) {
            $maxPos = (int) Ticket::query()
                ->where('project_id', $ticket->project_id)
                ->where('status', $data['status'])
                ->max('position');

            $ticket->status = $data['status'];
            $ticket->position = $maxPos + 1;
        }

        $ticket->title = $data['title'];
        $ticket->description = $data['description'] ?? null;
        $ticket->save();

        return back();
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $ticket->delete();
        return back();
    }

}
