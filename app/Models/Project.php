<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'start_date',
        'end_date',
        'baseline_start_date',
        'baseline_end_date',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'baseline_start_date' => 'date:Y-m-d',
        'baseline_end_date' => 'date:Y-m-d',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function scopeWithTotalTrackedSeconds(Builder $query): Builder
    {
        $now = now();

        $sub = DB::table('ticket_time_logs')
            ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN ticket_time_logs.ended_at IS NULL
                        THEN TIMESTAMPDIFF(SECOND, ticket_time_logs.started_at, ?)
                    ELSE COALESCE(ticket_time_logs.duration_seconds,
                        TIMESTAMPDIFF(SECOND, ticket_time_logs.started_at, ticket_time_logs.ended_at)
                    )
                END
            ), 0)
        ", [$now])
            ->join('tickets', 'tickets.id', '=', 'ticket_time_logs.ticket_id')
            ->whereColumn('tickets.project_id', 'projects.id');

        return $query->addSelect([
            'total_tracked_seconds' => $sub,
        ]);
    }
}
