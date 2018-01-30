<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUser extends Pivot
{
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
        return $query->where('project_id', $projectId);
    }
}
