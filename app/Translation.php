<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public function language()
    {
        return $this->belongsTo('Polyglot\Language');
    }

    public function text()
    {
        return $this->belongsTo('Polyglot\Text');
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
