<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Text;
use App\Translation;

class Project extends Model
{
    const DEFAULT_ICON = 'images/default-project_32.png';

    public function files()
    {
        return $this->hasMany('App\File');
    }

    public function users()
    {
        return $this->belongsToMany('App\User')
            ->using('App\ProjectUser')
            ->withPivot('language_id', 'role')
            ->withTimestamps();
    }

    public function administrators()
    {
        return $this->users()
            ->wherePivot('role', 2)
            ->orderBy('name');
    }

    // all people who have contributed at some point
    public function contributors()
    {
        return $this->users()
            ->wherePivot('role', '<>', 2)
            ->orderBy('name');
    }

    // active permissions
    public function translators()
    {
        return $this->users()
            ->wherePivot('role', 1)
            ->orderBy('name');
    }

    public function getIconAttribute($value)
    {
        return $value ? 'storage/' . $value . '.png' : self::DEFAULT_ICON;
    }
    
    public function allFilePathsAreUnique()
    {
        return $this->files()
            ->select('path')
            ->groupBy('path')
            ->havingRaw('count(*) > 1')
            ->doesntExist();
    }
    
    public function completeLanguages() {
        return Language::whereIn('id',
            DB::table(self::allNeedsWorkByLanguage()
                // this is actually faster than adding where in each query
                ->where('project_id', $this->id)
                ->having('needs_work', 0))
            ->select('language_id'));
    }

    public static function textsCountPerProject()
    {
        return DB::table(Text::projects())
            ->select('project_id as id')
            ->selectRaw('count(text_id) as text_count')
            ->groupBy('id');
    }

    public static function translatedCountByLanguage()
    {
        return Translation::select('project_id', 'language_id')
            ->selectRaw('count(id) as translated_count')
            ->leftJoinSub(Text::projects(), 'text_project', function($join) {
                $join->on('text_project.text_id', '=', 'translations.text_id');
                $join->where('needs_work', 0);
            })
            ->groupBy('project_id', 'language_id');
    }

    public static function allNeedsWorkByLanguage()
    {
        return DB::table(self::translatedCountByLanguage(), 'translation_counts')
            ->select('project_id', 'language_id')
            ->selectRaw('text_count > translated_count as needs_work')
            ->leftJoinSub(self::textsCountPerProject(), 'text_counts', 'text_counts.id', '=', 'translation_counts.project_id');
    }

    public static function allNeedsWorkForLanguages($languagesToMark)
    {
        $langID_projID_product = self::select('projects.id as pid', 'languages.id as lid')
            ->crossJoin('languages')
            ->whereIn('languages.id', $languagesToMark);
        return DB::table(
            DB::table(
                DB::table(
                    DB::table(self::allNeedsWorkByLanguage())
                        ->select('project_id', 'language_id', 'needs_work')
                        ->whereNotNull('project_id')
                        ->whereIn('language_id', $languagesToMark))
                    ->select('pid as project_id', 'lid as language_id')
                    ->selectRaw('coalesce(needs_work, 1) as needs_work')
                    // join with cross product of languages x projects to fill empty places
                    // (if project_id didn't appear it means it needs work)
                    ->rightJoinSub($langID_projID_product, 'product', function($join) {
                        $join->on('project_id', '=', 'pid');
                        $join->on('language_id', '=', 'lid');
                    }))
                // group by language_id and project_id
                ->select('project_id')
                ->selectRaw('sum(needs_work) > 0 as needs_work')
                ->groupBy('language_id', 'project_id'))
            // then group by project_id to merge the results
            ->select('project_id as id')
            ->selectRaw('sum(needs_work) > 0 as needs_work')
            ->groupBy('id');
    }
}
