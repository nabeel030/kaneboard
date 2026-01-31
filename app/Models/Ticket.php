<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $appends = ['is_overdue'];

    protected $fillable = [
        'project_id', 'title', 'description',
        'status', 'position',
        'created_by', 'assigned_to',
        'deadline', 'priority',
        'estimate', 'started_at', 'completed_at',
    ];

    public const STATUSES = [
        'backlog', 'todo', 'in_progress', 'done', 'tested', 'completed',
    ];


    public const STARTED_STATUSES = ['in_progress'];
    public const DONE_STATUSES = ['done', 'tested', 'completed'];

    public const PRIORITIES = [
        'low', 'medium', 'high'
    ];

    protected $casts = [
        'deadline' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'estimate' => 'int',
    ];

    protected static function booted()
    {
        static::saving(function (Ticket $ticket) {
            if (!$ticket->isDirty('status')) {
                return;
            }

            $now = Carbon::now();

            // If moved into "started" and started_at is empty, set it once
            if (in_array($ticket->status, self::STARTED_STATUSES, true) && !$ticket->started_at) {
                $ticket->started_at = $now;
            }

            // If moved into "done", set completed_at
            if (in_array($ticket->status, self::DONE_STATUSES, true)) {
                $ticket->completed_at = $ticket->completed_at ?: $now;
            } else {
                // Optional policy:
                // If ticket moved OUT of done, you can null completed_at OR keep it.
                // Enterprise analytics usually keeps it.
                // Uncomment if you prefer nulling:
                // $ticket->completed_at = null;
            }
        });
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', self::DONE_STATUSES);
    }

    public function scopeDone($query)
    {
        return $query->whereIn('status', self::DONE_STATUSES);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        // Do not mark completed tickets as overdue
        if (in_array($this->status, self::DONE_STATUSES, true)) {
            return false;
        }

        return $this->deadline->startOfDay()->isPast();
    }
}

