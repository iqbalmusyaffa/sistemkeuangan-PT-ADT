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

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->proyek = Proyek::factory()->create(['anggaran_kontrak' => 100000000]);

        $this->invoice = Invoice::create([
            'proyek_id' => $this->proyek->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'total_amount' => 1000000,
            'amount_paid' => 0,
            'status' => 'unpaid'
        ]);

        $this->unit = Unit::create(['unit_name' => 'Test Unit', 'unit_code' => 'TU001']);
        $this->merek = Merek::create(['name' => 'Test Merek', 'deskripsi' => 'Test']);
        $this->category = Kategori::create(['nama_kategori' => 'Test Category', 'jenis' => 'pengeluaran', 'deskripsi' => 'Test']);

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

    public function test_can_create_purchasematerial()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/purchasematerials', $this->purchasematerialData);

       $response->assertStatus(201)
    ->assertJsonFragment([
        'item' => 'Test Material',
        'qty' => 10,
        'harga' => "100000.00", // perhatikan format string karena dikonversi oleh Eloquent
        'type' => 'material',
        'proyek_id' => $this->proyek->id,
        'invoice_id' => $this->invoice->id,
        'deskripsi' => 'Test Description'
    ]);

        $this->assertDatabaseHas('purchase_materials', [
            'item' => 'Test Material',
            'qty' => 10,
            'harga' => 100000
        ]);
    }

    public function test_can_get_single_purchasematerial()
    {
        $this->actingAs($this->user);

        $material = PurchaseMaterial::create($this->purchasematerialData);

        $response = $this->getJson('/api/purchasematerials/' . $material->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $material->id,
                    'item' => 'Test Material',
                    'qty' => 10,
                    'harga' => 100000,
                    'total_harga' => 1000000
                ]
            ]);
    }

    public function test_can_update_purchasematerial()
    {
        $this->actingAs($this->user);

        $material = PurchaseMaterial::create($this->purchasematerialData);

        $update = [
    'item' => 'Updated Item',
    'type' => 'material',
    'qty' => 5,
    'harga' => 200000,
    'unit_id' => $this->unit->id,
    'category_id' => $this->category->id,
    'merek_id' => $this->merek->id,
    'invoice_id' => $this->invoice->id,
    'proyek_id' => $this->proyek->id,
    'deskripsi' => 'Updated'
];
        $response = $this->putJson('/api/purchasematerials/' . $material->id, $update);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'item' => 'Updated Item',
                    'qty' => 5,
                    'harga' => 200000,
                    'total_harga' => 1000000
                ]
            ]);

        $this->assertDatabaseHas('purchase_materials', [
            'id' => $material->id,
            'item' => 'Updated Item',
            'harga' => 200000
        ]);
    }

    public function test_can_delete_purchasematerial()
    {
        $this->actingAs($this->user);

        $material = PurchaseMaterial::create($this->purchasematerialData);

        $response = $this->deleteJson('/api/purchasematerials/' . $material->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseMissing('purchase_materials', [
            'id' => $material->id
        ]);
    }

    public function test_validation_error_on_create()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/purchasematerials', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
    }

    public function test_validation_error_on_update()
    {
        $this->actingAs($this->user);

        $material = PurchaseMaterial::create($this->purchasematerialData);

        $invalid = [
            'item' => '',
            'type' => '',
            'qty' => 'invalid',
            'harga' => 'NaN',
            'unit_id' => '',
        ];

        $response = $this->putJson('/api/purchasematerials/' . $material->id, $invalid);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
    }
}

