<?php


namespace Polyglot;


interface TranslationFile
{
    public function process(string $contents);
    public function assemble($keys);
    public function getMetaData(string $key);
    public function setMetaData(string $key, string $value);
    public function getLabelForMetaData(string $key) : string;
    public function validateMetaData($metadata) : bool;
    public function editableMetaData() : array;
    public function getExtension() : string;
    public function setLanguage(string $lang);
    public function getLanguage() : string;

    public static function getTypeName() : string;

    // list of columns to match translated strings by
    // used in import
    public function matchTranslationsBy() : array;

    // list of columns to match texts (untranslated) by
    // used in upload
    public function matchTextsBy() : array;

    // column containing order index for files without keys
    public function indexColumn() : ?string;
}
