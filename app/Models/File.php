<?php

namespace App\Models;

use \DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\FileTypes\CatkeysFile;
use App\FileTypes\JavaPropertiesFile;
use App\FileTypes\LineSeparatedFile;

class File extends Model
{
    use HasFactory;

    // formats
    const CATKEYS = 1;
    const LINE_SEPARATED = 2;
    const JAVA_PROPERTIES = 3;

    protected $fillable = ['name', 'type', 'path'];
    protected $casts = ['metadata' => 'array'];

    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function texts()
    {
        return $this->hasMany('App\Models\Text');
    }
    
    public function lastUpdatedAt(Language $lang)
    {
        $translationLastUpdated = null;
        $fileLastUpdated = new DateTime($this->updated_at);
        $query = Translation::lastUpdatedAt($this->id, $lang->id);
        if($query !== null)
            $translationLastUpdated = new DateTime($query->updated_at);
        $lastUpdated = $translationLastUpdated > $fileLastUpdated ? $translationLastUpdated : $fileLastUpdated;
        return $lastUpdated->format('Y-m-d H:i:s');
    }

    public function export(Language $lang)
    {
        $lastUpdated = $this->lastUpdatedAt($lang);

        $instance = $this->getFileInstance();
        // see if we have a cached copy
        $directory = sprintf('exported/%u/%u', $this->id, $lang->id);
        $filename = sprintf('%s/%s.%s', $directory, $lastUpdated, $instance->getExtension());
        if(Storage::exists($filename) == false) {
            // we don't, delete old ones and generate new
            Storage::delete(Storage::files($directory));

            $texts_query = $this->texts();
            if($instance->indexColumn() !== null) {
                $texts_query->orderByRaw('cast(' . $instance->indexColumn() . ' as unsigned) asc');
            } else {
                $texts_query->orderBy($instance->matchTranslationsBy()[0]);
            }
            $texts = $texts_query->get()->groupBy('context');
            $translations = Translation::where('language_id', $lang->id)
                ->whereIn('text_id', $this->texts()->select('id')->getQuery())
                ->get()->groupBy('text_id');
            $keys = [];
            foreach($texts as $context) {
                foreach($context as $text) {
                    $t = $translations->get($text['id']);
                    if($t !== null)
                        $translation = $t[0]['translation'];
                    else
                        $translation = $text['text'];
                    $keys[] = [
                        'text' => $text['text'],
                        'context' => $text['context'],
                        'comment' => $text['comment'],
                        'translation' => $translation
                    ];
                }
            }
            $instance->setLanguage($lang->name);
            $result = $instance->assemble($keys);
            Storage::put($filename, $result);
        }
        return $filename;
    }

    public function getFileInstance() {
        switch($this->type) {
            case self::CATKEYS: return new CatkeysFile($this->metadata);
            case self::LINE_SEPARATED: return new LineSeparatedFile($this->metadata);
            case self::JAVA_PROPERTIES: return new JavaPropertiesFile($this->metadata);
        }
        return null;
    }

    public static function getTypes() {
        return [
            self::CATKEYS => CatkeysFile::getTypeName(),
            self::LINE_SEPARATED => LineSeparatedFile::getTypeName(),
            self::JAVA_PROPERTIES => JavaPropertiesFile::getTypeName()
        ];
    }
}
