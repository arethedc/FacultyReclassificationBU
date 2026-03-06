<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ReclassificationApplication;


class ReclassificationSection extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'section_code',
        'title',
        'is_complete',
        'points_total',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function entries()
    {
        return $this->hasMany(ReclassificationSectionEntry::class, 'reclassification_section_id');
    }

    public function evidences()
    {
        return $this->hasMany(ReclassificationEvidence::class, 'reclassification_section_id');
    }
}
