<?php

namespace Polyglot;

use Polyglot\CatkeysFile;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    // formats
    const CATKEYS = 1;
    const LINE_SEPARATED = 2;

    protected $fillable = ['name', 'type', 'path'];
    protected $casts = ['metadata' => 'array'];

    public function project()
    {
        return $this->belongsTo('Polyglot\Project');
    }

    public function texts()
    {
        return $this->hasMany('Polyglot\Text');
    }

    public function getFileInstance() {
        switch($this->type) {
            case self::CATKEYS: return new CatkeysFile($this->metadata);
            case self::LINE_SEPARATED: return new LineSeparatedFile($this->metadata);
        }
        return null;
    }

    public static function getTypes() {
        return [
            self::CATKEYS => CatkeysFile::getTypeName(),
            self::LINE_SEPARATED => LineSeparatedFile::getTypeName()
        ];
    }
}
