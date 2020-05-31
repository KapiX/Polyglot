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
    public function getExtension() : string;
    public function setLanguage(string $lang);
    public function getLanguage() : string;
    public static function getTypeName() : string;

    // list of columns to match the strings by
    public function matchBy() : array;
}
