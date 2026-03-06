<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacultyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_no',
        'employment_type',
        'teaching_rank',
        'rank_step',
        'rank_level_id',
        'original_appointment_date',
    ];

    protected $casts = [
        'original_appointment_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rankLevel()
    {
        return $this->belongsTo(RankLevel::class);
    }
}
