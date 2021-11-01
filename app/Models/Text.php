<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use App\Models\File;
use App\Models\Language;

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

    public static function splitEndings(array $endings, File $file)
    {
        $case = 'case';
        $bindings = [];
        if(empty($endings)) {
            $case = 'null';
        } else {
            foreach($endings as $ending) {
                $case .= ' when right(text, ?) = ? then ?';
                $bindings[] = mb_strlen($ending, 'UTF-8');
                $bindings[] = $ending;
                $bindings[] = $ending;
            }
            $case .= ' else null end';
        }
        return self::select('texts.id as text_id', 'file_id', 'text', 'comment', 'context')
            ->selectRaw($case . ' as ending', $bindings)
            ->where('file_id', $file->id);
    }

    public static function splitEndingsWithNeedsWork(array $endings, File $file, Language $language)
    {
        return self::splitEndings($endings, $file)
            ->selectRaw('coalesce(needs_work, 1) as needs_work')
            ->leftJoin('translations', function($q) use ($language) {
                $q->on('texts.id', '=', 'translations.text_id')
                    ->on('language_id', '=', DB::raw($language->id));
            });
    }

    public static function pretranslated(File $file, Language $language, bool $all = true)
    {
        $endings = ['...', '…', '.', ':'];
        $q = null;
        if($all == true) {
            $q = self::splitEndings($endings, $file);
        } else {
            $q = self::splitEndingsWithNeedsWork($endings, $file, $language);
        }
        $query = DB::table($q, 't')
            ->select('text_id', 'file_id', 't.text', 'ending', 'context', 'comment')
            ->selectRaw('coalesce(language_id, ?) as language_id', [$language->id])
            ->selectRaw('concat(translation, coalesce(ending, \'\')) as translation')
            ->leftJoin('glossary', function($q) use ($language) {
                // assumes that sql functions called with any parameter = null
                // will return null
                // alternative:
                // on (case
                //   when `ending` is not null then LEFT(`t`.`text`, LENGTH(`t`.`text`)-LENGTH(`ending`))
                //   else `t`.`text`
                // end) = `glossary`.`text`
                $q->on(DB::raw('coalesce(left(t.text, length(t.text) - length(ending)), t.text)'), '=', 'glossary.text')
                    ->on('language_id', '=', DB::raw($language->id));
            })
            ->orderBy('context')
            ->orderBy('t.text');
        if($all == false) {
            $query = $query->addSelect('needs_work')->where('needs_work', 1)->whereNotNull('translation');
        }
        return $query;
    }
}
