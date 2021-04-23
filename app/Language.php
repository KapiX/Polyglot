<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function translations()
    {
        return $this->hasMany('App\Translation');
    }

    public function users()
    {
        return $this->belongsToMany('App\User')
            ->withTimestamps();
    }
}
