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
            ->withPivot('language_id', 'role')
            ->withTimestamps();
    }
}
