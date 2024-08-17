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
}
