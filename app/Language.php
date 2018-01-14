<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function projects()
    {
        return $this->belongsToMany('Polyglot\Project')->withTimestamps();
    }
}
