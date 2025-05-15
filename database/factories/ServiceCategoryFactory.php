<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition()
    {
        return [
            'nama_kategori' => $this->faker->word,
            'jenis' => 'pengeluaran',
            'harga' => $this->faker->randomFloat(2, 100000, 1000000),
            'unit_id' => Unit::factory(),
            'deskripsi' => $this->faker->sentence,
        ];
    }
} 