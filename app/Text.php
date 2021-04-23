<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    public function file()
    {
        return $this->belongsTo('App\File');
    }

    public function translations()
    {
        return $this->hasMany('App\Translation');
    }

    public static function projects($project_id = null)
    {
        return self::select('texts.id as text_id', 'project_id')->join('files', 'file_id', '=', 'files.id');
    }
}
