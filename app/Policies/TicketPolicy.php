<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function update(User $user, Ticket $ticket): bool
    {
        return (int) $ticket->created_by === (int) $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return (int) $ticket->created_by === (int) $user->id;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        $project = $ticket->project;
        return $project->owner_id === $user->id
            || $project->members()->where('users.id', $user->id)->exists();
    }

    public function uploadAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
