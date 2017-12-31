<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['name', 'path'];

    public function project()
    {
        return $this->belongsTo('Polyglot\Project');
    }
}
