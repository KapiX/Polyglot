<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class GlossaryEntry extends Model
{
    use HasFactory;
    use HasSlug;

    protected $table = 'glossary';

    protected $casts = [
        'text' => 'string',
        'translation' => 'string',
    ];

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }

    public function scopeSearch($query, $string) {
        return $query->where('text', 'LIKE', '%' . $string . '%')
            ->orWhere('translation', 'LIKE', '%' . $string . '%');
    }

    static public function glossaries(QueryBuilder|EloquentBuilder $languages, ?array $columns = null): QueryBuilder
    {
        $entries = GlossaryEntry::select('language_id')
            ->selectRaw('count(id) as entries')
            ->groupBy('language_id');
        if($columns === null) {
            $columns = ['id'];
        } else {
            if(!in_array('id', $columns))
                $columns[] = 'id';
        }
        return DB::table($languages, 'languages')
            ->select($columns)
            ->selectRaw('coalesce(entries, 0) as entries')
            ->leftJoinSub($entries, 'glossary', function($join) {
                $join->on('languages.id', '=', 'glossary.language_id');
            });
    }

    public function getSlugOptions() : SlugOptions {
        return SlugOptions::create()
            ->generateSlugsFrom('text')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string {
        return 'slug';
    }
}
