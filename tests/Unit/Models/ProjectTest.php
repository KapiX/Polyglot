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

class ProjectTest extends TestCase
{use RefreshDatabase;

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

    public function testTranslationStatusesWithQuery()
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

        $status = $this->project->translationStatus()->get()->toArray();

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
