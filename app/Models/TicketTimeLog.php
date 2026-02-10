<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTimeLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'note',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->ended_at === null;
    }

    public function getLiveDurationSecondsAttribute(): int
    {
        if ($this->ended_at) {
            return (int) ($this->duration_seconds ?? $this->ended_at->diffInSeconds($this->started_at));
        }

        return now()->diffInSeconds($this->started_at);
    }
}
