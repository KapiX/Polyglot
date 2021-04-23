<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Polyglot\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'provider' => 'github',
            'provider_id' => 0,
            'role' => 0,
            'preferred_languages' => [],
            'remember_token' => Str::random(10),
        ];
    }
}
