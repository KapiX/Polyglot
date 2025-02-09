<?php

namespace Tests\Unit\Models;

use App\Models\File;
use App\Models\Language;
use App\Models\Project;
use App\Models\Text;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $project;
    private $files;
    private $texts;
    private $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->files = File::factory()->count(3)->for($this->project)->create();
        $this->languages = Language::factory()->count(3)->create();
    }

    protected function insertTexts(): void
    {
        $texts = ['a' => 'text1', 'b' => 'text2', 'c' => 'text3'];

        $index = 0;
        foreach($this->files as $file) {
            $this->texts[] = array();
            foreach($texts as $comment => $text) {
                $t = new Text;
                $t->file_id = $file->id;
                $t->context = 'test';
                $t->comment = $comment;
                $t->text = $text;
                $t->save();
                $this->texts[$index][] = $t;
            }
            $index++;
        }
    }

    public function testTranslationCounts()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        $counts = $this->files[0]->translationCounts($this->languages[0])->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $this->languages[0]->id,
                'needs_work' => 0,
                'count' => 1
            ]
        ];
        $this->assertCount(1, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
    }

    public function testTranslationStatusEmptyFileAllLanguages()
    {
        $status = $this->files[0]->translationStatus()->get()->toArray();

        $this->assertEmpty($status);
    }

    public function testTranslationStatusEmptyFileOneLanguage()
    {
        $status = $this->files[0]->translationStatus($this->languages[0])->get()->toArray();

        $this->assertEmpty($status);
    }

    public function testTranslationStatusEmptyFileTwoLanguagesArray()
    {
        $status = $this->files[0]->translationStatus([$this->languages[0]->id, $this->languages[2]->id])->get()->toArray();

        $this->assertEmpty($status);
    }

    public function testTranslationStatusEmptyFileLanguageQuery()
    {
        $status = $this->files[0]->translationStatus(Language::select('id'))->get()->toArray();

        $this->assertEmpty($status);
    }

    public function testTranslationStatusOneFileAllLanguages()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($author);

        $status = $this->files[0]->translationStatus()->get()->toArray();

        $this->assertCount(2, $status);
        $this->assertEquals($this->files[0]->id, $status[0]->file_id);
        $this->assertEquals($this->languages[0]->id, $status[0]->language_id);
        $this->assertEquals(1, $status[0]->translated);
        $this->assertEquals(0, $status[0]->needs_work);
        $this->assertEquals(3, $status[0]->all_count);
        $this->assertEquals($this->files[0]->id, $status[1]->file_id);
        $this->assertEquals($this->languages[1]->id, $status[1]->language_id);
        $this->assertEquals(2, $status[1]->translated);
        $this->assertEquals(0, $status[1]->needs_work);
        $this->assertEquals(3, $status[1]->all_count);
    }

    public function testTranslationStatusOneFileAllLanguagesNeedsWork()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        $needsWork = ['author_id' => $this->user->id, 'needs_work' => true];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($needsWork);

        $status = $this->files[0]->translationStatus()->get()->toArray();

        $this->assertCount(2, $status);
        $this->assertEquals($this->files[0]->id, $status[0]->file_id);
        $this->assertEquals($this->languages[0]->id, $status[0]->language_id);
        $this->assertEquals(1, $status[0]->translated);
        $this->assertEquals(0, $status[0]->needs_work);
        $this->assertEquals(3, $status[0]->all_count);
        $this->assertEquals($this->files[0]->id, $status[1]->file_id);
        $this->assertEquals($this->languages[1]->id, $status[1]->language_id);
        $this->assertEquals(1, $status[1]->translated);
        $this->assertEquals(1, $status[1]->needs_work);
        $this->assertEquals(3, $status[1]->all_count);
    }

    public function testTranslationStatusOneFileOneLanguage()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($author);

        $status = $this->files[0]->translationStatus($this->languages[0])->get()->toArray();

        $this->assertCount(1, $status);
        $this->assertEquals($this->files[0]->id, $status[0]->file_id);
        $this->assertEquals($this->languages[0]->id, $status[0]->language_id);
        $this->assertEquals(1, $status[0]->translated);
        $this->assertEquals(0, $status[0]->needs_work);
        $this->assertEquals(3, $status[0]->all_count);
    }

    public function testTranslationStatusOneFileTwoLanguagesArray()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[2])->create($author);

        $status = $this->files[0]->translationStatus([$this->languages[0]->id, $this->languages[2]->id])->get()->toArray();

        $this->assertCount(2, $status);
        $this->assertEquals($this->files[0]->id, $status[0]->file_id);
        $this->assertEquals($this->languages[0]->id, $status[0]->language_id);
        $this->assertEquals(1, $status[0]->translated);
        $this->assertEquals(0, $status[0]->needs_work);
        $this->assertEquals(3, $status[0]->all_count);
        $this->assertEquals($this->files[0]->id, $status[1]->file_id);
        $this->assertEquals($this->languages[2]->id, $status[1]->language_id);
        $this->assertEquals(1, $status[1]->translated);
        $this->assertEquals(0, $status[1]->needs_work);
        $this->assertEquals(3, $status[1]->all_count);
    }

    public function testTranslationStatusOneFileTwoLanguagesQuery()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[2])->create($author);

        $query = Language::select('id')->whereIn('id', [$this->languages[0]->id, $this->languages[1]->id]);
        $status = $this->files[0]->translationStatus($query)->get()->toArray();

        $this->assertCount(2, $status);
        $this->assertEquals($this->files[0]->id, $status[0]->file_id);
        $this->assertEquals($this->languages[0]->id, $status[0]->language_id);
        $this->assertEquals(1, $status[0]->translated);
        $this->assertEquals(0, $status[0]->needs_work);
        $this->assertEquals(3, $status[0]->all_count);
        $this->assertEquals($this->files[0]->id, $status[1]->file_id);
        $this->assertEquals($this->languages[1]->id, $status[1]->language_id);
        $this->assertEquals(2, $status[1]->translated);
        $this->assertEquals(0, $status[1]->needs_work);
        $this->assertEquals(3, $status[1]->all_count);
    }

    public function testTranslationStatuses()
    {
        $this->insertTexts();

        $author = ['author_id' => $this->user->id];
        $needsWork = ['author_id' => $this->user->id, 'needs_work' => true];
        Translation::factory()->for($this->texts[0][0])->for($this->languages[0])->create($author);
        Translation::factory()->for($this->texts[0][1])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[0][2])->for($this->languages[1])->create($author);
        Translation::factory()->for($this->texts[1][1])->for($this->languages[2])->create($needsWork);
        Translation::factory()->for($this->texts[1][2])->for($this->languages[2])->create($author);

        $t = new Text;
        $t->file_id = $this->files[0]->id;
        $t->context = '';
        $t->comment = '';
        $t->text = 'text';
        $t->save();
        $this->texts[0][] = $t;

        $status = File::translationStatuses([$this->files[0]->id, $this->files[1]->id])->get()->toArray();

        $expected = [
            0 => [
                'file_id' => $this->files[0]->id,
                'language_id' => $this->languages[0]->id,
                'translated' => 1,
                'needs_work' => 0,
                'all_count' => 4,
                'translated_percent' => 25,
                'needs_work_percent' => 0,
                'total_percent' => 25
            ],
            1 => [
                'file_id' => $this->files[0]->id,
                'language_id' => $this->languages[1]->id,
                'translated' => 2,
                'needs_work' => 0,
                'all_count' => 4,
                'translated_percent' => 50,
                'needs_work_percent' => 0,
                'total_percent' => 50
            ],
            2 => [
                'file_id' => $this->files[1]->id,
                'language_id' => $this->languages[2]->id,
                'translated' => 1,
                'needs_work' => 1,
                'all_count' => 3,
                'translated_percent' => 33,
                'needs_work_percent' => 33,
                'total_percent' => 66
            ]
        ];

        $this->assertCount(3, $status);
        $this->assertEquals($expected[0], (array) $status[0]);
        $this->assertEquals($expected[1], (array) $status[1]);
        $this->assertEquals($expected[2], (array) $status[2]);
    }
}
