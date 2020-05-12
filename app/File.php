<?php

namespace Polyglot;

use Polyglot\CatkeysFile;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    // formats
    const CATKEYS = 1;

    protected $fillable = ['name', 'path'];

    public function project()
    {
        return $this->belongsTo('Polyglot\Project');
    }

    public function texts()
    {
        return $this->hasMany('Polyglot\Text');
    }

    public function getFileInstance() {
        switch($this->type) {
            case self::CATKEYS: return new CatkeysFile;
        }
    }
}
