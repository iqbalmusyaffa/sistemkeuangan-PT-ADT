<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\PurchaseMaterial;
use App\Models\User;
use App\Models\Unit;
use App\Models\Merek;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Kategori;

class PurchasematerialControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $unit;
    protected $merek;
    protected $proyek;
    protected $invoice;
    protected $category;
    protected $purchasematerialData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Create test proyek
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

        // Create test invoice
        $this->invoice = Invoice::create([
            'proyek_id' => $this->proyek->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'total_amount' => 1000000,
            'amount_paid' => 0,
            'status' => 'unpaid'
        ]);

        // Create test unit
        $this->unit = Unit::create([
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ]);

        // Create test merek
        $this->merek = Merek::create([
            'name' => 'Test Merek',
            'deskripsi' => 'Test Description'
        ]);

        // Create test category
        $this->category = Kategori::create([
            'nama_kategori' => 'Test Category',
            'deskripsi' => 'Test Description'
        ]);

        // Sample purchasematerial data
        $this->purchasematerialData = [
            'item' => 'Test Material',
            'unit_id' => $this->unit->id,
            'merek_id' => $this->merek->id,
            'category_id' => $this->category->id,
            'qty' => 10,
            'harga' => 100000,
            'type' => 'material',
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'deskripsi' => 'Test Description'
        ];
    }

    /**
     * Test creating a new purchasematerial
     */
    public function test_can_create_purchasematerial()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/purchasematerials', $this->purchasematerialData);

        $response->assertStatus(201)
            ->assertJson([
                'item' => 'Test Material',
                'unit_id' => $this->unit->id,
                'merek_id' => $this->merek->id,
                'qty' => 10,
                'harga' => 100000,
                'type' => 'material',
                'proyek_id' => $this->proyek->id,
                'invoice_id' => $this->invoice->id,
                'deskripsi' => 'Test Description'
            ]);

        $this->assertDatabaseHas('purchase_materials', [
            'item' => 'Test Material',
            'unit_id' => $this->unit->id,
            'merek_id' => $this->merek->id,
            'qty' => 10,
            'harga' => 100000,
            'type' => 'material',
            'proyek_id' => $this->proyek->id,
            'invoice_id' => $this->invoice->id,
            'deskripsi' => 'Test Description'
        ]);
    }

    /**
     * Test retrieving purchasematerial list
     */
    public function test_can_get_purchasematerial_list()
    {
        $this->actingAs($this->user);

        // Create a test purchasematerial
        PurchaseMaterial::create($this->purchasematerialData);

        $response = $this->getJson('/api/purchasematerials?proyek_id=' . $this->proyek->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'proyek',
                'purchases' => [
                    '*' => [
                        'id',
                        'item',
                        'unit_id',
                        'merek_id',
                        'category_id',
                        'qty',
                        'harga',
                        'total_harga',
                        'type',
                        'proyek_id',
                        'invoice_id',
                        'deskripsi',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test retrieving single purchasematerial
     */
    public function test_can_get_single_purchasematerial()
    {
        $this->actingAs($this->user);

        // Create a test purchasematerial
        $purchasematerial = PurchaseMaterial::create($this->purchasematerialData);

        $response = $this->getJson('/api/purchasematerials/' . $purchasematerial->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $purchasematerial->id,
                'item' => 'Test Material',
                'unit_id' => $this->unit->id,
                'merek_id' => $this->merek->id,
                'qty' => 10,
                'harga' => 100000,
                'total_harga' => 1000000,
                'type' => 'material',
                'proyek_id' => $this->proyek->id,
                'invoice_id' => $this->invoice->id,
                'deskripsi' => 'Test Description'
            ]);
    }

    /**
     * Test updating purchasematerial
     */
    public function test_can_update_purchasematerial()
    {
        $this->actingAs($this->user);

        // Create a test purchasematerial
        $purchasematerial = PurchaseMaterial::create($this->purchasematerialData);

        $updateData = [
            'item' => 'Updated Material',
            'qty' => 20,
            'harga' => 150000,
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/purchasematerials/' . $purchasematerial->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $purchasematerial->id,
                'item' => 'Updated Material',
                'qty' => 20,
                'harga' => 150000,
                'total_harga' => 3000000,
                'deskripsi' => 'Updated Description'
            ]);

        $this->assertDatabaseHas('purchase_materials', [
            'id' => $purchasematerial->id,
            'item' => 'Updated Material',
            'qty' => 20,
            'harga' => 150000,
            'total_harga' => 3000000,
            'deskripsi' => 'Updated Description'
        ]);
    }

    /**
     * Test deleting purchasematerial
     */
    public function test_can_delete_purchasematerial()
    {
        $this->actingAs($this->user);

        // Create a test purchasematerial
        $purchasematerial = PurchaseMaterial::create($this->purchasematerialData);

        $response = $this->deleteJson('/api/purchasematerials/' . $purchasematerial->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('purchase_materials', [
            'id' => $purchasematerial->id
        ]);
    }

    /**
     * Test validation rules for purchasematerial creation
     */
    public function test_validation_rules_for_purchasematerial_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'item' => '', // required
            'unit_id' => '', // required
            'merek_id' => '', // required
            'category_id' => '', // required
            'qty' => '', // required
            'harga' => '', // required
            'type' => '', // required
            'proyek_id' => '', // required
            'invoice_id' => '', // required
            'deskripsi' => '' // optional
        ];

        $response = $this->postJson('/api/purchasematerials', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'item',
                    'unit_id',
                    'merek_id',
                    'category_id',
                    'qty',
                    'harga',
                    'type',
                    'proyek_id',
                    'invoice_id'
                ]
            ]);
    }

    /**
     * Test validation rules for purchasematerial update
     */
    public function test_validation_rules_for_purchasematerial_update()
    {
        $this->actingAs($this->user);

        // Create a test purchasematerial
        $purchasematerial = PurchaseMaterial::create($this->purchasematerialData);

        $invalidData = [
            'item' => '', // required
            'unit_id' => '', // required
            'merek_id' => '', // required
            'category_id' => '', // required
            'qty' => 'not_a_number', // invalid qty
            'harga' => 'not_a_number', // invalid harga
            'type' => '', // required
            'proyek_id' => '', // required
            'invoice_id' => '' // required
        ];

        $response = $this->putJson('/api/purchasematerials/' . $purchasematerial->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'item',
                    'unit_id',
                    'merek_id',
                    'category_id',
                    'qty',
                    'harga',
                    'type',
                    'proyek_id',
                    'invoice_id'
                ]
            ]);
    }
} 