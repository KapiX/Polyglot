<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Translation;
use App\Models\User;
use App\Models\Project;
use App\Models\File;
use App\Models\Text;
use App\Models\Language;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $project;
    private $file;
    private $text;
    private $language;
    private $translation;

    public function setUp() : void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->file = File::factory()->for($this->project)->create();
        $this->text = Text::factory()->for($this->file)->create();
        $this->language = Language::factory()->create();

        $this->translation = new Translation;
        $this->translation->text_id = $this->text->id;
        $this->translation->language_id = $this->language->id;
        $this->translation->author_id = $this->user->id;
    }

    public function testCreateTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'translation' => 'test'
        ]);
        $this->assertDatabaseCount('past_translations', 0);
    }

    public function testUpdateTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->translation->translation = 'test update';
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'translation' => 'test update'
        ]);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'translation' => 'test'
        ]);
    }

    public function testUpdateTranslationPreservesOriginalCreationDate()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();
        $updatedAt = $this->translation->updated_at;

        $this->travel(5)->minutes();

        $this->translation->translation = 'test update';
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'translation' => 'test update'
        ]);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'translation' => 'test',
            'created_at' => $updatedAt
        ]);
    }

    public function testUpdateTranslationPreservesOriginalAuthor()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $secondUser = User::factory()->create();

        $this->translation->author_id = $secondUser->id;
        $this->translation->translation = 'test update';
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'author_id' => $secondUser->id,
            'translation' => 'test update'
        ]);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'author_id' => $this->user->id,
            'translation' => 'test'
        ]);
    }

    public function testUpdateTranslationChangingAuthorWithoutChangingTextDoesntSaveTranslationAndPastTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $secondUser = User::factory()->create();

        $this->translation->author_id = $secondUser->id;
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'author_id' => $this->user->id,
            'translation' => 'test'
        ]);
        $this->assertDatabaseCount('past_translations', 0);
    }

    public function testUpdateTranslationNeedsWorkWithoutChangingTextDoesntSavePastTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->translation->needs_work = 1;
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'translation' => 'test'
        ]);
        $this->assertDatabaseCount('past_translations', 0);
    }

    public function testUpdateTranslationMultipleTimesSavesAllPastTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->translation->translation = 'test update';
        $this->translation->save();

        $this->translation->translation = 'test second update';
        $this->translation->save();

        $this->translation->translation = 'test third update';
        $this->translation->save();

        $this->assertDatabaseHas('translations', [
            'translation' => 'test third update'
        ]);
        $this->assertDatabaseCount('past_translations', 3);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'translation' => 'test'
        ]);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'translation' => 'test update'
        ]);
        $this->assertDatabaseHas('past_translations', [
            'translation_id' => $this->translation->id,
            'translation' => 'test second update'
        ]);
    }

    public function testDeleteTranslation()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->translation->delete();

        $this->assertDatabaseCount('translations', 0);
        $this->assertDatabaseCount('past_translations', 0);
    }

    public function testDeleteTranslationDeletesRelatedPastTranslations()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->translation->translation = 'test update';
        $this->translation->save();

        $this->translation->translation = 'test second update';
        $this->translation->save();

        $this->translation->translation = 'test third update';
        $this->translation->save();

        $this->translation->delete();

        $this->assertDatabaseCount('translations', 0);
        $this->assertDatabaseCount('past_translations', 0);
    }

    public function testTranslationLastUpdatedAt()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);

        $this->assertEquals($lastUpdatedAt['updated_at'], $this->translation->updated_at);
    }

    public function testTranslationLastUpdatedAtWithThreeTranslations()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->travel(5)->minutes();
    
        Text::factory()->for($this->file)->hasTranslations([
            'language_id' => $this->language->id,
            'author_id' => $this->user->id
        ])->create();

        $this->travel(5)->minutes();

        $lastText = Text::factory()->for($this->file)->hasTranslations([
            'language_id' => $this->language->id,
            'author_id' => $this->user->id
        ])->create();

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);

        $this->assertEquals($lastUpdatedAt['updated_at'], $lastText->translations()->first()->updated_at);
    }

    public function testTranslationLastUpdatedAtWithThreeTranslationsAndFirstMostRecentlyUpdated()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $this->travel(5)->minutes();
    
        Text::factory()->for($this->file)->hasTranslations([
            'language_id' => $this->language->id,
            'author_id' => $this->user->id
        ])->create();

        $this->travel(5)->minutes();

        Text::factory()->for($this->file)->hasTranslations([
            'language_id' => $this->language->id,
            'author_id' => $this->user->id
        ])->create();

        $this->travel(5)->minutes();
        
        $this->translation->needs_work = 1;
        $this->translation->save();        

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);

        $this->assertDatabaseCount('translations', 3);
        $this->assertEquals($lastUpdatedAt['updated_at'], $this->translation->updated_at);
    }

    public function testTranslationLastUpdatedAtWithTranslationInTwoLanguages()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $language = Language::factory()->create();

        $this->travel(5)->minutes();

        $translation = Translation::factory()->for($this->text)->for($language)->create([
            'author_id' => $this->user->id
        ]);

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $this->translation->updated_at);

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $translation->updated_at);
    }

    public function testTranslationLastUpdatedAtWithTranslationInTwoFiles()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $file = File::factory()->for($this->project)->create();
        $text = Text::factory()->for($file)->create();

        $this->travel(5)->minutes();

        $translation = Translation::factory()->for($text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $this->translation->updated_at);

        $lastUpdatedAt = Translation::lastUpdatedAt($file->id, $this->language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $translation->updated_at);
    }

    public function testTranslationLastUpdatedAtWithTranslationInTwoFilesWithTwoLanguages()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $language = Language::factory()->create();
        $file = File::factory()->for($this->project)->create();
        $text = Text::factory()->for($file)->create();

        $this->travel(5)->minutes();

        $translation2 = Translation::factory()->for($text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);
    
        $this->travel(5)->minutes();

        $translation3 = Translation::factory()->for($text)->for($language)->create([
            'author_id' => $this->user->id
        ]);

        $this->travel(5)->minutes();

        $translation4 = Translation::factory()->for($this->text)->for($language)->create([
            'author_id' => $this->user->id
        ]);

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $this->language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $this->translation->updated_at);

        $lastUpdatedAt = Translation::lastUpdatedAt($file->id, $this->language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $translation2->updated_at);

        $lastUpdatedAt = Translation::lastUpdatedAt($file->id, $language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $translation3->updated_at);

        $lastUpdatedAt = Translation::lastUpdatedAt($this->file->id, $language->id);
        $this->assertEquals($lastUpdatedAt['updated_at'], $translation4->updated_at);
    }

    public function testTranslationCountsOneTextOneLanguage()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $counts = Translation::counts($this->text)->get()->toArray();

        $expectedCounts = [0 => [
            'language_id' => $this->language->id,
            'needs_work' => 0,
            'count' => 1
        ]];
        $this->assertCount(1, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
    }

    public function testTranslationCountsManyTextsOneLanguage()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id])->get()->toArray();

        $expectedCounts = [0 => [
            'language_id' => $this->language->id,
            'needs_work' => 0,
            'count' => 3
        ]];
        $this->assertCount(1, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
    }

    public function testTranslationCountsManyTextsOneLanguageAndSomeNeedsWork()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id, 'needs_work' => 1],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id])->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $this->language->id,
                'needs_work' => 0,
                'count' => 2
            ],
            1 => [
                'language_id' => $this->language->id,
                'needs_work' => 1,
                'count' => 1
            ]
        ];
        $this->assertCount(2, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
    }

    public function testTranslationCountsManyTextsManyLanguages()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();
        $languages = Language::factory(3)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[0])->forEachSequence(
            ['text_id' => $texts[1]->id],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id])->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $this->language->id,
                'needs_work' => 0,
                'count' => 3
            ],
            1 => [
                'language_id' => $languages[0]->id,
                'needs_work' => 0,
                'count' => 2
            ]
        ];
        $this->assertCount(2, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
    }

    public function testTranslationCountsManyTextsManyLanguagesAndSomeNeedsWork()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();
        $languages = Language::factory(3)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id, 'needs_work' => 1],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[1])->forEachSequence(
            ['text_id' => $texts[1]->id, 'needs_work' => 1],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id])->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $this->language->id,
                'needs_work' => 0,
                'count' => 2
            ],
            1 => [
                'language_id' => $this->language->id,
                'needs_work' => 1,
                'count' => 1
            ],
            2 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 0,
                'count' => 1
            ],
            3 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 1,
                'count' => 1
            ]
        ];
        $this->assertCount(4, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
        $this->assertEquals($expectedCounts[2], (array) $counts[2]);
        $this->assertEquals($expectedCounts[3], (array) $counts[3]);
    }

    public function testTranslationCountsManyTextsManyLanguagesAndSomeNeedsWorkForOneLanguage()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();
        $languages = Language::factory(3)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id, 'needs_work' => 1],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[1])->forEachSequence(
            ['text_id' => $texts[1]->id, 'needs_work' => 1],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id], $languages[1])
            ->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 0,
                'count' => 1
            ],
            1 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 1,
                'count' => 1
            ]
        ];
        $this->assertCount(2, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
    }

    public function testTranslationCountsManyTextsManyLanguagesAndSomeNeedsWorkForManyLanguages()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();
        $languages = Language::factory(3)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id, 'needs_work' => 1],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[0])->forEachSequence(
            ['text_id' => $this->text->id],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[1])->forEachSequence(
            ['text_id' => $texts[1]->id, 'needs_work' => 1],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            [$this->text->id, $texts[0]->id, $texts[1]->id, $texts[2]->id],
            [$languages[0]->id, $languages[1]->id]
        )->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $languages[0]->id,
                'needs_work' => 0,
                'count' => 2
            ],
            1 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 0,
                'count' => 1
            ],
            2 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 1,
                'count' => 1
            ]
        ];
        $this->assertCount(3, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
        $this->assertEquals($expectedCounts[2], (array) $counts[2]);
    }

    public function testTranslationCountsManyTextsManyLanguagesAndSomeNeedsWorkForManyLanguagesWithTextsQuery()
    {
        $this->translation->translation = 'test';
        $this->translation->needs_work = 0;
        $this->translation->save();

        $texts = Text::factory(3)->for($this->file)->create();
        $languages = Language::factory(3)->create();

        Translation::factory()->for($this->language)->forEachSequence(
            ['text_id' => $texts[0]->id, 'needs_work' => 1],
            ['text_id' => $texts[1]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[0])->forEachSequence(
            ['text_id' => $this->text->id],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        Translation::factory()->for($languages[1])->forEachSequence(
            ['text_id' => $texts[1]->id, 'needs_work' => 1],
            ['text_id' => $texts[2]->id],
        )->create([
            'author_id' => $this->user->id
        ]);

        $counts = Translation::counts(
            $this->file->texts()->select('id')->getQuery(),
            [$languages[0]->id, $languages[1]->id]
        )->get()->toArray();

        $expectedCounts = [
            0 => [
                'language_id' => $languages[0]->id,
                'needs_work' => 0,
                'count' => 2
            ],
            1 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 0,
                'count' => 1
            ],
            2 => [
                'language_id' => $languages[1]->id,
                'needs_work' => 1,
                'count' => 1
            ]
        ];
        $this->assertCount(3, $counts);
        $this->assertEquals($expectedCounts[0], (array) $counts[0]);
        $this->assertEquals($expectedCounts[1], (array) $counts[1]);
        $this->assertEquals($expectedCounts[2], (array) $counts[2]);
    }
}
