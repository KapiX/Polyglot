<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\PastTranslation;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['text_id', 'language_id', 'author_id', 'translation', 'needs_work'];

    static protected function booted()
    {
        static::updating(function($translation) {
            $original = $translation->getOriginal();
            if($original['translation'] === $translation->translation) {
                $translation->author_id = $original['author_id'];
            } else {
                $pastTranslation = new PastTranslation;
                $pastTranslation->translation_id = $translation->id;
                $pastTranslation->author_id = $original['author_id'];
                $pastTranslation->translation = $original['translation'];
                $pastTranslation->created_at = $original['updated_at'];
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
