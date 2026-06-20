<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(ProjectSeeder::class);
        $this->call(FileSeeder::class);
        $this->call(TextSeeder::class);
        $this->call(TranslationSeeder::class);
    }
}
