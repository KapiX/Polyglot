<?php

namespace Polyglot;

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
        return $this->belongsTo('Polyglot\Language');
    }

    public function user()
    {
        return $this->belongsTo('Polyglot\User');
    }

    public function project()
    {
        return $this->belongsTo('Polyglot\Project');
    }

    public function scopeContributors($query, $projectId)
    {
        return $query->where('project_id', $projectId)
                     ->where('role', '<>', 2);
    }
}
