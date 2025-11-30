<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
}
