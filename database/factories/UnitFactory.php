<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        return [
            'unit_name' => $this->faker->word,
            'unit_code' => strtoupper($this->faker->unique()->bothify('??###')),
        ];
    }
} 