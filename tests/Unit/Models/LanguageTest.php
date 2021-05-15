<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Language;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private $languages;

    public function setUp() : void
    {
        parent::setUp();

        $names = ['a', 'b', 'c', 'd', 'e'];
        $this->languages = [];
        foreach($names as $name) {
            $language = new Language;
            $language->name = $name;
            $language->iso_code = $name;
            $language->save();
            $this->languages[] = $language;
        }
    }

    public function testAllWithPrioritizedNull()
    {
        $list = Language::allWithPrioritized(null)
            ->get()->pluck('name')->toArray();
        $expected = ['a', 'b', 'c', 'd', 'e'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedEmptyList()
    {
        $list = Language::allWithPrioritized([])
            ->get()->pluck('name')->toArray();
        $expected = ['a', 'b', 'c', 'd', 'e'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedWithOneId()
    {
        $list = Language::allWithPrioritized([$this->languages[2]->id])
            ->get()->pluck('name')->toArray();
        $expected = ['c', 'a', 'b', 'd', 'e'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedWithThreeIds()
    {
        $list = Language::allWithPrioritized([
                $this->languages[1]->id,
                $this->languages[3]->id,
                $this->languages[4]->id
            ])->get()->pluck('name')->toArray();
        $expected = ['b', 'd', 'e', 'a', 'c'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedWithThreeIdsAndTwoColumns()
    {
        $list = Language::allWithPrioritized([
                $this->languages[1]->id,
                $this->languages[3]->id,
                $this->languages[4]->id
        ], ['iso_code', 'style_guide_url'])->get()->pluck('name')->toArray();
        $expected = ['b', 'd', 'e', 'a', 'c'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedWithThreeIdsAndNameColumn()
    {
        $list = Language::allWithPrioritized([
                $this->languages[1]->id,
                $this->languages[3]->id,
                $this->languages[4]->id
        ], ['iso_code', 'name'])->get()->pluck('name')->toArray();
        $expected = ['b', 'd', 'e', 'a', 'c'];

        $this->assertEquals($expected, $list);
    }

    public function testAllWithPrioritizedWithAllIds()
    {
        $list = Language::allWithPrioritized([
                $this->languages[0]->id,
                $this->languages[1]->id,
                $this->languages[2]->id,
                $this->languages[3]->id,
                $this->languages[4]->id
            ])->get()->pluck('name')->toArray();
        $expected = ['a', 'b', 'c', 'd', 'e'];

        $this->assertEquals($expected, $list);
    }
}
