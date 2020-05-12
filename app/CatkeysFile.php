<?php


namespace Polyglot;

class CatkeysFile implements TranslationFile
{
    private const SEPARATOR = "\t";
    private const LINE_SEPARATOR = "\n";

    // metadata
    public const LANGUAGE = 'language';
    public const MIME_TYPE = 'mime_type';
    public const CHECKSUM = 'checksum';

    private $mime_type;
    private $checksum;
    private $language;

    public function process(string $contents) {
        $separator = "\r\n";
        $line = strtok($contents, $separator);

        $catkeys = [];
        $first = explode(self::SEPARATOR, $line);
        $this->language = $first[1];
        $this->mime_type = $first[2];
        $this->checksum = $first[3];
        $line = strtok($separator);

        $i = 1;
        while($line !== false) {
            ++$i;
            $exploded = explode(self::SEPARATOR, $line);
            if(count($exploded) != 4) {
                throw new \Exception('File is malformed, error is in line ' . $i
                    . '. Most likely a tab is missing or there are too many.');
            }
            $catkeys[] = [
                'text' => $exploded[0],
                'context' => $exploded[1],
                'comment' => $exploded[2],
                'translation' => $exploded[3]
            ];
            $line = strtok($separator);
        }
        return $catkeys;
    }

    public function getMetaData(string $key)
    {
        switch ($key) {
            case self::MIME_TYPE: return $this->mime_type;
            case self::CHECKSUM: return $this->checksum;
            case self::LANGUAGE: return $this->language;
        }
    }

    public function setMetaData(string $key, string $value)
    {
        switch ($key) {
            case self::MIME_TYPE: $this->mime_type = $value; break;
            case self::CHECKSUM: $this->checksum = $value; break;
            case self::LANGUAGE: $this->language = $value; break;
        }
    }

    public function assemble($keys)
    {
        $contents = implode(self::SEPARATOR, ['1', $this->language, $this->mime_type, $this->checksum]) . self::LINE_SEPARATOR;
        foreach($keys as $key) {
            $contents .= implode(self::SEPARATOR, [$key['text'], $key['context'], $key['comment'], $key['translation']]);
            $contents .= self::LINE_SEPARATOR;
        }
        return $contents;
    }

    public function getExtension(): string
    {
        return 'catkeys';
    }
}
