<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Polyglot\User;
use Polyglot\Project;

class ProjectsControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function testIndexShowsProjectsList()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
        $response->assertViewIs('projects.index');
    }

    public function testAddingProjectSetsProjectAdminAndRedirectsToEdit()
    {
        $user = User::factory()->create();
        $user->role = 2;

        $name = 'test';

        $response = $this->actingAs($user)->post('/projects', [
            'name' => $name
        ]);

        $project = Project::where('name', $name)->first();
        $this->assertNotNull($project);

        $this->assertDatabaseHas('project_user', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'role' => 2
        ]);
        
        $response->assertRedirect(route('projects.edit', $project->id));
    }

    public function testDeveloperCanAddProjectsAndIsSetAsItsAdmin()
    {
        $user = User::factory()->create();
        $user->role = 1;

        $name = 'test';

        $response = $this->actingAs($user)->post('/projects', [
            'name' => $name
        ]);

        $project = Project::where('name', $name)->first();
        $this->assertNotNull($project);

        $this->assertDatabaseHas('project_user', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'role' => 2
        ]);
        
        $response->assertRedirect(route('projects.edit', $project->id));
    }

    public function testRegularUserCannotAddProjects()
    {
        $user = User::factory()->create();

        $name = 'test';

        $response = $this->actingAs($user)->post('/projects', [
            'name' => $name
        ]);

        $project = Project::where('name', $name)->first();
        $this->assertNull($project);

        $this->assertDatabaseMissing('project_user', [
            'user_id' => $user->id,
            'role' => 2
        ]);
        
        $response->assertStatus(403);
    }
    
    public function testAddedProjectHasToHaveAName()
    {
        $user = User::factory()->create();
        $user->role = 2;

        $response = $this->actingAs($user)->post('/projects', [
            'name' => ''
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    public function testAddedProjectsNameHasToBeUnique()
    {
        $user = User::factory()->create();
        $user->role = 2;

        $name = 'name';

        $project = new Project();
        $project->name = $name;
        $project->save();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => $name
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    public function testAddedProjectsNameCannotBeTooLong()
    {
        $user = User::factory()->create();
        $user->role = 2;

        $response = $this->actingAs($user)->post('/projects', [
            'name' => str_repeat('a', 512)
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }
}
