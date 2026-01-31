<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'owner_id', 'start_date', 'end_date', 
        'baseline_start_date', 'baseline_end_date',
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
}
