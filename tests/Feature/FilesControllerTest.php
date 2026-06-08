<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Notifications\ProjectFileUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

use App\Models\File;
use App\Models\Language;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;

class FilesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $project;
    private $file;

    public function setUp() : void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->file = File::factory()->for($this->project)->create();
    }

    public function testEdit()
    {
        $user = User::factory()->developer()->hasAttached(
            [$this->project],
            ['role' => 2]
        )->create();
        $texts = Text::factory()->count(3)->for($this->file)->create();

        $response = $this->actingAs($user)->get(route('files.edit', [$this->project, $this->file]));

        $response->assertSuccessful();
        $response->assertViewIs('files.edit');

        $response->assertSee($texts[1]->context);
        foreach($texts as $text) {
            $response->assertSee($text->text);
        }
    }

    public function testPretranslate()
    {
        $language = Language::factory()->create();
        $user = User::factory()->hasAttached(
            [$language]
        )->create();
        Text::factory()->count(3)->for($this->file)->create();

        $response = $this->actingAs($user)->get(route('files.pretranslate', [$this->project, $this->file, $language]));

        $response->assertSuccessful();
        $response->assertViewIs('files.pretranslate');

        $response->assertSee('Save');
    }

    public function testTranslate()
    {
        $language = Language::factory()->create();
        $user = User::factory()->hasAttached(
            [$language]
        )->create();
        $texts = Text::factory()->count(3)->for($this->file)->create();

        $response = $this->actingAs($user)->get(route('files.translate', [$this->project, $this->file, $language]));

        $response->assertSuccessful();
        $response->assertViewIs('files.translate');

        $response->assertSee($texts[1]->context);
        foreach($texts as $text) {
            $response->assertSee($text->text);
        }
    }

    public function testTranslateDeveloperOfOtherProjectCanTranslateBasedOnLanguagePermission()
    {
        $language = Language::factory()->create();
        $project = Project::factory()->create();
        $developer = User::factory()->developer()
            ->hasAttached([$language])
            ->hasAttached([$project], ['role' => 2])->create();
        $texts = Text::factory()->count(3)->for($this->file)->create();

        $response = $this->actingAs($developer)
            ->get(route('files.translate', [$this->project, $this->file, $language]));

        $response->assertSuccessful();
        $response->assertViewIs('files.translate');

        $response->assertSee($texts[1]->context);
        foreach($texts as $text) {
            $response->assertSee($text->text);
        }
    }

    public function testUpload()
    {
        Notification::fake();

        $this->file->type = File::CATKEYS;
        $this->file->save();
        $language = Language::factory()->create();
        $developer = User::factory()->developer()->hasAttached(
            [$this->project],
            ['role' => 2]
        )->create();
        $contributor = User::factory()->hasAttached(
            [$this->project],
            ['role' => 0]
        )->create();
        $user = User::factory()->hasAttached(
            [$language]
        )->create();

        $content = <<<CATKEYS
1	English	application/x-vnd.tipster	2518152396
Quit	MainWindow	testcomment	Quit
Tipster	System name		Tipster
Tip	MainWindow		Tip
CATKEYS;

        $file = UploadedFile::fake()->createWithContent('en.catkeys', $content);
        $this->assertDatabaseEmpty('texts');

        $response = $this->actingAs($developer)->post(route('files.upload',
            [$this->project, $this->file, $language]), ['catkeys' => $file]);

        $response->assertRedirect(route('files.edit', [$this->project, $this->file]));
        $this->assertDatabaseCount('texts', 3);
        $this->assertDatabaseHas('texts', [
            'file_id' => $this->file->id,
            'text' => 'Quit',
            'comment' => 'testcomment',
            'context' => 'MainWindow'
        ]);
        $this->assertDatabaseHas('texts', [
            'file_id' => $this->file->id,
            'text' => 'Tipster',
            'comment' => '',
            'context' => 'System name'
        ]);
        $this->assertDatabaseHas('texts', [
            'file_id' => $this->file->id,
            'text' => 'Tip',
            'comment' => '',
            'context' => 'MainWindow'
        ]);

        Notification::assertCount(1);
        Notification::assertSentTo($contributor, ProjectFileUpdatedNotification::class);
        Notification::assertNothingSentTo($user);
        Notification::assertNothingSentTo($developer);
    }

    public function testImport() {
        $this->file->type = File::CATKEYS;
        $this->file->metadata = [
            'mime_type' => 'application/x-vnd.tipster',
            'checksum' => '2518152396'
        ];
        $this->file->save();
        $language = Language::factory()->create();
        $user = User::factory()->hasAttached(
            [$language]
        )->create();
        $texts = Text::factory()->for($this->file)->count(3)->sequence(
            ['text' => 'Quit', 'context' => 'MainWindow', 'comment' => 'testcomment'],
            ['text' => 'Tipster', 'context' => 'System name'],
            ['text' => 'Tip', 'context' => 'MainWindow'])->create();

        $content = <<<CATKEYS
1	Polish	application/x-vnd.tipster	2518152396
Quit	MainWindow	testcomment	Wyjdź
Tipster	System name		Tipster
Tip	MainWindow		Porada
CATKEYS;

        $file = UploadedFile::fake()->createWithContent('pl.catkeys', $content);
        $this->assertDatabaseCount('texts', 3);
        $this->assertDatabaseEmpty('translations');

        $response = $this->actingAs($user)->post(route('files.import',
            [$this->project, $this->file, $language]), ['catkeys' => $file]);

        $response->assertRedirect(route('files.translate', [$this->project, $this->file, $language]));
        $this->assertDatabaseCount('translations', 2);
        $this->assertDatabaseHas('translations', [
            'text_id' => $texts[0]->id,
            'language_id' => $language->id,
            'author_id' => $user->id,
            'translation' => 'Wyjdź'
        ]);
        // Tipster is not added as translation because it is the same as original
        $this->assertDatabaseHas('translations', [
            'text_id' => $texts[2]->id,
            'language_id' => $language->id,
            'author_id' => $user->id,
            'translation' => 'Porada'
        ]);
    }

    public function testExportAll() {
        $this->file->type = File::CATKEYS;
        $this->file->save();
        $language = Language::factory()->create();
        $user = User::factory()->create();
        $texts = Text::factory()->count(3)->for($this->file)->create();
        Translation::factory()->for($texts[0])->for($language)->create([
            'author_id' => $user->id
        ]);

        $response = $this->actingAs($user)->get(route('files.exportAll', [$this->project, $this->file]));

        $response->assertDownload($this->project->name . '_' . $this->file->name . '.zip');
    }
}
