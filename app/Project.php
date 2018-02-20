<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    const DEFAULT_ICON = 'images/default-project_32.png';

    public function files()
    {
        return $this->hasMany('Polyglot\File');
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

    // all people who have contributed at some point
    public function contributors()
    {
        return $this->users()
            ->wherePivot('role', '<>', 2)
            ->orderBy('name');
    }

    // active permissions
    public function translators()
    {
        return $this->users()
            ->wherePivot('role', 1)
            ->orderBy('name');
    }

    public function getIconAttribute($value)
    {
        return $value ? 'storage/' . $value . '.png' : self::DEFAULT_ICON;
    }
}
