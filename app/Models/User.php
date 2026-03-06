<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\FacultyHighestDegree;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'department_id',
    'status',
];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
public function department()
{
    return $this->belongsTo(\App\Models\Department::class);
}

public function facultyProfile()
{
    return $this->hasOne(\App\Models\FacultyProfile::class);
}

public function facultyHighestDegree()
{
    return $this->hasOne(FacultyHighestDegree::class);
}

public function reclassificationRowComments()
{
    return $this->hasMany(\App\Models\ReclassificationRowComment::class, 'user_id');
}


}
