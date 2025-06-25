<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Proyek;
use App\Models\Termin;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat user admin login
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
    }

    public function test_can_list_expenses_with_project_filter()
    {
        $proyek = Proyek::factory()->create();
        Expense::factory()->count(3)->create(['proyek_id' => $proyek->id]);

        $response = $this->getJson("/api/expenses?proyek_id={$proyek->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_create_expense_for_termin()
    {
        $termin = Termin::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/expenses', [
            'termin_id' => $termin->id,
            'type' => 'dp',
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Testing termin expense',
            'payment_method_id' => $paymentMethod->id
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil ditambahkan'
            ]);
    }

    public function test_cannot_create_expense_if_amount_exceeds_budget()
    {
        $proyek = Proyek::factory()->create(['anggaran_kontrak' => 100000]);
        $termin = Termin::factory()->create(['proyek_id' => $proyek->id]);
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->postJson('/api/expenses', [
            'termin_id' => $termin->id,
            'type' => 'dp',
            'amount' => 200000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Over budget expense',
            'payment_method_id' => $paymentMethod->id
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Anggaran proyek tidak mencukupi', $response->json('message'));
    }

    public function test_can_show_expense_detail()
    {
        $expense = Expense::factory()->create();

        $response = $this->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'success']);
    }

    public function test_can_update_expense()
    {
        $expense = Expense::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->putJson("/api/expenses/{$expense->id}", [
            'amount' => 100000,
            'description' => 'Updated description',
            'transaction_date' => now()->toDateString(),
            'status' => 'pending',
            'payment_method_id' => $paymentMethod->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil diperbarui',
            ]);
    }

    public function test_can_delete_expense()
    {
        $expense = Expense::factory()->create();

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil dihapus'
            ]);
    }

    public function test_cannot_delete_expense_linked_to_termin()
    {
        $termin = Termin::factory()->create();
        $expense = Expense::factory()->create([
            'source_type' => 'termin',
            'source_id' => $termin->id
        ]);

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(403);
        $this->assertStringContainsString('Tidak dapat menghapus pengeluaran yang terkait', $response->json('message'));
    }
}
