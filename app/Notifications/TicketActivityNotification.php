<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Ticket $ticket,
        public string $action,
        public string $message,
        public int $actorId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'action' => $this->action,
            'message' => $this->message,
            'actor_id' => $this->actorId,
        ];
    }
}
