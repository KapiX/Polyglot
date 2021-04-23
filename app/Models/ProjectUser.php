<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUser extends Pivot
{
    /**
    * Indicates if the IDs are auto-incrementing.
    *
    * @var bool
    */
    public $incrementing = true;

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function scopeContributors($query, $projectId)
    {
        return $query->where('project_id', $projectId)
                     ->where('role', '<>', 2);
    }
}
