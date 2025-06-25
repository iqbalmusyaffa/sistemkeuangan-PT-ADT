<?php

namespace Database\Factories;
use App\Models\Proyek;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proyek_id' => Proyek::factory(),
            'invoice_number' => 'INV-' . $this->faker->unique()->numerify('###'),
            'invoice_date' => now(),
            'grand_total' => 1000000,
            'total_amount' => 1000000, // ✅ FIX ditambahkan agar tidak error
            'amount_paid' => 0,
            'status' => 'unpaid',
        ];
    }
}
