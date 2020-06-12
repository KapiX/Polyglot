<?php

namespace Polyglot;

use Illuminate\Database\Eloquent\Model;
use Polyglot\FileTypes\CatkeysFile;
use Polyglot\FileTypes\JavaPropertiesFile;
use Polyglot\FileTypes\LineSeparatedFile;

class File extends Model
{
    // formats
    const CATKEYS = 1;
    const LINE_SEPARATED = 2;
    const JAVA_PROPERTIES = 3;

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
}
