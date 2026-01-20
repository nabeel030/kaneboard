<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'project_id', 'title', 'description',
        'status', 'position',
        'created_by', 'assigned_to',
    ];

    public const STATUSES = [
        'backlog', 'todo', 'in_progress', 'done', 'tested', 'completed',
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
}

