<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    public function file()
    {
        return $this->belongsTo('Polyglot\File');
    }

    public function translations()
    {
        return $this->hasMany('Polyglot\Translation');
    }

    public static function projects()
    {
        return self::select('texts.id as text_id', 'project_id')->join('files', 'file_id', '=', 'files.id');
    }
}
