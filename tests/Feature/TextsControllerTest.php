<?php

namespace Tests\Feature;

use App\Notifications\ProjectFileUpdatedNotification;
use App\Notifications\TranslationCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Mockery\Matcher\Not;
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

    protected function notificationTestRequest(?User $actingAs = null, string $needs_work = 'false') : User {
        $developer = User::factory()->developer()->hasAttached(
            [$this->project],
            ['role' => 2]
        )->create();

        $route = route('texts.store', [$this->text, $this->language]);
        $response = $this->actingAs($actingAs ?: $developer)->postJson($route, [
            'translation' => 'test',
            'needswork' => $needs_work,
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'status' => 'success',
            'translation' => 'test'
        ]);
        $this->assertDatabaseHas('translations', [
            'text_id' => $this->text->id,
            'language_id' => $this->language->id,
            'author_id' => $actingAs ? $actingAs->id : $developer->id,
            'translation' => 'test'
        ]);

        return $developer;
    }

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
        $route = route('texts.store', [$this->text, $this->language]); 
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
        $route = route('texts.store', [$this->text, $this->language]); 
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
        // avoid triggering translation completed notification
        Text::factory()->for($this->file)->count(3)->create();

        $this->project->users()->attach([
            $this->user->id => ['role' => 2]
        ]);

        $route = route('texts.store', [$this->text, $this->language]); 
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

        $route = route('texts.store', [$this->text, $this->language]); 
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

    public function testStoreTranslationCompleteNotificationNotSentIfItWasComplete()
    {
        Notification::fake();

        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);

        $this->notificationTestRequest($this->user);

        Notification::assertNothingSent();
    }

    public function testStoreTranslationCompleteNotificationOnlyOneFileInProject()
    {
        Notification::fake();

        $developer = $this->notificationTestRequest($this->user);

        Notification::assertCount(1);
        Notification::assertNotSentTo($this->user, TranslationCompletedNotification::class);
        Notification::assertSentTo($developer, TranslationCompletedNotification::class);
    }

    public function testStoreTranslationCompleteNotificationOnlyOneFileInProjectExistingTranslationNeededWork()
    {
        Notification::fake();

        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id,
            'needs_work' => 1
        ]);

        $developer = $this->notificationTestRequest($this->user);

        Notification::assertCount(1);
        Notification::assertNotSentTo($this->user, TranslationCompletedNotification::class);
        Notification::assertSentTo($developer, TranslationCompletedNotification::class);
    }

    public function testStoreTranslationCompleteNotificationOnlyOneFileInProjectExistingTranslationNeededWorkReplacedWithTranslationThatNeedsWork()
    {
        Notification::fake();

        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id,
            'needs_work' => 1
        ]);

        $developer = $this->notificationTestRequest($this->user, 'true');

        Notification::assertNothingSent();
    }

    public function testStoreTranslationCompleteNotificationMoreFilesInProjectOneCompletedNoTextsInOtherFile()
    {
        Notification::fake();

        $file = File::factory()->for($this->project)->create();

        $developer = $this->notificationTestRequest($this->user);

        Notification::assertCount(1);
        Notification::assertNotSentTo($this->user, TranslationCompletedNotification::class);
        Notification::assertSentTo($developer, TranslationCompletedNotification::class);
    }

    public function testStoreTranslationCompleteNotificationMoreFilesInProjectOneCompletedNoTranslationsInOtherFile()
    {
        Notification::fake();

        $file = File::factory()->for($this->project)->create();
        Text::factory()->for($file)->create();

        $this->notificationTestRequest($this->user);

        Notification::assertNothingSent();
    }

    public function testStoreTranslationCompleteNotificationMoreFilesInProjectOneCompleteSecondCompleted()
    {
        Notification::fake();

        $file = File::factory()->for($this->project)->create();
        $text = Text::factory()->for($file)->create();
        Translation::factory()->for($text)->for($this->language)->create([
            'author_id' => $this->user->id,
        ]);

        $developer = $this->notificationTestRequest($this->user);

        Notification::assertCount(1);
        Notification::assertNotSentTo($this->user, TranslationCompletedNotification::class);
        Notification::assertSentTo($developer, TranslationCompletedNotification::class);
    }

    public function testStoreTranslationCompleteNotificationProjectAdminCompletedTranslation()
    {
        Notification::fake();

        $developer = $this->notificationTestRequest();

        Notification::assertNothingSent();
    }

    public function testStoreTranslationUpdatingNeedsWork()
    {
        Translation::factory()->for($this->text)->for($this->language)->create([
            'author_id' => $this->user->id
        ]);

        $route = route('texts.store', [$this->text, $this->language]); 
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

        $route = route('texts.history', [$this->text, $this->language]); 
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

        $route = route('texts.history', [$this->text, $this->language]);
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
