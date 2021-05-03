<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Translation;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition()
    {
        return [
            'text' => $this->faker->paragraph($this->faker->randomDigitNotZero())
        ];
    }

    public function needsWork()
    {
        return $this->state([
            'needs_work' => 1
        ]);
    }
}
