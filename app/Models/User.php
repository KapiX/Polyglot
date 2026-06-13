<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

    const ROLE_USER = 0;
    const ROLE_DEVELOPER = 1;
    const ROLE_ADMIN = 2;

    private const ROLES_STRINGS = [
        User::ROLE_USER => 'User',
        User::ROLE_DEVELOPER => 'Developer',
        User::ROLE_ADMIN => 'Admin'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'provider', 'provider_id'
    ];

    protected $casts = [
        'preferred_languages' => 'array',
        'email_preferences' => 'array'
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

    public function canMail(string $what) : bool {
        return $this->email && in_array($what, $this->email_preferences ?? []);
    }

    public function isAdministrator()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    public function isDeveloper()
    {
        return $this->role == self::ROLE_DEVELOPER;
    }

    public function roleName() {
        return self::ROLES_STRINGS[$this->role];
    }

    static public function rolesNames() {
        return Collection::make(self::ROLES_STRINGS);
    }
}
