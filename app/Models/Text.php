<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    use HasFactory;

    public function file()
    {
        return $this->belongsTo('App\Models\File');
    }

    public function translations()
    {
        return $this->hasMany('App\Models\Translation');
    }

    public static function projects($project_id = null)
    {
        return self::select('texts.id as text_id', 'project_id')->join('files', 'file_id', '=', 'files.id');
    }
}
