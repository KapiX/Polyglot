<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::factory()->count(5)->create();
        foreach ($projects as $project) {
            $project->users()->attach(User::factory()->create()->id, [
                'role' => 2,
            ]);
        }
    }
}
