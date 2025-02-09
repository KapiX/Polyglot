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
            'type' => mt_rand(1, 3),
            'name' => $this->faker->unique()->lexify('file-????'),
        ];
    }
}
