<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationStatusTrail extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'actor_user_id',
        'actor_role',
        'from_status',
        'to_status',
        'from_step',
        'to_step',
        'action',
        'note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

