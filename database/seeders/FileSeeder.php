<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\Project;
use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();
        $projects->pop(1);
        foreach ($projects as $project) {
            File::factory()->for($project)->create();
        }
        File::factory()->for($projects[0])->count(2)->create();
    }
}
