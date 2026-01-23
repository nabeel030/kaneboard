<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable','exists:users,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file','mimes:png,jpg,jpeg,webp,gif,pdf,zip','max:10240'],
        ]);

        abort_unless(in_array($data['status'], Ticket::STATUSES, true), 422);

        $nextPos = (int) $project->tickets()
            ->where('status', $data['status'])
            ->max('position');

        if (!empty($data['assigned_to'])) {
            $allowed = $project->owner_id === (int)$data['assigned_to']
                || $project->members()->where('users.id', $data['assigned_to'])->exists();

            abort_unless($allowed, 422);
        }

        $ticket = Ticket::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'position' => $nextPos + 1,
            'created_by' => $request->user()->id,
            'assigned_to' => $data['assigned_to'] ?? null
        ]);

        $files = $request->file('files', []);
        foreach ($files as $file) {
            $filename = Str::uuid() . '-' . $file->getClientOriginalName();
            $path = $file->storeAs("tickets/{$ticket->id}", $filename, 'public');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        if($ticket) {
            return back()->with('success', 'Ticket created successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'project:id,name',
            'attachments',
            'comments.user:id,name,email',
        ]);

        return inertia('Ticket/Show', [
            'ticket' => $ticket,
            'comments' => $ticket->comments()->with('user:id,name,email')->latest()->get()
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable','exists:users,id'],
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

        if (!empty($data['assigned_to'])) {
            $project = $ticket->project;
            $allowed = $project->owner_id === (int)$data['assigned_to']
                || $project->members()->where('users.id', $data['assigned_to'])->exists();

            abort_unless($allowed, 422);
        }

        $ticket->assigned_to = $data['assigned_to'] ?? null;

        $updated = $ticket->save();

        if($updated) {
            return back()->with('success', 'Ticket updated successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $deleted = $ticket->delete();
        if($deleted) {
            return back()->with('success', 'Ticket deleted successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

}
