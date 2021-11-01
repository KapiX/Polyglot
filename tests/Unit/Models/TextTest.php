<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Text;
use App\Models\Translation;
use App\Models\User;
use App\Models\Project;
use App\Models\File;
use App\Models\Language;
use App\Models\GlossaryEntry;

class TextTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $project;
    private $file;
    private $text;
    private $language;
    private $glossaryEntries;

    public function setUp() : void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->file = File::factory()->for($this->project)->create();
        $this->language = Language::factory()->create();

        $this->text = new Text;
        $this->text->file_id = $this->file->id;
        $this->text->context = 'a';
        $this->text->comment = '';

        $entries = ['test' => 'translated', 'test2' => 'translated2', 'test3' => 'translated3'];
        foreach($entries as $text => $translation) {
            $entry = new GlossaryEntry;
            $entry->language_id = $this->language->id;
            $entry->author_id = $this->user->id;
            $entry->text = $text;
            $entry->translation = $translation;
            $entry->save();
            $this->glossaryEntries[] = $entry;
        }
    }

    public function testSplitEndingsEndingsArrayEmpty()
    {
        $this->text->text = 'test';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test'
        ]);

        $result = Text::splitEndings([], $this->file)->get();

        $this->assertNull($result[0]->ending);
    }

    public function testSplitEndingsNoEnding()
    {
        $this->text->text = 'test';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test'
        ]);

        $result = Text::splitEndings(['.'], $this->file)->get();

        $this->assertNull($result[0]->ending);
    }

    public function testSplitEndingsOneEnding()
    {
        $this->text->text = 'test.';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test.'
        ]);

        $result = Text::splitEndings(['.'], $this->file)->get();

        $this->assertEquals('.', $result[0]->ending);
    }

    public function testSplitEndingsUTF8EndingHasCorrectLength()
    {
        $this->text->text = 'test…';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test…'
        ]);

        $result = Text::splitEndings(['…'], $this->file)->get();

        $this->assertEquals('…', $result[0]->ending);
    }

    public function testSplitEndingsOneEndingNotInArray()
    {
        $this->text->text = 'test.';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test.'
        ]);

        $result = Text::splitEndings([','], $this->file)->get();

        $this->assertNull($result[0]->ending);
    }

    public function testSplitEndingsOneMulticharEnding()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndings(['...'], $this->file)->get();

        $this->assertEquals('...', $result[0]->ending);
    }

    public function testSplitEndingsOneEndingOnlyMatchesEndOfText()
    {
        $this->text->text = 'tes.t';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'tes.t'
        ]);

        $result = Text::splitEndings(['.'], $this->file)->get();

        $this->assertNull($result[0]->ending);
    }

    public function testSplitEndingsMultipleEndings()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndings(['...', '.'], $this->file)->get();

        $this->assertEquals('...', $result[0]->ending);
    }

    public function testSplitEndingsMultipleEndingsFirst()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndings(['.', '...'], $this->file)->get();

        $this->assertEquals('.', $result[0]->ending);
    }

    public function testSplitEndingsMultipleEndingsSecond()
    {
        $this->text->text = 'test.:';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test.:'
        ]);

        $result = Text::splitEndings(['.', ':'], $this->file)->get();

        $this->assertEquals(':', $result[0]->ending);
    }

    public function testSplitEndingsMultipleEndingsMultipleResults()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $text2 = new Text;
        $text2->file_id = $this->file->id;
        $text2->context = 'a';
        $text2->comment = '';
        $text2->text = 'test:';
        $text2->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndings(['...', ':'], $this->file)->get();

        $this->assertEquals('...', $result[0]->ending);
        $this->assertEquals(':', $result[1]->ending);
    }

    public function testSplitEndingsWithNeedsWorkWithoutTranslation()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndingsWithNeedsWork(['...', ':'], $this->file, $this->language)->get();

        $this->assertEquals(1, $result[0]->needs_work);
        $this->assertEquals('...', $result[0]->ending);
    }

    public function testSplitEndingsWithNeedsWorkWithTranslationMarkedAsNeedsWork()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $translation = Translation::factory()
            ->for($this->text)->for($this->language)->create([
                'author_id' => $this->user->id,
                'needs_work' => 1
            ]);

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndingsWithNeedsWork(['...', ':'], $this->file, $this->language)->get();

        $this->assertEquals(1, $result[0]->needs_work);
        $this->assertEquals('...', $result[0]->ending);
    }

    public function testSplitEndingsWithNeedsWorkWithTranslation()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $translation = Translation::factory()
            ->for($this->text)->for($this->language)->create([
                'author_id' => $this->user->id
            ]);

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndingsWithNeedsWork(['...', ':'], $this->file, $this->language)->get();

        $this->assertEquals(0, $result[0]->needs_work);
        $this->assertEquals('...', $result[0]->ending);
    }

    public function testSplitEndingsWithNeedsWorkWithTranslationInADifferentLanguage()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $language = Language::factory()->create();
        Translation::factory()->for($this->text)->for($language)->create([
            'author_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('texts', [
            'text' => 'test...'
        ]);

        $result = Text::splitEndingsWithNeedsWork(['...', ':'], $this->file, $this->language)->get();

        $this->assertEquals(1, $result[0]->needs_work);
        $this->assertEquals('...', $result[0]->ending);
    }

    public function testPretranslatedWithoutEnding()
    {
        $this->text->text = 'test';
        $this->text->save();

        $result = Text::pretranslated($this->file, $this->language)->get();

        $this->assertEquals('translated', $result[0]->translation);
    }

    public function testPretranslatedWithEnding()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $result = Text::pretranslated($this->file, $this->language)->get();

        $this->assertEquals('translated...', $result[0]->translation);
    }

    public function testPretranslatedOnlyNeedsWork()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $translation = Translation::factory()
            ->for($this->text)->for($this->language)->create([
                'author_id' => $this->user->id
            ]);

        $text2 = new Text;
        $text2->file_id = $this->file->id;
        $text2->context = 'a';
        $text2->comment = '';
        $text2->text = 'test:';
        $text2->save();

        $result = Text::pretranslated($this->file, $this->language, false)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('translated:', $result[0]->translation);
    }

    public function testPretranslatedOnlyOneFileIsProcessed()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $file = File::factory()->for($this->project)->create();
        $text2 = new Text;
        $text2->file_id = $file->id;
        $text2->context = 'a';
        $text2->comment = '';
        $text2->text = 'test:';
        $text2->save();

        $result = Text::pretranslated($this->file, $this->language)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('translated...', $result[0]->translation);

        $result = Text::pretranslated($file, $this->language)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('translated:', $result[0]->translation);
    }

    public function testPretranslatedOnlyOneLanguageIsProcessed()
    {
        $this->text->text = 'test...';
        $this->text->save();

        $language = Language::factory()->create();
        $entry = new GlossaryEntry;
        $entry->language_id = $language->id;
        $entry->author_id = $this->user->id;
        $entry->text = 'test';
        $entry->translation = 'another translation';
        $entry->save();

        $result = Text::pretranslated($this->file, $this->language)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('translated...', $result[0]->translation);

        $result = Text::pretranslated($this->file, $language)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('another translation...', $result[0]->translation);
    }
}
