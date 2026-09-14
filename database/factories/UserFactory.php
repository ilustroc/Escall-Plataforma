<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'usuario' => fake()->unique()->safeEmail(),
            'contrasena' => Hash::make('Testing-only-password-42!'),
            'rol' => 'asesor',
            'activo' => 1,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['rol' => 'administrador']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['activo' => 0]);
    }
}
