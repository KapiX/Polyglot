<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function translations()
    {
        return $this->hasMany('App\Models\Translation');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User')
            ->withTimestamps();
    }
}
