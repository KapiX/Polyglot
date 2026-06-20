<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Text;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $texts = Text::all();
        $languages = Language::all();
        $users = User::all();
        foreach ($texts as $text) {
            foreach ($languages as $language) {
                if(mt_rand(0, 2) > 0) {
                    Translation::factory()->for($text)->for($language)->create([
                        'author_id' => $users->random()->id,
                    ]);
                }
            }
        }
    }
}
