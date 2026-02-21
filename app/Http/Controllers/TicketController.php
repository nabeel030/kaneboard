<?php

namespace App\Http\Controllers;

use App\Enums\TicketType;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketTimeLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class TicketController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,webp,gif,pdf,zip', 'max:10240'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'priority' => ['nullable', 'string'],
            'type' => ['required', new Enum(TicketType::class)],
        ]);

        abort_unless(in_array($data['status'], Ticket::STATUSES, true), 422);

        if (!empty($data['priority'])) {
            abort_unless(in_array($data['priority'], Ticket::PRIORITIES, true), 422);
        }

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
            'assigned_to' => $data['assigned_to'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'priority' => $data['priority'] ?? 'low',
            'type' => $request->type,
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

        if ($ticket) {
            return back()->with('success', 'Ticket created successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $userId = $request->user()->id;

        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'project:id,name',
            'attachments',
            'comments.user:id,name,email',
            'timeLogs'
        ]);

        $timer = $this->buildTimerState($ticket->id, $userId);

        return inertia('Ticket/Show', [
            'ticket' => $ticket,
            'comments' => $ticket->comments,
            'timer' => $timer,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'priority' => ['nullable', 'string'],
            'type' => ['required', new Enum(TicketType::class)],
        ]);

        abort_unless(in_array($data['status'], Ticket::STATUSES, true), 422);

        if (!empty($data['priority'])) {
            abort_unless(in_array($data['priority'], Ticket::PRIORITIES, true), 422);
        }

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

        $ticket->deadline = $data['deadline'] ?? $ticket->deadline;
        $ticket->priority = $data['priority'] ?? $ticket->priority;
        $ticket->type = $data['type'] ?? $ticket->type;
        $updated = $ticket->save();

        if ($updated) {
            return back()->with('success', 'Ticket updated successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $deleted = $ticket->delete();
        if ($deleted) {
            return back()->with('success', 'Ticket deleted successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    private function buildTimerState(int $ticketId, int $userId): array
    {
        $endedSeconds = (int) TicketTimeLog::query()
            ->where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->sum(DB::raw('COALESCE(duration_seconds, TIMESTAMPDIFF(SECOND, started_at, ended_at))'));

        $runningLog = TicketTimeLog::query()
            ->where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $runningSeconds = 0;

        if ($runningLog) {
            $startedAt = $runningLog->started_at;

            if ($startedAt instanceof CarbonInterface) {
                $runningSeconds = $startedAt->diffInSeconds(now(), true);
            } else {
                $runningSeconds = now()->diffInSeconds(Carbon::parse($runningLog->getRawOriginal('started_at')), true);
            }

            $runningSeconds = max(0, (int) $runningSeconds);
        }

        return [
            'running' => (bool) $runningLog,
            'elapsed_seconds' => $endedSeconds + $runningSeconds,
            'started_at' => $runningLog?->started_at?->toISOString(),
        ];
    }
}
