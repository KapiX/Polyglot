<?php

namespace App\Models;

use \DateTime;
use App\FileTypes\CatkeysFile;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use App\FileTypes\LineSeparatedFile;
use App\FileTypes\JavaPropertiesFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class File extends Model
{
    use HasFactory;
    use HasSlug;

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

    public function translationCounts(Language $language): Builder {
        return Translation::counts($this->texts()->select('id')->getQuery(), $language);
    }

    public function translationStatus($language = null): Builder {
        return self::translationStatuses($this->id, $language);
    }

    public static function translationStatuses($file, $language = null): Builder {
        $texts = Text::whereNotNull('file_id');
        if(is_int($file)) {
            $texts->where('file_id', $file);
        } elseif($file instanceof File) {
            $texts->where('file_id', $file->id);
        } else {
            $texts->whereIn('file_id', $file);
        }
        $counts = Translation::counts($texts, $language)
            ->addSelect('file_id')
            ->leftJoin('texts', 'text_id', '=', 'texts.id')
            ->groupBy('file_id');
        $grouped = DB::query()->fromSub($counts, 'temp')
            ->select('temp.file_id', 'language_id', 'all_count')
            ->selectRaw('max(case when needs_work = 0 then temp.count else 0 end) translated')
            ->selectRaw('max(case when needs_work = 1 then temp.count else 0 end) needs_work')
            ->leftJoinSub($texts->select('file_id')->selectRaw('count(id) as all_count')->groupBy('file_id'), 'temp2', 'temp2.file_id', '=', 'temp.file_id')
            ->groupBy('file_id', 'language_id');
        return DB::query()->from($grouped)
            ->select('file_id', 'language_id', 'translated', 'needs_work', 'all_count')
            ->selectRaw('cast(floor(translated / all_count * 100) as signed) as translated_percent')
            ->selectRaw('cast(floor(needs_work / all_count * 100) as signed) as needs_work_percent')
            ->selectRaw('cast(floor((needs_work + translated) / all_count * 100) as signed) as total_percent');
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

    public function getSlugOptions() : SlugOptions {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->extraScope(fn ($builder) => $builder->where('project_id', $this->project_id));
    }

    public function getRouteKeyName(): string {
        return 'slug';
    }
}
