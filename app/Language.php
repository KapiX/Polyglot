<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function translations()
    {
        return $this->hasMany('Polyglot\Translation');
    }

    public function users()
    {
        return $this->belongsToMany('Polyglot\User')
            ->withTimestamps();
    }
}
