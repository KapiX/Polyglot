<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\Text;
use Illuminate\Database\Seeder;

class TextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $files = File::all();
        $files->pop(1);
        foreach ($files as $file) {
            Text::factory()->for($file)->count(mt_rand(10, 100))->create();
        }
    }
}
