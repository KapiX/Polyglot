<?php

namespace Tests\Feature;

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
}
