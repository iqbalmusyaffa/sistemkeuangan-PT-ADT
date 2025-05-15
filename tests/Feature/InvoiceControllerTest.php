<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proyek;
use App\Models\Unit;
use App\Models\Merek;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

class InvoiceControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $proyek;
    protected $unit;
    protected $merek;
    protected $serviceCategory;
    protected $invoiceData;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user using factory
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        Sanctum::actingAs($this->user);

        // Create test proyek using factory
        $this->proyek = Proyek::factory()->create([
            'nama_customer' => 'Test Customer',
            'nama_proyek' => 'Test Project',
            'nama_perusahaan' => 'Test Company',
            'alamat' => 'Test Address',
            'no_telp' => '08123456789',
            'email' => 'test@example.com',
            'lokasi' => 'Test Location',
            'anggaran_kontrak' => 100000000,
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addMonths(3),
            'status_project' => 'Berjalan',
            'deskripsi' => 'Test Description'
        ]);

        // Create test unit using factory
        $this->unit = Unit::factory()->create([
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ]);

        // Create test merek using factory
        $this->merek = Merek::factory()->create([
            'name' => 'Test Merek',
            'deskripsi' => 'Test Description'
        ]);

        // Create test service category using factory
        $this->serviceCategory = ServiceCategory::factory()->create([
            'nama_kategori' => 'Test Service',
            'jenis' => 'pengeluaran',
            'harga' => 1000000,
            'unit_id' => $this->unit->id,
            'deskripsi' => 'Test Description'
        ]);

        // Sample invoice data
        $this->invoiceData = [
            'proyek_id' => $this->proyek->id,
            'invoice_date' => now()->format('Y-m-d'),
            'purchase_materials' => [
                [
                    'item' => 'Test Item',
                    'qty' => 1,
                    'harga' => 1000000,
                    'type' => 'material',
                    'unit_id' => $this->unit->id,
                    'merek_id' => $this->merek->id,
                    'deskripsi' => 'Test Description'
                ]
            ],
            'use_ppn' => true,
            'use_pph_non_final' => true,
            'use_pph_final' => true
        ];
    }

    /**
     * Test creating a new invoice
     */
    public function test_can_create_invoice()
    {
        $response = $this->postJson('/api/invoices', $this->invoiceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'invoice_date',
                    'total_amount',
                    'amount_paid',
                    'status',
                    'notes',
                    'proyek',
                    'purchase_materials',
                    'termins',
                    'expenses',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('invoices', [
            'proyek_id' => $this->proyek->id
        ]);
    }

    /**
     * Test retrieving invoice list
     */
    public function test_can_get_invoice_list()
    {
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'invoice_date',
                        'total_amount',
                        'amount_paid',
                        'status',
                        'notes',
                        'proyek',
                        'purchase_materials',
                        'termins',
                        'expenses',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test retrieving single invoice
     */
    public function test_can_get_single_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)
            ->json('data');

        $response = $this->getJson('/api/invoices/' . $invoice['id']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'invoice_date',
                    'total_amount',
                    'amount_paid',
                    'status',
                    'notes',
                    'proyek',
                    'purchase_materials',
                    'termins',
                    'expenses',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test updating invoice
     */
    public function test_can_update_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)
            ->json('data');

        $updateData = [
            'status' => 'paid',
            'amount_paid' => 1000000
        ];

        $response = $this->putJson('/api/invoices/' . $invoice['id'], $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'paid'
                ]
            ]);
    }

    /**
     * Test deleting invoice
     */
    public function test_can_delete_invoice()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)
            ->json('data');

        $response = $this->deleteJson('/api/invoices/' . $invoice['id']);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice['id']
        ]);
    }

    /**
     * Test validation rules for invoice creation
     */
    public function test_validation_rules_for_invoice_creation()
    {
        $response = $this->postJson('/api/invoices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'proyek_id',
                'invoice_date',
                'purchase_materials'
            ]);
    }

    /**
     * Test validation rules for invoice update
     */
    public function test_validation_rules_for_invoice_update()
    {
        $invoice = $this->postJson('/api/invoices', $this->invoiceData)
            ->json('data');

        $response = $this->putJson('/api/invoices/' . $invoice['id'], []);
        
        // Debug the response
        dump($response->json());

        $response->assertStatus(400)
            ->assertJson([
                'status' => [
                    'The status field is required.'
                ],
                'amount_paid' => [
                    'The amount paid field is required.'
                ]
            ]);
    }

    /**
     * Test unique invoice number validation
     */
    public function test_unique_invoice_number_validation()
    {
        // Create first invoice
        $this->postJson('/api/invoices', $this->invoiceData);

        // Try to create second invoice with same data
        $response = $this->postJson('/api/invoices', $this->invoiceData);

        $response->assertStatus(201); // Should still succeed as invoice numbers are auto-generated with timestamps
    }
}
