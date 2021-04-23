<?php

namespace Polyglot;

use Database\Factories\UserFactory;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

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
        return $this->belongsToMany('Polyglot\Project')
            ->using('Polyglot\ProjectUser')
            ->withPivot('language_id', 'role')
            ->withTimestamps();
    }

    public function languages() {
        return $this->belongsToMany('Polyglot\Language')
            ->withTimestamps();
    }

    public static function search($query) {
        return self::where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('email', 'LIKE', '%' . $query . '%');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
