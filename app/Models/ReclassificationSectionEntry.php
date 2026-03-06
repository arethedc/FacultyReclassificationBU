<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationSectionEntry extends Model
{
    protected $fillable = [
        'reclassification_section_id',
        'criterion_key',
        'title',
        'description',
        'evidence_note',
        'points',
        'is_validated',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'is_validated' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(ReclassificationSection::class, 'reclassification_section_id');
    }

    public function evidences()
    {
        return $this->belongsToMany(
            ReclassificationEvidence::class,
            'reclassification_evidence_links',
            'reclassification_section_entry_id',
            'reclassification_evidence_id'
        )->withTimestamps();
    }

    public function rowComments()
    {
        return $this->hasMany(ReclassificationRowComment::class, 'reclassification_section_entry_id');
    }
}
