<?php


namespace Polyglot;

class LineSeparatedFile implements TranslationFile
{
    // metadata
    public const SEPARATOR = 'separator';

    private $separator;

    public function __construct($metadata)
    {
        if($metadata === null) {
            // TODO: remove when separator can be adjusted
            $this->separator = '%';
            return;
        }

        $this->separator = $metadata['separator'];
    }

    public function process(string $contents) {
        $separator = "\r\n";
        $line = strtok($contents, $separator);

        $buffer = [];
        $catkeys = [];
        $i = 1;
        while($line !== false) {
            if($line === $this->separator) {
                $text = implode("\n", $buffer);
                $buffer = [];
                $catkeys[] = [
                    'text' => $text,
                    'context' => '',
                    'comment' => $i,
                    'translation' => $text
                ];
                ++$i;
            } else {
                $buffer[] = $line;
            }
            $line = strtok($separator);
        }
        $text = implode("\n", $buffer);
        $catkeys[] = [
            'text' => $text,
            'context' => '',
            'comment' => $i,
            'translation' => $text
        ];
        return $catkeys;
    }

    public function getMetaData(string $key = null)
    {
        switch ($key) {
            // TODO: allow changing separator
            case self::SEPARATOR: return '%';
        }
        return [
            'separator' => '%'
        ];
        // XXX: exception?
    }

    public function setMetaData(string $key, string $value)
    {
        switch ($key) {
            // case self::SEPARATOR: $this->separator = $value; break;
        }
    }

    public function getLabelForMetaData(string $key) : string
    {
        switch ($key) {
            case self::SEPARATOR: return 'Separator';
        }
        return '';
    }

    public function validateMetaData($metadata) : bool
    {
        return true;
    }

    public function assemble($keys)
    {
        $contents = '';
        foreach($keys as $key) {
            $contents .= $key['translation'] . "\n";
            if($key !== end($keys))
                $contents .= $this->separator . "\n";
        }
        return $contents;
    }

    public function getExtension(): string
    {
        // TODO: allow changing extension
        return 'txt';
    }

    public function setLanguage(string $lang)
    {
        // noop
    }

    public function getLanguage() : string
    {
        return ''; // noop
    }

    public static function getTypeName() : string
    {
        return 'Line separated file';
    }

    public function matchBy(): array
    {
        return ['comment'];
    }
}
