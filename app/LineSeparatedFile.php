<?php


namespace Polyglot;

class LineSeparatedFile implements TranslationFile
{
    // metadata
    public const SEPARATOR = 'separator';
    public const LAST_EMPTY = 'last_empty';

    private $separator;
    private $last_empty;

    public function __construct($metadata)
    {
        $this->last_empty = false;
        if($metadata === null) {
            // TODO: remove when separator can be adjusted
            $this->separator = '%';
            return;
        }

        $this->separator = $metadata['separator'];
        if(array_key_exists('last_empty', $metadata))
            $this->last_empty = $metadata['last_empty'];
    }

    public function process(string $contents) {
        $this->last_empty = true;
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
        if(empty($buffer) == false) {
            $this->last_empty = false;
            $text = implode("\n", $buffer);
            $catkeys[] = [
                'text' => $text,
                'context' => '',
                'comment' => $i,
                'translation' => $text
            ];
        }
        return $catkeys;
    }

    public function getMetaData(string $key = null)
    {
        switch ($key) {
            // TODO: allow changing separator
            case self::SEPARATOR: return '%';
        }
        return [
            'separator' => '%',
            'last_empty' => $this->last_empty
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
            if($key !== end($keys) || $this->last_empty == true)
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
