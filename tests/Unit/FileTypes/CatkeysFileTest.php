<?php

namespace Tests\Unit\FileTypes;

use Tests\TestCase;
use App\FileTypes\CatkeysFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatkeysFileTest extends TestCase
{
    const RESOURCES_DIR = __DIR__ . '/resources/';

    private $instance;

    public function setUp() : void
    {
        $this->instance = new CatkeysFile(null);
    }

    public function testProcessBasicTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'basic.catkeys');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => 'Quit',
                'context' => 'MainWindow',
                'comment' => 'testcomment',
                'translation' => 'Quit'
            ]
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testProcessMultipleLinesTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'multiple_lines.catkeys');
        $catkeys = $this->instance->process($contents);
        $expected = [
            0 => [
                'text' => 'Quit',
                'context' => 'MainWindow',
                'comment' => '',
                'translation' => 'Quit'
            ],
            1 => [
                'text' => 'An application to show usability tips for Haiku.',
                'context' => 'About',
                'comment' => '',
                'translation' => 'An application to show usability tips for Haiku.'
            ],
            2 => [
                'text' => 'Tipster',
                'context' => 'System name',
                'comment' => '',
                'translation' => 'Tipster'
            ],
            3 => [
                'text' => 'Tip',
                'context' => 'MainWindow',
                'comment' => '',
                'translation' => 'Tip'
            ],
        ];
        $this->assertIsArray($catkeys);
        $this->assertEquals($expected, $catkeys);
    }

    public function testProcessMalformedRowTest()
    {
        $this->expectException(\Exception::class);
        $contents = file_get_contents(self::RESOURCES_DIR . 'malformed.catkeys');
        $this->instance->process($contents);
    }

    public function testMetaDataTest()
    {
        $contents = file_get_contents(self::RESOURCES_DIR . 'basic.catkeys');
        $this->instance->process($contents);
        $this->assertEquals('English', $this->instance->getLanguage());
        $this->assertEquals('application/x-vnd.tipster', $this->instance->getMetaData(CatkeysFile::MIME_TYPE));
        $this->assertEquals('2518152396', $this->instance->getMetaData(CatkeysFile::CHECKSUM));
    }

    public function testAssembleTest()
    {
        $keys = [
            0 => [
                'text' => 'Quit',
                'context' => 'MainWindow',
                'comment' => 'testcomment',
                'translation' => 'Quit'
            ]
        ];
        $expected = file_get_contents(self::RESOURCES_DIR . 'basic.catkeys');
        $this->instance->setLanguage('English');
        $this->instance->setMetaData(CatkeysFile::MIME_TYPE, 'application/x-vnd.tipster');
        $this->instance->setMetaData(CatkeysFile::CHECKSUM, '2518152396');
        $this->assertEquals($expected, $this->instance->assemble($keys));
    }

    public function testAssembleWithEscapingTest()
    {
        $keys = [
            0 => [
                'text' => 'Quit',
                'context' => 'MainWindow',
                'comment' => 'testcomment',
                'translation' => "\n" . 'Quit'
            ],
            1 => [
                'text' => 'Quit application',
                'context' => 'MainWindow',
                'comment' => 'testcomment',
                'translation' => 'Quit' . "\t" . 'application'
            ]
        ];
        $expected = file_get_contents(self::RESOURCES_DIR . 'escaped.catkeys');
        $this->instance->setLanguage('English');
        $this->instance->setMetaData(CatkeysFile::MIME_TYPE, 'application/x-vnd.tipster');
        $this->instance->setMetaData(CatkeysFile::CHECKSUM, '2518152396');
        $this->assertEquals($expected, $this->instance->assemble($keys));
    }
}
