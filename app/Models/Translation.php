<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\PastTranslation;

class Translation extends Model
{
    use HasFactory;

    static protected function booted()
    {
        static::updating(function($translation) {
            $originalTranslation = $translation->getOriginal('translation');
            $originalAuthor = $translation->getOriginal('author_id');
            if($originalTranslation === $translation->translation) {
                $translation->author_id = $originalAuthor;
            } else {
                $pastTranslation = new PastTranslation;
                $pastTranslation->translation_id = $translation->id;
                $pastTranslation->author_id = $originalAuthor;
                $pastTranslation->translation = $originalTranslation;
                $pastTranslation->save();
            }
        });
    }

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }

    public function text()
    {
        return $this->belongsTo('App\Models\Text');
    }

    public function pastTranslations()
    {
        return $this->hasMany('App\Models\PastTranslation');
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
