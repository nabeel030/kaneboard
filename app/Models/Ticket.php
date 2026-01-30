<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $appends = ['is_overdue'];

    protected $fillable = [
        'project_id', 'title', 'description',
        'status', 'position',
        'created_by', 'assigned_to',
        'deadline', 'priority',
    ];

    public const STATUSES = [
        'backlog', 'todo', 'in_progress', 'done', 'tested', 'completed',
    ];

    public const PRIORITIES = [
        'low', 'medium', 'high'
    ];

    protected $casts = [
    'deadline' => 'date:d-m-Y',
    ];

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

        return $this->deadline->startOfDay()->isPast();
    }
}

