<?php

namespace Database\Factories;

use App\Models\GlossaryEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlossaryEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlossaryEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'author_id' => User::factory(),
            'text' => $this->faker->paragraph($this->faker->randomDigitNotZero()),
            'translation' => $this->faker->paragraph($this->faker->randomDigitNotZero()),
        ];
    }
}
