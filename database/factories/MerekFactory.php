<?php

namespace Database\Factories;

use App\Models\Merek;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerekFactory extends Factory
{
    protected $model = Merek::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'deskripsi' => $this->faker->sentence,
        ];
    }
} 