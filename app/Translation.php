<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public function language()
    {
        return $this->belongsTo('Polyglot\Language');
    }

    public function text()
    {
        return $this->belongsTo('Polyglot\Text');
    }
}
