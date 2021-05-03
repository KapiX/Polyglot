<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }

    public function text()
    {
        return $this->belongsTo('App\Models\Text');
    }

    static public function lastUpdatedAt($file_id, $language_id)
    {
        return self::select('updated_at')
                    ->where('language_id', $language_id)
                    ->whereIn('text_id', function($query) use($file_id) {
                        $query->select('id')
                              ->from('texts')
                              ->where('file_id', $file_id);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->limit(1)->first();
    }
}
