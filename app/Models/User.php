<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

    const ROLE_USER = 0;
    const ROLE_DEVELOPER = 1;
    const ROLE_ADMIN = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'provider', 'provider_id'
    ];

    protected $casts = [
        'preferred_languages' => 'array'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    public function projects() {
        return $this->belongsToMany('App\Models\Project')
            ->using('App\Models\ProjectUser')
            ->withPivot('language_id', 'role')
            ->withTimestamps();
    }

    public function languages() {
        return $this->belongsToMany('App\Models\Language')
            ->withTimestamps();
    }

    public static function search($query) {
        return self::where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('email', 'LIKE', '%' . $query . '%');
    }

    public function isAdministrator()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    public function isDeveloper()
    {
        return $this->role == self::ROLE_DEVELOPER;
    }
}
