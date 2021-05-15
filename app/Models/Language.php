<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    public function translations()
    {
        return $this->hasMany('App\Models\Translation');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User')
            ->withTimestamps();
    }

    /**
     * Queries for all languages with prioritized ids pulled to the top.
     *
     * @param int[]|null $first ids of languages to pull to the top
     * @param string[]|null $columns names of columns to select (name is always selected)
     */
    static public function allWithPrioritized(?array $first, ?array $columns = null)
    {
        if($first != null && !empty($first)) {
            $prioritized = self::whereIn('id', $first);
            $all = self::whereNotIn('id', $first)
                ->union($prioritized)
                ->orderBy('g')
                ->orderBy('name');
            if($columns !== null && !empty($columns)) {
                if(!in_array('name', $columns))
                    $columns[] = 'name';
                $prioritized->select($columns);
                $all->select($columns);
            } else {
                $prioritized->select('*');
                $all->select('*');
            }
            // g is a virtual column which allows to order by in subqueries
            $prioritized->selectRaw('1 as g');
            $all->selectRaw('2 as g');
            return $all;
        }
        return self::select('*');
    }
}
