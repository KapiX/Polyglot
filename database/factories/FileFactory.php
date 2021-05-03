<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\File;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition()
    {
        return [
            'type' => 0,
            'name' => $this->faker->unique()->word(),
        ];
    }
}
