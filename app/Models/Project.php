<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['team_id', 'name', 'description'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
