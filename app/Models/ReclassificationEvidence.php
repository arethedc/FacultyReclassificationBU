<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationEvidence extends Model
{
    protected $table = 'reclassification_evidences';

    protected $fillable = [
        'reclassification_application_id',
        'reclassification_section_id',
        'reclassification_section_entry_id',
        'uploaded_by_user_id',

        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'label',

        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function section()
    {
        return $this->belongsTo(ReclassificationSection::class, 'reclassification_section_id');
    }

    public function entry()
    {
        return $this->belongsTo(ReclassificationSectionEntry::class, 'reclassification_section_entry_id');
    }

    public function entries()
    {
        return $this->belongsToMany(
            ReclassificationSectionEntry::class,
            'reclassification_evidence_links',
            'reclassification_evidence_id',
            'reclassification_section_entry_id'
        )->withTimestamps();
    }

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
