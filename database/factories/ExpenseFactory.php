<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Proyek;
use App\Models\Termin;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'proyek_id' => Proyek::factory(),
            'invoice_id' => null,
            'termin_id' => null,
            'amount' => $this->faker->numberBetween(100000, 1000000),
            'description' => $this->faker->sentence,
            'transaction_date' => now(),
            'status' => 'pending',
            'payment_method_id' => null,
            'category_id' => null,
            'service_category_id' => null,
            'source_type' => null,
            'source_id' => null,
            'prepared_fund' => false,
            'user_id' => null,
        ];
    }
}
