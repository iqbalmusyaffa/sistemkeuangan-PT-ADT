<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Unit;
use App\Models\User;

class UnitTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $unitData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Sample unit data
        $this->unitData = [
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ];
    }

    /**
     * Test creating a new unit
     */
    public function test_can_create_unit()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/units', $this->unitData);

        $response->assertStatus(201)
            ->assertJson([
                'unit_name' => 'Test Unit',
                'unit_code' => 'TU001'
            ]);

        $this->assertDatabaseHas('units', [
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ]);
    }

    /**
     * Test retrieving unit list
     */
    public function test_can_get_unit_list()
    {
        $this->actingAs($this->user);

        // Create a test unit
        Unit::create($this->unitData);

        $response = $this->getJson('/api/units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'unit_name',
                    'unit_code',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test retrieving single unit
     */
    public function test_can_get_single_unit()
    {
        $this->actingAs($this->user);

        // Create a test unit
        $unit = Unit::create($this->unitData);

        $response = $this->getJson('/api/units/' . $unit->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $unit->id,
                'unit_name' => 'Test Unit',
                'unit_code' => 'TU001'
            ]);
    }

    /**
     * Test updating unit
     */
    public function test_can_update_unit()
    {
        $this->actingAs($this->user);

        // Create a test unit
        $unit = Unit::create($this->unitData);

        $updateData = [
            'unit_name' => 'Updated Unit',
            'unit_code' => 'TU002'
        ];

        $response = $this->putJson('/api/units/' . $unit->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $unit->id,
                'unit_name' => 'Updated Unit',
                'unit_code' => 'TU002'
            ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'unit_name' => 'Updated Unit',
            'unit_code' => 'TU002'
        ]);
    }

    /**
     * Test deleting unit
     */
    public function test_can_delete_unit()
    {
        $this->actingAs($this->user);

        // Create a test unit
        $unit = Unit::create($this->unitData);

        $response = $this->deleteJson('/api/units/' . $unit->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('units', [
            'id' => $unit->id
        ]);
    }

    /**
     * Test validation rules for unit creation
     */
    public function test_validation_rules_for_unit_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'unit_name' => '', // required
            'unit_code' => '' // required
        ];

        $response = $this->postJson('/api/units', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'unit_name',
                    'unit_code'
                ]
            ]);
    }

    /**
     * Test validation rules for unit update
     */
    public function test_validation_rules_for_unit_update()
    {
        $this->actingAs($this->user);

        // Create a test unit
        $unit = Unit::create($this->unitData);

        $invalidData = [
            'unit_name' => '', // required
            'unit_code' => '' // required
        ];

        $response = $this->putJson('/api/units/' . $unit->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'unit_name',
                    'unit_code'
                ]
            ]);
    }

    /**
     * Test unique unit_code validation
     */
    public function test_unique_unit_code_validation()
    {
        $this->actingAs($this->user);

        // Create a test unit
        Unit::create($this->unitData);

        // Try to create another unit with the same unit_code
        $response = $this->postJson('/api/units', $this->unitData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'unit_code'
                ]
            ]);
    }
} 