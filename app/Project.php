<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function files()
    {
        return $this->hasMany('Polyglot\File');
    }

    public function languages()
    {
        return $this->belongsToMany('Polyglot\Language')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany('Polyglot\User')
            ->using('Polyglot\ProjectUser')
            ->withPivot('language_id', 'role')
            ->withTimestamps();
    }

    public function administrators()
    {
        return $this->users()
            ->wherePivot('role', 2)
            ->orderBy('name');
    }

    public function contributors()
    {
        return $this->users()
            ->wherePivot('role', '<>', 2)
            ->orderBy('name');
    }
}
