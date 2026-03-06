<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationApplication extends Model
{
    protected $fillable = [
        'faculty_user_id',
        'period_id',
        'cycle_year',
        'status',
        'current_step',
        'returned_from',
        'submitted_at',
        'finalized_at',
        'current_rank_label_at_approval',
        'approved_rank_label',
        'approved_by_user_id',
        'approved_at',
        'rejection_recommended_by_user_id',
        'rejection_recommendation_reason',
        'rejection_recommended_at',
        'rejection_finalized_by_user_id',
        'rejection_final_reason',
        'rejection_finalized_at',
        'faculty_return_requested_by_user_id',
        'faculty_return_requested_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejection_recommended_at' => 'datetime',
        'rejection_finalized_at' => 'datetime',
        'faculty_return_requested_at' => 'datetime',
    ];

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_user_id');
    }

    public function sections()
    {
        return $this->hasMany(ReclassificationSection::class, 'reclassification_application_id');
    }

    public function period()
    {
        return $this->belongsTo(ReclassificationPeriod::class, 'period_id');
    }

    public function rowComments()
    {
        return $this->hasMany(ReclassificationRowComment::class, 'reclassification_application_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function statusTrails()
    {
        return $this->hasMany(ReclassificationStatusTrail::class, 'reclassification_application_id')
            ->orderBy('created_at');
    }

    public function changeLogs()
    {
        return $this->hasMany(ReclassificationChangeLog::class, 'reclassification_application_id')
            ->orderByDesc('created_at');
    }

    public function rejectionRecommendedBy()
    {
        return $this->belongsTo(User::class, 'rejection_recommended_by_user_id');
    }

    public function rejectionFinalizedBy()
    {
        return $this->belongsTo(User::class, 'rejection_finalized_by_user_id');
    }

    public function facultyReturnRequestedBy()
    {
        return $this->belongsTo(User::class, 'faculty_return_requested_by_user_id');
    }
}
