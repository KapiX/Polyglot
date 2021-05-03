<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition()
    {
        return [
            'iso_code' => $this->faker->unique()->languageCode(),
            'name' => $this->faker->unique()->word(),
        ];
    }
}
