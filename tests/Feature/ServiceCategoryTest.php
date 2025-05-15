<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\ServiceCategory;
use App\Models\Unit;
use App\Models\User;

class ServiceCategoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $unit;
    protected $serviceCategoryData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Create a test unit
        $this->unit = Unit::create([
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ]);

        // Sample service category data
        $this->serviceCategoryData = [
            'nama_kategori' => 'Test Service Category',
            'jenis' => 'pengeluaran',
            'harga' => 100000,
            'unit_id' => $this->unit->id,
            'deskripsi' => 'Test Description'
        ];
    }

    /**
     * Test creating a new service category
     */
    public function test_can_create_service_category()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/service-categories', $this->serviceCategoryData);

        $response->assertStatus(201)
            ->assertJson([
                'nama_kategori' => 'Test Service Category',
                'jenis' => 'pengeluaran',
                'harga' => 100000,
                'unit_id' => $this->unit->id
            ]);

        $this->assertDatabaseHas('service_categories', [
            'nama_kategori' => 'Test Service Category',
            'jenis' => 'pengeluaran',
            'harga' => 100000,
            'unit_id' => $this->unit->id
        ]);
    }

    /**
     * Test retrieving service category list
     */
    public function test_can_get_service_category_list()
    {
        $this->actingAs($this->user);

        // Create a test service category
        ServiceCategory::create($this->serviceCategoryData);

        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'nama_kategori',
                    'jenis',
                    'harga',
                    'unit_id',
                    'deskripsi',
                    'unit' => [
                        'id',
                        'unit_name',
                        'unit_code'
                    ]
                ]
            ]);
    }

    /**
     * Test retrieving service category list with unit filter
     */
    public function test_can_get_service_category_list_with_unit_filter()
    {
        $this->actingAs($this->user);

        // Create a test service category
        ServiceCategory::create($this->serviceCategoryData);

        $response = $this->getJson('/api/service-categories?unit_id=' . $this->unit->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'nama_kategori',
                    'jenis',
                    'harga',
                    'unit_id',
                    'deskripsi',
                    'unit' => [
                        'id',
                        'unit_name',
                        'unit_code'
                    ]
                ]
            ]);
    }

    /**
     * Test retrieving single service category
     */
    public function test_can_get_single_service_category()
    {
        $this->actingAs($this->user);

        // Create a test service category
        $serviceCategory = ServiceCategory::create($this->serviceCategoryData);

        $response = $this->getJson('/api/service-categories/' . $serviceCategory->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $serviceCategory->id,
                'nama_kategori' => 'Test Service Category',
                'jenis' => 'pengeluaran',
                'harga' => 100000,
                'unit_id' => $this->unit->id
            ])
            ->assertJsonStructure([
                'id',
                'nama_kategori',
                'jenis',
                'harga',
                'unit_id',
                'deskripsi',
                'unit' => [
                    'id',
                    'unit_name',
                    'unit_code'
                ]
            ]);
    }

    /**
     * Test updating service category
     */
    public function test_can_update_service_category()
    {
        $this->actingAs($this->user);

        // Create a test service category
        $serviceCategory = ServiceCategory::create($this->serviceCategoryData);

        $updateData = [
            'nama_kategori' => 'Updated Service Category',
            'jenis' => 'pengeluaran',
            'harga' => 200000,
            'unit_id' => $this->unit->id,
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/service-categories/' . $serviceCategory->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $serviceCategory->id,
                'nama_kategori' => 'Updated Service Category',
                'jenis' => 'pengeluaran',
                'harga' => 200000,
                'unit_id' => $this->unit->id
            ]);

        $this->assertDatabaseHas('service_categories', [
            'id' => $serviceCategory->id,
            'nama_kategori' => 'Updated Service Category',
            'jenis' => 'pengeluaran',
            'harga' => 200000,
            'unit_id' => $this->unit->id
        ]);
    }

    /**
     * Test deleting service category
     */
    public function test_can_delete_service_category()
    {
        $this->actingAs($this->user);

        // Create a test service category
        $serviceCategory = ServiceCategory::create($this->serviceCategoryData);

        $response = $this->deleteJson('/api/service-categories/' . $serviceCategory->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('service_categories', [
            'id' => $serviceCategory->id
        ]);
    }

    /**
     * Test validation rules for service category creation
     */
    public function test_validation_rules_for_service_category_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'nama_kategori' => '', // required
            'jenis' => '', // required
            'harga' => -1000, // must be positive
            'unit_id' => 999999, // must exist
            'deskripsi' => '' // optional
        ];

        $response = $this->postJson('/api/service-categories', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_kategori',
                    'jenis',
                    'harga',
                    'unit_id'
                ]
            ]);
    }

    /**
     * Test validation rules for service category update
     */
    public function test_validation_rules_for_service_category_update()
    {
        $this->actingAs($this->user);

        // Create a test service category
        $serviceCategory = ServiceCategory::create($this->serviceCategoryData);

        $invalidData = [
            'nama_kategori' => '', // required
            'jenis' => '', // required
            'harga' => -1000, // must be positive
            'unit_id' => 999999, // must exist
            'deskripsi' => '' // optional
        ];

        $response = $this->putJson('/api/service-categories/' . $serviceCategory->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_kategori',
                    'jenis',
                    'harga',
                    'unit_id'
                ]
            ]);
    }
} 