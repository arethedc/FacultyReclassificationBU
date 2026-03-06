<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RankLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'order_no',
    ];
}
