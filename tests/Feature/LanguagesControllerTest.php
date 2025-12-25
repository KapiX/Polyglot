<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Language;
use App\Models\User;

class LanguagesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexEmpty(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('languages.index'));

        $response->assertSuccessful();
        $response->assertViewIs('languages.index');
    }

    public function testIndex(): void
    {
        $user = User::factory()->admin()->create();
        Language::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('languages.index'));

        $response->assertSuccessful();
        $response->assertViewIs('languages.index');
    }

    public function testIndexForbiddenForUsers(): void
    {
        $user = User::factory()->create();
        Language::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('languages.index'));

        $response->assertForbidden();
    }

    public function testIndexForbiddenForDevelopers(): void
    {
        $user = User::factory()->developer()->create();
        Language::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('languages.index'));

        $response->assertForbidden();
    }

    public function testCreate(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('languages.create'));

        $response->assertSuccessful();
        $response->assertViewIs('languages.create');

        $response->assertSeeInOrder(['<form', 'method="post"'], false);
    }

    public function testCreateForbiddenForUsers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('languages.create'));

        $response->assertForbidden();
    }

    public function testCreateForbiddenForDevelopers(): void
    {
        $user = User::factory()->developer()->create();

        $response = $this->actingAs($user)->get(route('languages.create'));

        $response->assertForbidden();
    }

    public function testStore(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertDatabaseCount('languages', 0);

        $response = $this->actingAs($user)->post(route('languages.store'), [
            'name' => 'English',
            'iso_code' => 'en'
        ]);

        $response->assertRedirect(route('languages.index'));

        $this->assertDatabaseCount('languages', 1);
        $this->assertDatabaseHas('languages', [
            'name' => 'English',
            'iso_code' => 'en',
            'style_guide_url' => null,
            'terminology_url' => null
        ]);
    }

    public function testStoreForbiddenForUsers(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseCount('languages', 0);

        $response = $this->actingAs($user)->post(route('languages.store'), [
            'name' => 'English',
            'iso_code' => 'en'
        ]);

        $response->assertForbidden();
    }

    public function testStoreForbiddenForDevelopers(): void
    {
        $user = User::factory()->developer()->create();

        $this->assertDatabaseCount('languages', 0);

        $response = $this->actingAs($user)->post(route('languages.store'), [
            'name' => 'English',
            'iso_code' => 'en'
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('languages', 0);
    }

    public function testEdit(): void
    {
        $admin = User::factory()->admin()->create();
        $language = Language::factory()->create();

        $response = $this->actingAs($admin)->get(route('languages.edit', [$language]));

        $response->assertSuccessful();
        $response->assertViewIs('languages.edit');

        $response->assertSeeInOrder(['<form', 'value="PUT"'], false);
        $response->assertSeeText($language->name);
    }

    public function testEditForbiddenForUsers(): void
    {
        $admin = User::factory()->create();
        $language = Language::factory()->create();

        $response = $this->actingAs($admin)->get(route('languages.edit', [$language]));

        $response->assertForbidden();
    }

    public function testEditForbiddenForDevelopers(): void
    {
        $admin = User::factory()->developer()->create();
        $language = Language::factory()->create();

        $response = $this->actingAs($admin)->get(route('languages.edit', [$language]));

        $response->assertForbidden();
    }

    public function testUpdateForbiddenForUsers(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();

        $response = $this->actingAs($user)->put(
            route('languages.update', [$language]),
            [
                'name' => 'test',
                'iso_code' => 'te',
                'style_guide_url' => 'https://example.com',
                'terminology_url' => 'https://example.com'
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('languages', $language->toArray());
    }

    public function testUpdateForbiddenForDevelopers(): void
    {
        $user = User::factory()->developer()->create();
        $language = Language::factory()->create();

        $response = $this->actingAs($user)->put(
            route('languages.update', [$language]),
            [
                'name' => 'test',
                'iso_code' => 'te',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('languages', $language->toArray());
    }

    public function testUpdateEmptyStyleGuideAndTerminology(): void
    {
        $user = User::factory()->admin()->create();
        $language = Language::factory()->create([
            'style_guide_url' => 'https://example.com',
            'terminology_url' => 'https://example.com'
        ]);

        $data = [
            'name' => 'test',
            'iso_code' => 'te',
            'style_guide_url' => null,
            'terminology_url' => null
        ];
        $response = $this->actingAs($user)->put(
            route('languages.update', [$language]), $data
        );

        $response->assertRedirectToRoute('languages.index');

        $this->assertDatabaseHas('languages', $data);
    }
}
