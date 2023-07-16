<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

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
            'role' => User::ROLE_USER,
            'preferred_languages' => [],
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state([
            'role' => User::ROLE_ADMIN
        ]);
    }

    public function developer()
    {
        return $this->state([
            'role' => User::ROLE_DEVELOPER
        ]);
    }

    public function user()
    {
        return $this->state([
            'role' => User::ROLE_USER
        ]);
    }

    public function preferredLanguages($languages)
    {
        return $this->state([
            'preferred_languages' => $languages
        ]);
    }
}
