<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Language;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex() {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertSuccessful();
        $response->assertViewIs('users.index');

        $response->assertSeeInOrder([$admin->name], false);
    }

    public function testIndexSearchName() {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'deabcf'
        ]);
        User::factory()->create([
            'name' => 'uxyabw'
        ]);

        $response = $this->actingAs($admin)->get(route('users.index') . '?search=abc');

        $response->assertSuccessful();
        $response->assertViewIs('users.index');

        $response->assertSeeText('deabcf');
        $response->assertDontSeeText('uxyabw');
    }

    public function testEdit() {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.edit', [$user->id]));

        $response->assertSuccessful();
        $response->assertViewIs('users.edit');

        $response->assertSeeInOrder(['<form', 'value="PUT"'], false);
        $response->assertSeeText($user->name);
    }

    public function testEditOneself() {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('users.edit', [$admin->id]));

        $response->assertRedirectToRoute('users.index');
    }

    public function testRegularUserCannotUpdateUsers(): void
    {
        $users = User::factory(2)->create();

        $response = $this->actingAs($users[0])->put(
            route('users.update', [$users[1]->id]),
            [
                'name' => $users[1]['name'] . 'test',
                'email' => $users[1]['email'],
                'role' => [2],
                'languages' => null
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $users[1]['id'],
            'name' => $users[1]['name'],
            'role' => 0
        ]);
        $this->assertDatabaseMissing('users', [
            'role' => 2
        ]);
    }

    public function testDeveloperCannotUpdateUsers(): void
    {
        $developer = User::factory()->developer()->create();
        $user = User::factory()->user()->create();

        $response = $this->actingAs($developer)->put(
            route('users.update', [$user->id]),
            [
                'name' => $user['name'] . 'test',
                'email' => $user['email'],
                'role' => [2],
                'languages' => null
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => 0
        ]);
        $this->assertDatabaseMissing('users', [
            'role' => 2
        ]);
    }

    public function testUpdateLanguagesEmpty(): void
    {
        $languages = Language::factory(5)->create();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create();

        $user->languages()->sync([$languages[0]->id, $languages[2]->id]);

        $this->assertDatabaseCount('language_user', 2);
        $this->assertDatabaseHas('language_user', [
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($admin)->put(
            route('users.update', [$user->id]),
            [
                'name' => $user['name'] . 'test',
                'email' => $user['email'],
                'role' => [$user['role']],
                'languages' => null
            ]
        );

        $response->assertSessionHas('success');
        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseCount('language_user', 0);
        $this->assertDatabaseMissing('language_user', [
            'user_id' => $user->id
        ]);
    }
}
