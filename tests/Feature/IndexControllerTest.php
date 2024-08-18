<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Language;
use Illuminate\Support\Facades\File;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    public function testUpdateProfile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'abcdef',
            'email' => 'abcdef@example.com'
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasNoErrors();
    }

    public function testUpdateProfileWrongEmail(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => 'abcdef',
            'email' => 'abcdef'
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasErrors(['email']);
    }

    public function testUpdateProfileNoPreferredLanguages(): void
    {
        $user = User::factory()->create([
            'preferred_languages' => [1, 2]
        ]);

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => 'abcdef',
            'email' => 'abcdef@example.com',
            'languages' => []
        ]);

        $this->assertEmpty($user->preferred_languages);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasNoErrors();
    }

    public function testUpdateProfileMultiplePreferredLanguages(): void
    {
        $user = User::factory()->create();
        Language::factory()->count(5)->create();

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => 'abcdef',
            'email' => 'abcdef@example.com',
            'languages' => [1, 2, 5]
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasNoErrors();
    }

    public function testUpdateProfileNonUniqueName(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'xyzw', 'email' => 'xyzw@example.com']);

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => 'xyzw',
            'email' => 'abcdef@example.com'
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasErrors(['name']);
    }

    public function testUpdateProfileNonUniqueEmail(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'xyzw', 'email' => 'xyzw@example.com']);

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => 'abcdef',
            'email' => 'xyzw@example.com'
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasErrors(['email']);
    }

    public function testUpdateProfileNoChanges(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->fromRoute('profile')->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email
        ]);

        $response->assertRedirectToRoute('profile');
        $response->assertSessionHasNoErrors();
    }

    public function testHelp(): void
    {
        $response = $this->get(route('help'));

        $response->assertSuccessful();
        $response->assertViewIs('help.index');

        $response->assertSeeText('This instance is running commit');
    }

    public function testHelpNoCommitHash(): void
    {
        File::shouldReceive('exists')->once()->withArgs([base_path('.git/')])->andReturn(false);
        File::makePartial();

        $response = $this->get(route('help'));

        $response->assertSuccessful();
        $response->assertViewIs('help.index');

        $response->assertDontSeeText('This instance is running commit');
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
