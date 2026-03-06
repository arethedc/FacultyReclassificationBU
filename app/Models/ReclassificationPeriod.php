<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationPeriod extends Model
{
    protected $fillable = [
        'name',
        'cycle_year',
        'start_year',
        'end_year',
        'status',
        'is_open',
        'start_at',
        'end_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'is_open' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function applications()
    {
        return $this->hasMany(ReclassificationApplication::class, 'period_id');
    }
}
