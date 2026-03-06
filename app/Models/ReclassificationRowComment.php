<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationRowComment extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'reclassification_section_entry_id',
        'user_id',
        'body',
        'visibility',
        'action_type',
        'parent_id',
        'status',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function entry()
    {
        return $this->belongsTo(ReclassificationSectionEntry::class, 'reclassification_section_entry_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        // Backward-compatible alias used by older controller/view code paths.
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
