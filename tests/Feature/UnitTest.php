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

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->unitData = [
            'unit_name' => 'Test Unit',
            'unit_code' => 'TU001'
        ];
    }

    public function test_can_create_unit()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/units', $this->unitData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'unit_name' => 'Test Unit',
                    'unit_code' => 'TU001'
                ]
            ]);

        $this->assertDatabaseHas('units', $this->unitData);
    }

    public function test_can_get_unit_list()
    {
        $this->actingAs($this->user);

        Unit::create($this->unitData);

        $response = $this->getJson('/api/units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'unit_name',
                        'unit_code',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_can_get_single_unit()
    {
        $this->actingAs($this->user);

        $unit = Unit::create($this->unitData);

        $response = $this->getJson('/api/units/' . $unit->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $unit->id,
                    'unit_name' => 'Test Unit',
                    'unit_code' => 'TU001'
                ]
            ]);
    }

    public function test_can_update_unit()
    {
        $this->actingAs($this->user);

        $unit = Unit::create($this->unitData);

        $updateData = [
            'unit_name' => 'Updated Unit',
            'unit_code' => 'TU002'
        ];

        $response = $this->putJson('/api/units/' . $unit->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $unit->id,
                    'unit_name' => 'Updated Unit',
                    'unit_code' => 'TU002'
                ]
            ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'unit_name' => 'Updated Unit',
            'unit_code' => 'TU002'
        ]);
    }

    public function test_can_delete_unit()
    {
        $this->actingAs($this->user);

        $unit = Unit::create($this->unitData);

        $response = $this->deleteJson('/api/units/' . $unit->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_validation_rules_for_unit_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'unit_name' => '',
            'unit_code' => ''
        ];

        $response = $this->postJson('/api/units', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit_name', 'unit_code']);
    }

    public function test_validation_rules_for_unit_update()
    {
        $this->actingAs($this->user);

        $unit = Unit::create($this->unitData);

        $invalidData = [
            'unit_name' => '',
            'unit_code' => ''
        ];

        $response = $this->putJson('/api/units/' . $unit->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit_name', 'unit_code']);
    }

    public function test_unique_unit_code_validation()
    {
        $this->actingAs($this->user);

        Unit::create($this->unitData);

        $response = $this->postJson('/api/units', $this->unitData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit_code']);
    }
}
