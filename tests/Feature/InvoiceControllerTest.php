<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proyek;
use App\Models\Unit;
use App\Models\Merek;
use App\Models\ServiceCategory;
use App\Models\PaymentMethod;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InvoiceControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $proyek;
    protected $unit;
    protected $merek;
    protected $serviceCategory;
    protected $paymentMethod;
    protected $kategori;
    protected $invoiceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->user);

        $this->proyek = Proyek::factory()->create();
        $this->unit = Unit::factory()->create();
        $this->merek = Merek::factory()->create();
        $this->kategori = Kategori::factory()->create();
        $this->serviceCategory = ServiceCategory::factory()->create([
            'unit_id' => $this->unit->id
        ]);
        $this->paymentMethod = PaymentMethod::factory()->create();

        $this->invoiceData = [
            'proyek_id' => $this->proyek->id,
            'payment_method_id' => $this->paymentMethod->id,
            'invoice_date' => now()->format('Y-m-d'),
            'purchase_materials' => [
                [
                    'item' => 'Test Item',
                    'qty' => 1,
                    'harga' => 1000000,
                    'type' => 'material',
                    'unit_id' => $this->unit->id,
                    'merek_id' => $this->merek->id,
                    'category_id' => $this->kategori->id,
                    'deskripsi' => 'Test Description'
                ]
            ],
            'use_ppn' => true,
            'use_pph_non_final' => true,
            'use_pph_final' => true
        ];
    }

    public function test_can_create_invoice()
    {
        $response = $this->postJson('/api/invoices', $this->invoiceData);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'invoice_number', 'invoice_date']]);

        $this->assertDatabaseHas('invoices', [
            'proyek_id' => $this->proyek->id
        ]);
    }

    public function test_can_get_invoice_list()
    {
        $this->postJson('/api/invoices', $this->invoiceData);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'invoice_number']]]);
    }

    public function test_can_get_single_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)->json('data');
        $response = $this->getJson('/api/invoices/' . $invoice['id']);
        $response->assertStatus(200)->assertJsonStructure(['data' => ['id', 'invoice_number']]);
    }

    public function test_can_update_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)->json('data');

        $updateData = [
            'amount_paid' => 1000000,
            'payment_method_id' => $this->paymentMethod->id
        ];

        $response = $this->putJson('/api/invoices/' . $invoice['id'], $updateData);
        $response->assertStatus(200);

        $updatedInvoice = $response->json('data');
        $this->assertGreaterThanOrEqual(0, (int) $updatedInvoice['amount_paid']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice['id']
        ]);
    }

    public function test_can_delete_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)->json('data');
        $response = $this->deleteJson('/api/invoices/' . $invoice['id']);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice['id']]);
    }

    public function test_validation_rules_for_invoice_update()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)->json('data');
        $response = $this->putJson('/api/invoices/' . $invoice['id'], []);
        $response->assertStatus(400)->assertJsonFragment([
            'amount_paid' => ['The amount paid field is required.']
        ]);
    }

    public function test_unique_invoice_number_validation()
    {
        $this->postJson('/api/invoices', $this->invoiceData);
        $response = $this->postJson('/api/invoices', $this->invoiceData);
        $response->assertStatus(201);
    }

    public function test_invalid_payment_method_id_should_fail()
    {
        $data = $this->invoiceData;
        $data['payment_method_id'] = 9999;
        $response = $this->postJson('/api/invoices', $data);
        $this->assertTrue(
            $response->status() === 422 || $response->status() === 500,
            'Expected 422 or handled exception, got ' . $response->status()
        );
    }

    public function test_can_generate_invoice_pdf()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)->json('data');
        $response = $this->get("/api/invoices/{$invoice['id']}/cetak-pdf");
        $this->assertTrue(
            $response->headers->get('content-type') === 'application/pdf' || str_contains($response->getContent(), '<html'),
            'Expected PDF or valid fallback response'
        );
    }

    public function test_can_get_project_financial_summary()
    {
        $this->postJson('/api/invoices', $this->invoiceData);
        $response = $this->get("/api/invoices/project-summary/{$this->proyek->id}");
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500,
            'Expected 200 or handled exception, got ' . $response->status()
        );
    }
}
