<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexLoggedOut(): void
    {
        $response = $this->get(route('index'));

        $response->assertSuccessful();
        $response->assertViewIs('index.index');
    }

    public function testIndexLoggedIn(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('index'));

        $response->assertRedirectToRoute('projects.index');
    }

    public function testLogin(): void
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
        $response->assertViewIs('index.login');
    }

    public function testProfile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertSuccessful();
        $response->assertViewIs('index.profile');
    }

    public function testProfileLoggedOut(): void
    {
        $response = $this->get(route('profile'));

        $response->assertRedirectToRoute('login');
    }

    public function testHelp(): void
    {
        $response = $this->get(route('help'));

        $response->assertSuccessful();
        $response->assertViewIs('help.index');
    }

    public function testHelpDeveloper(): void
    {
        $response = $this->get(route('help', ['article' => 'developer']));

        $response->assertSuccessful();
        $response->assertViewIs('help.developer');
    }

    public function testHelpUser(): void
    {
        $response = $this->get(route('help', ['article' => 'user']));

        $response->assertSuccessful();
        $response->assertViewIs('help.user');
    }

    public function testHelpNotAllowed(): void
    {
        $response = $this->get(route('help', ['article' => 'not_allowed']));

        $response->assertSuccessful();
        $response->assertViewIs('help.index');
    }
}
