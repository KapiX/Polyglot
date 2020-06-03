<?php

namespace Tests\Unit;

use Polyglot\JavaPropertiesFile;
use PHPUnit\Framework\TestCase;

class JavaPropertiesFileTest extends TestCase
{
    const RESOURCES_DIR = __DIR__ . '/resources/';

    private $instance;

    public function setUp() : void
    {
        $this->instance = new JavaPropertiesFile(null);
    }

    public function testProcessBasicTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'basic.properties');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => "https://en.wikipedia.org/",
                'context' => '',
                'comment' => 'website',
                'translation' => "https://en.wikipedia.org/"
            ],
            1 => [
                'text' => "English",
                'context' => '',
                'comment' => 'language',
                'translation' => "English"
            ]
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testProcessCommentsTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'comments.properties');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => "https://en.wikipedia.org/",
                'context' => '',
                'comment' => 'website',
                'translation' => "https://en.wikipedia.org/"
            ],
            1 => [
                'text' => "English",
                'context' => '',
                'comment' => 'language',
                'translation' => "English"
            ]
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testProcessEscapedNewlinesTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'escaped_newlines.properties');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => "a comment",
                'context' => 'comment with escaped \\',
                'comment' => 'not',
                'translation' => "a comment"
            ],
            1 => [
                'text' => "more thanone line value",
                'context' => '',
                'comment' => 'key',
                'translation' => "more thanone line value"
            ],
            2 => [
                'text' => "too many lines",
                'context' => '',
                'comment' => 'longer_key',
                'translation' => "too many lines"
            ]
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testProcessContextsTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'contexts.properties');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => "value1",
                'context' => 'context 1',
                'comment' => 'test.1',
                'translation' => "value1"
            ],
            1 => [
                'text' => "value2",
                'context' => 'context 1',
                'comment' => 'test.2',
                'translation' => "value2"
            ],
            2 => [
                'text' => "value3",
                'context' => 'context 2 is longer',
                'comment' => 'test.3',
                'translation' => "value3"
            ],
            3 => [
                'text' => "value4 has no context",
                'context' => 'test',
                'comment' => 'test.4',
                'translation' => "value4 has no context"
            ],
            4 => [
                'text' => "value5",
                'context' => 'second_test',
                'comment' => 'second_test.5',
                'translation' => "value5"
            ],
            5 => [
                'text' => "value6",
                'context' => 'second_test',
                'comment' => 'second_test.6',
                'translation' => "value6"
            ],
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testAssembleTest()
    {
        $keys = [
            0 => [
                'text' => "value1",
                'context' => 'context 1',
                'comment' => 'test.1',
                'translation' => "value1"
            ],
            1 => [
                'text' => "value2",
                'context' => 'context 1',
                'comment' => 'test.2',
                'translation' => "value2"
            ],
            2 => [
                'text' => "value3",
                'context' => 'context 2 is longer',
                'comment' => 'test.3',
                'translation' => "value3"
            ],
            3 => [
                'text' => "value4 has no context",
                'context' => 'test',
                'comment' => 'test.4',
                'translation' => "value4 has no context"
            ],
            4 => [
                'text' => "value5",
                'context' => 'second_test',
                'comment' => 'second_test.5',
                'translation' => "value5"
            ],
            5 => [
                'text' => "value6",
                'context' => 'second_test',
                'comment' => 'second_test.6',
                'translation' => "value6"
            ],
        ];
        $expected = file_get_contents(self::RESOURCES_DIR . 'contexts_expected.properties');
        $this->assertEquals($expected, $this->instance->assemble($keys));
    }
}
