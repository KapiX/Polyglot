<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Text;

class TextFactory extends Factory
{
    protected $model = Text::class;

    public function definition()
    {
        return [
            'text' => $this->faker->paragraph($this->faker->randomDigitNotZero()),
            'comment' => $this->faker->boolean(80) ? null : $this->faker->paragraph(1),
            'context' => $this->faker->randomElement([null, 'a', 'b', 'c']),
        ];
    }
}
