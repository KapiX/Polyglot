<?php


namespace Polyglot;

class LineSeparatedFile implements TranslationFile
{
    // metadata
    public const SEPARATOR = 'separator';
    public const LAST_EMPTY = 'last_empty';
    public const EXTENSION = 'extension';

    private $separator;
    private $last_empty;
    private $extension;

    public function __construct($metadata)
    {
        $this->last_empty = false;
        $this->separator = '';
        $this->extension = 'txt';

        if($metadata === null) {
            return;
        }

        if(array_key_exists('last_empty', $metadata))
            $this->last_empty = $metadata['last_empty'];
        if(array_key_exists('separator', $metadata))
            $this->separator = $metadata['separator'] ?: '';
        if(array_key_exists('extension', $metadata))
            $this->extension = $metadata['extension'];
    }

    public function process(string $contents) {
        $this->last_empty = true;
        $separator = "\r\n";

        $contents = str_replace("\r\n", "\n", $contents);
        // strtok skips empty lines

        $pos = strpos($contents, "\n");
        if($pos !== false) {
            $line = substr($contents, 0, $pos);
            $rest = substr($contents, ++$pos);
        } else {
            $line = $contents;
            $rest = false;
        }

        $buffer = [];
        $catkeys = [];
        $i = 1;
        while($line !== false) {
            $lineToCompare = $line;
            if($this->separator === '') {
                $lineToCompare = trim($line);
            }
            if($lineToCompare === $this->separator) {
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
                if($this->separator !== '') {
                    if($line !== '') {
                        $buffer[] = $line;
                    }
                } else {
                    $buffer[] = $line;
                }
            }

            $pos = strpos($rest, "\n");
            if($pos !== false) {
                $line = substr($rest, 0, $pos);
                $rest = substr($rest, ++$pos);
            } else {
                $line = $rest;
                $rest = false;
            }
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
            case self::SEPARATOR: return $this->separator;
            case self::LAST_EMPTY: return $this->last_empty;
            case self::EXTENSION: return $this->extension;
        }
        return [
            'separator' => $this->separator,
            'last_empty' => $this->last_empty,
            'extension' => $this->extension
        ];
        // XXX: exception?
    }

    public function setMetaData(string $key, string $value)
    {
        switch ($key) {
            case self::SEPARATOR: $this->separator = $value; break;
            case self::EXTENSION: $this->extension = $value; break;
        }
    }

    public function getLabelForMetaData(string $key) : string
    {
        switch ($key) {
            case self::SEPARATOR: return 'Separator';
            case self::LAST_EMPTY: return 'Last key empty';
            case self::EXTENSION: return 'Extension';
        }
        return '';
    }

    public function validateMetaData($metadata) : bool
    {
        return true;
    }

    public function editableMetaData(): array
    {
        return [self::SEPARATOR, self::EXTENSION];
    }

    public function assemble($keys)
    {
        $contents = '';
        foreach($keys as $key) {
            $contents .= $key['translation'] . "\n";
            if($key !== end($keys) || ($this->last_empty == true && $this->separator !== ''))
                $contents .= $this->separator . "\n";
        }
        return $contents;
    }

    public function getExtension(): string
    {
        if($this->extension === null)
            return 'txt';
        return $this->extension;
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

    public function matchTranslationsBy(): array
    {
        return ['comment'];
    }

    public function matchTextsBy(): array
    {
        return ['text'];
    }

    public function indexColumn(): ?string
    {
        return 'comment';
    }
}
