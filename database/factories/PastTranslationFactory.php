<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\PastTranslation;

class PastTranslationFactory extends Factory
{
    protected $model = PastTranslation::class;

    public function definition()
    {
        return [
            'translation' => $this->faker->paragraph($this->faker->randomDigitNotZero())
        ];
    }
}
