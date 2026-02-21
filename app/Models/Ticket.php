<?php

namespace App\Models;

use App\Enums\TicketType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    protected $appends = ['is_overdue', 'tracked_seconds', 'tracked_hours'];

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'position',
        'created_by',
        'assigned_to',
        'deadline',
        'priority',
        'type',
        'estimate',
        'started_at',
        'completed_at',
    ];

    public const STATUSES = [
        'backlog',
        'todo',
        'in_progress',
        'done',
        'tested',
        'completed',
    ];


    public const STARTED_STATUSES = ['in_progress'];
    public const DONE_STATUSES = ['done', 'tested', 'completed'];

    public const PRIORITIES = [
        'low',
        'medium',
        'high'
    ];

    protected $casts = [
        'deadline' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'estimate' => 'int',
        'type' => TicketType::class,
    ];

    protected static function booted()
    {
        static::saving(function (Ticket $ticket) {
            if (!$ticket->isDirty('status')) {
                return;
            }

            $now = Carbon::now();

            $oldStatus = $ticket->getOriginal('status');
            $newStatus = $ticket->status;

            $wasDoneLike = in_array($oldStatus, self::DONE_STATUSES, true);
            $isDoneLike  = in_array($newStatus, self::DONE_STATUSES, true);

            // If moved into "started" and started_at is empty, set it once
            if (in_array($newStatus, self::STARTED_STATUSES, true) && !$ticket->started_at) {
                $ticket->started_at = $now;
            }

            // If moved into done-like, set completed_at AND stop any running timers
            if ($isDoneLike) {
                $ticket->completed_at = $ticket->completed_at ?: $now;

                // ✅ Auto-stop running logs (important)
                // Ensure relation exists in model: timeLogs()
                $ticket->timeLogs()
                    ->whereNull('ended_at')
                    ->get()
                    ->each(function ($log) use ($now) {
                        $log->ended_at = $now;
                        $log->duration_seconds = $log->started_at->diffInSeconds($now);
                        $log->save();
                    });
            }

            // ✅ OPTIONAL (recommended): If reopened from done-like -> not done-like, clear completed_at
            if ($wasDoneLike && !$isDoneLike) {
                $ticket->completed_at = null;
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

        if (in_array($this->status, self::DONE_STATUSES, true)) {
            return false;
        }

        return $this->deadline->startOfDay()->isPast();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TicketTimeLog::class);
    }

    public function getTrackedSecondsAttribute(): int
    {
        $ended = (int) $this->timeLogs()
            ->whereNotNull('ended_at')
            ->sum(DB::raw('COALESCE(duration_seconds, TIMESTAMPDIFF(SECOND, started_at, ended_at))'));

        $running = $this->timeLogs()
            ->whereNull('ended_at')
            ->get()
            ->sum(fn($l) => now()->diffInSeconds($l->started_at));

        return $ended + $running;
    }

    public function getTrackedHoursAttribute(): float
    {
        return round($this->tracked_seconds / 3600, 2);
    }
}
