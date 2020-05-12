<?php


namespace Polyglot;


interface TranslationFile
{
    public function process(string $contents);
    public function getMetaData(string $key);
    public function setMetaData(string $key, string $value);
    public function assemble($keys);
    public function getExtension() : string;
}
