<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Language;
use App\Models\GlossaryEntry;

class GlossaryEntryTest extends TestCase
{
    use RefreshDatabase;

    private $languages;
    
    public function setUp() : void
    {
        parent::setUp();

        $names = ['a', 'b', 'c'];
        $this->languages = [];
        foreach($names as $name) {
            $language = new Language;
            $language->name = $name;
            $language->iso_code = $name;
            $language->save();
            $this->languages[] = $language;
        }
    }

    public function testGlossariesQueryNoEntries()
    {
        $glossaries = GlossaryEntry::glossaries(Language::select(), ['name'])
            ->get()->pluck('entries', 'name')->toArray();

        $expected = [
            'a' => 0,
            'b' => 0,
            'c' => 0
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testGlossariesQueryEntriesInOneLanguage()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();

        $glossaries = GlossaryEntry::glossaries(Language::select(), ['name'])
            ->get()->pluck('entries', 'name')->toArray();

        $expected = [
            'a' => 5,
            'b' => 0,
            'c' => 0
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testGlossariesQueryEntriesInAllLanguages()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();
        GlossaryEntry::factory()->count(2)->for($this->languages[1])->create();
        GlossaryEntry::factory()->count(6)->for($this->languages[2])->create();

        $glossaries = GlossaryEntry::glossaries(Language::select(), ['name'])
            ->get()->pluck('entries', 'name')->toArray();

        $expected = [
            'a' => 5,
            'b' => 2,
            'c' => 6
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testGlossariesQueryWithPrioritizedLanguages()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();
        GlossaryEntry::factory()->count(2)->for($this->languages[1])->create();
        GlossaryEntry::factory()->count(6)->for($this->languages[2])->create();

        $glossaries = GlossaryEntry::glossaries(
                Language::allWithPrioritized([$this->languages[2]->id]), ['name'])
            ->get()->pluck('entries', 'name')->toArray();

        $expected = [
            'c' => 6,
            'a' => 5,
            'b' => 2
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testGlossariesQueryWithoutColumns()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();

        $glossaries = GlossaryEntry::glossaries(Language::select())
            ->get()->pluck('entries', 'id')->toArray();

        $expected = [
            $this->languages[0]->id => 5,
            $this->languages[1]->id => 0,
            $this->languages[2]->id => 0
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testGlossariesQueryWithExplicitIdColumn()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();

        $glossaries = GlossaryEntry::glossaries(Language::select(), ['id', 'name', 'iso_code'])
            ->get()->pluck('entries', 'iso_code')->toArray();

        $expected = [
            'a' => 5,
            'b' => 0,
            'c' => 0
        ];
        $this->assertEquals($expected, $glossaries);
    }

    public function testDeleteLanguageDeletesGlossary()
    {
        GlossaryEntry::factory()->count(5)->for($this->languages[0])->create();

        $this->assertDatabaseCount('glossary', 5);

        $this->languages[0]->delete();

        $this->assertDatabaseCount('glossary', 0);
    }
}
