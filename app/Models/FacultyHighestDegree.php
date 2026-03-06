<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyHighestDegree extends Model
{
    protected $fillable = [
        'user_id',
        'highest_degree',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
