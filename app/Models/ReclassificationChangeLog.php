<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationChangeLog extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'reclassification_section_id',
        'reclassification_section_entry_id',
        'section_code',
        'criterion_key',
        'change_type',
        'summary',
        'before_data',
        'after_data',
        'actor_user_id',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function section()
    {
        return $this->belongsTo(ReclassificationSection::class, 'reclassification_section_id');
    }

    public function entry()
    {
        return $this->belongsTo(ReclassificationSectionEntry::class, 'reclassification_section_entry_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

