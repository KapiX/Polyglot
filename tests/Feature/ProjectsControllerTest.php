<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Project;
use App\Models\Language;
use App\Models\File;
use App\Models\Text;
use App\Models\Translation;

class ProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testProjectsListWorksWithNoProjects()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeText('No projects');
    }
    
    public function testProjectsListWithNoPreferredLanguages()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeText($projects[0]->name);
        $response->assertSeeText($projects[1]->name);
        $response->assertSeeText($projects[2]->name);
    }

    public function testProjectsListShowsDefaultIconIfNotProvided()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeInOrder(['img src=', 'default-project']);
    }

    public function testProjectsListShowsEditButtonOnAllProjectsForAdmin()
    {
        $user = User::factory()->admin()->create();
        $projects = Project::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeInOrder([$projects[0]->name, route('projects.edit', $projects[0]->id), 'Edit']);
        $response->assertSeeInOrder([$projects[1]->name, route('projects.edit', $projects[1]->id), 'Edit']);
        $response->assertSeeInOrder([$projects[2]->name, route('projects.edit', $projects[2]->id), 'Edit']);
    }

    public function testProjectsListShowsEditButtonOnlyOnOwnedProjectsForDeveloper()
    {
        $projects = Project::factory()->count(3)->create();
        $user = User::factory()->developer()
            ->hasAttached(
                [$projects[0], $projects[1]],
                ['role' => 2]
            )->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeInOrder([$projects[0]->name, route('projects.edit', $projects[0]->id), 'Edit']);
        $response->assertSeeInOrder([$projects[1]->name, route('projects.edit', $projects[1]->id), 'Edit']);
        $response->assertDontSee(route('projects.edit', $projects[2]->id));
    }

    public function testProjectsListDoesntShowEditButtonsForUsers()
    {
        $projects = Project::factory()->count(3)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertDontSee(route('projects.edit', $projects[0]->id));
        $response->assertDontSee(route('projects.edit', $projects[1]->id));
        $response->assertDontSee(route('projects.edit', $projects[2]->id));
    }

    public function testProjectsListHighlightsIncompleteProjectsWithOnePreferredLanguageAndNoTranslations()
    {
        $project = Project::factory()->create();
        $language = Language::factory()->create();
        $user = User::factory()->preferredLanguages([$language->id])->create();
        $file = File::factory()->hasTexts(3)->for($project)->create();
        
        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeInOrder(['class="warning"', $project->name], false);
    }

    public function testProjectsListHighlightsIncompleteProjectsWithManyPreferredLanguagesAndNoTranslations()
    {
        $project = Project::factory()->create();
        $language = Language::factory()->count(3)->create();
        $user = User::factory()->preferredLanguages([$language[0]->id, $language[2]->id])->create();
        $file = File::factory()->hasTexts(3)->for($project)->create();
        
        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeInOrder(['class="warning"', $project->name], false);
    }

    public function testAddingProjectSetsProjectAdminAndRedirectsToEdit()
    {
        $user = User::factory()->admin()->create();

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
        $user = User::factory()->developer()->create();

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
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => ''
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    public function testAddedProjectsNameHasToBeUnique()
    {
        $user = User::factory()->admin()->create();

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
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => str_repeat('a', 512)
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    public function testProjectViewWithPreferredLanguage() {
        $project = Project::factory()->create();
        $language = Language::factory()->create();
        $user = User::factory()->preferredLanguages([$language->id])->create();
        $file = File::factory()->hasTexts(3)->for($project)->create();
        
        $response = $this->actingAs($user)->get(route('projects.show', [$project->id]));

        $response->assertSuccessful();
        $response->assertViewIs('projects.show');

        $response->assertSeeInOrder([$file->name, $language->name], false);
    }
}
