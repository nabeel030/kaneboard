<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->notifyProjectUsers($ticket, 'created', "New ticket created: {$ticket->title}");
    }

    public function updated(Ticket $ticket): void
    {
        $changes = $ticket->getChanges();

        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $action = 'updated';
        $message = "Ticket updated: {$ticket->title}";

        if (isset($changes['status']) || isset($changes['position'])) {
            $action = 'moved';
            $message = "Ticket moved: {$ticket->title}";
        }

        if (isset($changes['assigned_to'])) {
            $action = 'assigned';
            $message = "Ticket assignment changed: {$ticket->title}";
        }

        $this->notifyProjectUsers($ticket, $action, $message);
    }

    public function deleted(Ticket $ticket): void
    {
        $this->notifyProjectUsers($ticket, 'deleted', "Ticket deleted: {$ticket->title}");
    }

    private function notifyProjectUsers(Ticket $ticket, string $action, string $message): void
    {
        $project = $ticket->project()->first();
        if (!$project) return;

        $recipientIds = collect([$project->owner_id])
            ->merge($project->members()->pluck('users.id'))
            ->unique()
            ->values();

        $actorId = auth()->id() ?? $ticket->created_by ?? 0;

        $recipientIds = $recipientIds->reject(fn ($id) => (int)$id === (int)$actorId);

        if ($recipientIds->isEmpty()) return;

        User::query()
            ->whereIn('id', $recipientIds)
            ->get()
            ->each(fn ($u) => $u->notify(
                new TicketActivityNotification($project, $ticket, $action, $message, $actorId)
            ));
    }
}
