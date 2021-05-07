<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Project;
use App\Models\Language;
use App\Models\File;
use App\Models\Text;
use App\Models\Translation;

class TextsControllerTest extends TestCase
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

        $this->user = User::factory()->admin()->create();
        $this->project = Project::factory()->create();
        $this->file = File::factory()->for($this->project)->create();
        $this->text = Text::factory()->for($this->file)->create();
        $this->language = Language::factory()->create();
    }

    public function testStoreTranslationCreating()
    {
        $route = route('texts.store', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->postJson($route, [
            'translation' => 'test'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test'
        ]);
        $this->assertDatabaseHas('translations', [
            'text_id' => $this->text->id,
            'language_id' => $this->language->id,
            'author_id' => $this->user->id,
            'translation' => 'test'
        ]);
    }

    public function testStoreTranslationAddsContributor()
    {
        $route = route('texts.store', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->postJson($route, [
            'translation' => 'test'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test'
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'language_id' => $this->language->id,
            'role' => 0
        ]);
    }

    public function testStoreTranslationDoesntAddContributorIfProjectAdmin()
    {
        $this->project->users()->attach([
            $this->user->id => ['role' => 2]
        ]);

        $route = route('texts.store', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->postJson($route, [
            'translation' => 'test'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test'
        ]);
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'language_id' => $this->language->id,
            'role' => 0
        ]);
    }

    public function testStoreTranslationUpdating()
    {
        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);

        $route = route('texts.store', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->postJson($route, [
            'translation' => 'test'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test'
        ]);
        $this->assertDatabaseHas('translations', [
            'text_id' => $this->text->id,
            'language_id' => $this->language->id,
            'author_id' => $this->user->id,
            'translation' => 'test'
        ]);
    }

    public function testStoreTranslationUpdatingNeedsWork()
    {
        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);

        $route = route('texts.store', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->postJson($route, [
            'translation' => 'test',
            'needswork' => 'true'
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test',
            'needswork' => true
        ]);
        $this->assertDatabaseHas('translations', [
            'text_id' => $this->text->id,
            'language_id' => $this->language->id,
            'author_id' => $this->user->id,
            'translation' => 'test',
            'needs_work' => 1
        ]);
    }

    public function testHistory()
    {
        $translation = new Translation;
        $translation->text_id = $this->text->id;
        $translation->language_id = $this->language->id;
        $translation->author_id = $this->user->id;
        $translation->translation = 'dummy';
        $translation->needs_work = 0;
        $translation->save();
        $updatedAt = $translation->updated_at;
    
        $translation->translation = 'test';
        $translation->save();

        $route = route('texts.history', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->get($route);

        $response->assertSuccessful();
        $response->assertJson([
            [
                'author_id' => $this->user->id,
                'translation' => 'dummy',
                'created_at' => $updatedAt->jsonSerialize()
            ]
        ]);
    }

    public function testHistoryDescendingOrder()
    {
        $translation = new Translation;
        $translation->text_id = $this->text->id;
        $translation->language_id = $this->language->id;
        $translation->author_id = $this->user->id;
        $translation->translation = 'dummy';
        $translation->needs_work = 0;
        $translation->save();
        $updated1 = $translation->updated_at;

        $translation->translation = 'test';
        $translation->save();
        $updated2 = $translation->updated_at;

        $translation->translation = 'test2';
        $translation->save();
        $updated3 = $translation->updated_at;
    
        $translation->translation = 'test3';
        $translation->save();

        $route = route('texts.history', [$this->text->id, $this->language->id]); 
        $response = $this->actingAs($this->user)->get($route);

        $response->assertSuccessful();
        $response->assertJson([
            [
                'author_id' => $this->user->id,
                'translation' => 'test2',
                'created_at' => $updated3->jsonSerialize()
            ],
            [
                'author_id' => $this->user->id,
                'translation' => 'test',
                'created_at' => $updated2->jsonSerialize()
            ],
            [
                'author_id' => $this->user->id,
                'translation' => 'dummy',
                'created_at' => $updated1->jsonSerialize()
            ],
        ]);
    }
}
