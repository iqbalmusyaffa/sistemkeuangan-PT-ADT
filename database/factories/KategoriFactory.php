<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kategori>
 */
class KategoriFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Kategori::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_kategori' => $this->faker->unique()->words(2, true),
            'jenis' => $this->faker->randomElement(['pemasukan', 'pengeluaran']),
            'deskripsi' => $this->faker->optional(0.7)->paragraph(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
