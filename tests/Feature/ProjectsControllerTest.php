<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;

use App\Models\Text;
use App\Models\User;
use App\Models\Project;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    public function testProjectsListSortedByNameByDefault()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->sequence(['name' => 'c'], ['name' => 'a'], ['name' => 'b'])->create();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeTextInOrder([$projects[1]->name, $projects[2]->name, $projects[0]->name]);
    }

    public function testProjectsListSortedByLastUpdated()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->sequence(['name' => 'a'], ['name' => 'b'], ['name' => 'c'])->create();

        $files = array();
        $files[] = File::factory()->for($projects[0])->create();
        $files[] = File::factory()->for($projects[1])->create();
        $files[] = File::factory()->for($projects[2])->create();
        Text::factory()->for($files[0])->create();
        $this->travel(5)->seconds();
        Text::factory()->for($files[2])->create();
        $this->travel(5)->seconds();
        Text::factory()->for($files[1])->create();

        $response = $this->actingAs($user)->get('/projects?sort=updated');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeTextInOrder([$projects[1]->name, $projects[2]->name, $projects[0]->name]);
    }

    public function testProjectsListSortedByLastUpdatedAndEmptyProjectsAreLast()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(4)->sequence(['name' => 'a'], ['name' => 'b'], ['name' => 'c'], ['name' => 'd'])->create();

        $files = array();
        $files[] = File::factory()->for($projects[0])->create();
        $files[] = File::factory()->for($projects[1])->create();
        $files[] = File::factory()->for($projects[2])->create();
        Text::factory()->for($files[0])->create();
        $this->travel(5)->seconds();
        Text::factory()->for($files[2])->create();

        $response = $this->actingAs($user)->get('/projects?sort=updated');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeTextInOrder([$projects[2]->name, $projects[0]->name, $projects[1]->name, $projects[3]->name]);
    }

    public function testProjectsListSortedByLastUpdatedNotAffectedByTranslations()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(4)->sequence(['name' => 'a'], ['name' => 'b'], ['name' => 'c'], ['name' => 'd'])->create();

        $files = array();
        $files[] = File::factory()->for($projects[0])->create();
        $files[] = File::factory()->for($projects[1])->create();
        $files[] = File::factory()->for($projects[2])->create();
        $text = Text::factory()->for($files[0])->create();
        $language = Language::factory()->create();
        $this->travel(5)->seconds();
        Text::factory()->for($files[2])->create();
        $this->travel(5)->seconds();
        Translation::factory()->for($text)->for($language)->create([
            'author_id' => $user->id
        ]);

        $response = $this->actingAs($user)->get('/projects?sort=updated');

        $response->assertSuccessful();
        $response->assertViewIs('projects.index');

        $response->assertSeeTextInOrder([$projects[2]->name, $projects[0]->name, $projects[1]->name, $projects[3]->name]);
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

        $response->assertSeeInOrder(['class="table-warning"', $project->name], false);
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

        $response->assertSeeInOrder(['class="table-warning"', $project->name], false);
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

    public function testProjectViewWithEmptyLanguagesDefaultNotShown()
    {
        $project = Project::factory()->create();
        $languages = Language::factory()->count(3)
            ->sequence(['iso_code' => 'a'], ['iso_code' => 'b'], ['iso_code' => 'c'])->create();
        $user = User::factory()->create();
        $file = File::factory()->for($project)->create();
        $texts = Text::factory()->count(3)->for($file)->create();
        $author = ['author_id' => $user->id];
        Translation::factory()->for($texts[0])->for($languages[0])->create($author);
        Translation::factory()->for($texts[1])->for($languages[2])->create($author);

        $response = $this->actingAs($user)->get(route('projects.show', [$project->id]));

        $response->assertSuccessful();
        $response->assertViewIs('projects.show');

        $response->assertSeeInOrder([
            $file->name,
            $languages[0]->name, $languages[2]->name], false);
        $response->assertDontSeeText($languages[1]->name);
    }

    public function testProjectViewWithEmptyLanguagesAllShown()
    {
        $project = Project::factory()->create();
        $languages = Language::factory()->count(3)
            ->sequence(['iso_code' => 'a'], ['iso_code' => 'b'], ['iso_code' => 'c'])->create();
        $user = User::factory()->create();
        $file = File::factory()->for($project)->create();
        $texts = Text::factory()->count(3)->for($file)->create();
        $author = ['author_id' => $user->id];
        Translation::factory()->for($texts[0])->for($languages[0])->create($author);
        Translation::factory()->for($texts[1])->for($languages[2])->create($author);

        $response = $this->actingAs($user)->get(route('projects.show', [$project->id, 'all']));

        $response->assertSuccessful();
        $response->assertViewIs('projects.show');

        $response->assertSeeTextInOrder([
            $file->name,
            $languages[0]->name, $languages[1]->name, $languages[2]->name], false);
    }

    public function testProjectViewWithMultipleFilesAndEmptyLanguagesDefaultNotShown()
    {
        $project = Project::factory()->create();
        $languages = Language::factory()->count(3)
            ->sequence(['iso_code' => 'a'], ['iso_code' => 'b'], ['iso_code' => 'c'])->create();
        $user = User::factory()->create();
        $files = File::factory()->for($project)->count(3)->create();
        $texts0 = Text::factory()->count(3)->for($files[0])->create();
        $texts1 = Text::factory()->count(3)->for($files[1])->create();
        $texts2 = Text::factory()->count(3)->for($files[2])->create();
        $author = ['author_id' => $user->id];
        Translation::factory()->for($texts0[0])->for($languages[0])->create($author);
        Translation::factory()->for($texts1[1])->for($languages[2])->create($author);

        $response = $this->actingAs($user)->get(route('projects.show', [$project->id]));

        $response->assertSuccessful();
        $response->assertViewIs('projects.show');

        $response->assertSeeInOrder([
            $files[0]->name, $files[1]->name, $files[2]->name,
            $languages[0]->name, $languages[2]->name], false);
        $response->assertDontSeeText($languages[1]->name);
    }

    public function testProjectViewWithMultipleFilesAndEmptyLanguagesAllShown()
    {
        $project = Project::factory()->create();
        $languages = Language::factory()->count(3)
            ->sequence(['iso_code' => 'a'], ['iso_code' => 'b'], ['iso_code' => 'c'])->create();
        $user = User::factory()->create();
        $files = File::factory()->for($project)->count(3)->create();
        $texts0 = Text::factory()->count(3)->for($files[0])->create();
        $texts1 = Text::factory()->count(3)->for($files[1])->create();
        $texts2 = Text::factory()->count(3)->for($files[2])->create();
        $author = ['author_id' => $user->id];
        Translation::factory()->for($texts0[0])->for($languages[0])->create($author);
        Translation::factory()->for($texts1[1])->for($languages[2])->create($author);

        $response = $this->actingAs($user)->get(route('projects.show', [$project->id, 'all']));

        $response->assertSuccessful();
        $response->assertViewIs('projects.show');

        $response->assertSeeInOrder([
            $files[0]->name, $files[1]->name, $files[2]->name,
            $languages[0]->name, $languages[1]->name, $languages[2]->name], false);
    }

    public function testProjectEdit() {
        $project = Project::factory()->create();
        Language::factory()->count(3)->create();
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('projects.edit', [$project->id]));

        $response->assertSuccessful();
        $response->assertViewIs('projects.edit');

        $response->assertSeeInOrder(['<form', 'enctype="multipart/form-data"', 'value="PUT"', $project->name], false);
    }
}
