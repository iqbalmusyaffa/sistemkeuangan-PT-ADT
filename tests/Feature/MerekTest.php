<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Merek;
use App\Models\User;

class MerekTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $merekData;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Sample merek data
        $this->merekData = [
            'name' => 'Test Merek',
            'deskripsi' => 'Test Description'
        ];
    }

    /**
     * Test creating a new merek
     */
    public function test_can_create_merek()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/mereks', $this->merekData);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Test Merek',
                'deskripsi' => 'Test Description'
            ]);

        $this->assertDatabaseHas('mereks', [
            'name' => 'Test Merek',
            'deskripsi' => 'Test Description'
        ]);
    }

    /**
     * Test retrieving merek list
     */
    public function test_can_get_merek_list()
    {
        $this->actingAs($this->user);

        // Create a test merek
        Merek::create($this->merekData);

        $response = $this->getJson('/api/mereks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'deskripsi',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test retrieving single merek
     */
    public function test_can_get_single_merek()
    {
        $this->actingAs($this->user);

        // Create a test merek
        $merek = Merek::create($this->merekData);

        $response = $this->getJson('/api/mereks/' . $merek->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $merek->id,
                'name' => 'Test Merek',
                'deskripsi' => 'Test Description'
            ]);
    }

    /**
     * Test updating merek
     */
    public function test_can_update_merek()
    {
        $this->actingAs($this->user);

        // Create a test merek
        $merek = Merek::create($this->merekData);

        $updateData = [
            'name' => 'Updated Merek',
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/mereks/' . $merek->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $merek->id,
                'name' => 'Updated Merek',
                'deskripsi' => 'Updated Description'
            ]);

        $this->assertDatabaseHas('mereks', [
            'id' => $merek->id,
            'name' => 'Updated Merek',
            'deskripsi' => 'Updated Description'
        ]);
    }

    /**
     * Test deleting merek
     */
    public function test_can_delete_merek()
    {
        $this->actingAs($this->user);

        // Create a test merek
        $merek = Merek::create($this->merekData);

        $response = $this->deleteJson('/api/mereks/' . $merek->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('mereks', [
            'id' => $merek->id
        ]);
    }

    /**
     * Test validation rules for merek creation
     */
    public function test_validation_rules_for_merek_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'name' => '', // required
            'deskripsi' => '' // optional
        ];

        $response = $this->postJson('/api/mereks', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name'
                ]
            ]);
    }

    /**
     * Test validation rules for merek update
     */
    public function test_validation_rules_for_merek_update()
    {
        $this->actingAs($this->user);

        // Create a test merek
        $merek = Merek::create($this->merekData);

        $invalidData = [
            'name' => '', // required
            'deskripsi' => '' // optional
        ];

        $response = $this->putJson('/api/mereks/' . $merek->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name'
                ]
            ]);
    }

}
