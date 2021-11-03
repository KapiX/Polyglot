<?php


namespace App\FileTypes;

class JavaPropertiesFile implements TranslationFile
{
    public function __construct($metadata)
    {
    }

    public function process(string $contents) {
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

        $state = 'start';
        $key = '';
        $catkeys = [];
        $buffer = [];
        $context = '';
        while($line !== false) {
            if($line === '') {
                $state = 'empty';
            }
            switch($state) {
                case 'start': {
                    if($line[0] == '#' || $line[0] == '!') {
                        $state = 'comment';
                        if(!empty($context)) {
                            $context .= ' ';
                        }
                        $context .= ltrim(substr($line, 1));
                        break;
                    }
                    $equals_pos = 0;
                    do {
                        $equals_pos = strpos($line, '=', $equals_pos);
                    } while ($line[$equals_pos - 1] == '\\');
                    $key = trim(substr($line, 0, $equals_pos));
                    if($context === '') {
                        $context = strstr($key, '.', true) ?: '';
                    }
                    $buffer[] = rtrim(ltrim(substr($line, $equals_pos + 1)), '\\');
                } break;
            }

            if($state != 'empty' && $line[strlen($line) - 1] != '\\') {
                if($state == 'comment') {
                    $state = 'start';
                } else {
                    if($state == 'value') {
                        $buffer[] = rtrim(ltrim($line), '\\');
                    }
                    $state = 'end';
                }
            } else {
                if($state == 'value') {
                    $buffer[] = rtrim(ltrim($line), '\\');
                }
                if($state == 'start') {
                    $state = 'value';
                }
                if($state == 'comment') {
                    $state = 'start';
                }
                if($state == 'empty') {
                    $context = '';
                    $state = 'start';
                }
            }

            if($state == 'end') {
                $value = implode('', $buffer);
                $catkeys[] = [
                    'text' => $value,
                    'context' => $context,
                    'comment' => $key,
                    'translation' => $value
                ];
                $buffer = [];
                $state = 'start';
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
        return $catkeys;
    }

    public function getMetaData(string $key = null)
    {
        return [];
        // XXX: exception?
    }

    public function setMetaData(string $key, string $value)
    {
    }

    public function getLabelForMetaData(string $key) : string
    {
        return '';
    }

    public function validateMetaData($metadata) : bool
    {
        return true;
    }

    public function editableMetaData(): array
    {
        return [];
    }

    public function assemble($keys)
    {
        $contents = '';
        $context = '';
        foreach($keys as $key) {
            if($context != $key['context']) {
                if(!empty($contents)) {
                    $contents .= "\n";
                }
                $context_from_key = strstr($key['comment'], '.', true);
                if($context_from_key != $key['context']) {
                    $contents .= '# ' . $key['context'] . "\n";
                    $context = $key['context'];
                } else {
                    $context = $context_from_key;
                }
            }
            $contents .= $key['comment'] . '=' . $key['translation'] . "\n";
        }
        return $contents;
    }

    public function getExtension(): string
    {
        return 'properties';
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
        return '.properties (Java)';
    }

    public function matchTranslationsBy(): array
    {
        return ['comment'];
    }

    public function matchTextsBy(): array
    {
        return ['text', 'context', 'comment'];
    }

    public function indexColumn() : ?string
    {
        return null;
    }
}
