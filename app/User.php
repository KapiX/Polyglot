<?php

namespace Polyglot;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'provider', 'provider_id'
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
}
