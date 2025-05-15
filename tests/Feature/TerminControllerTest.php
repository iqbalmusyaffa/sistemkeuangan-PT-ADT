<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Termin;
use App\Models\User;
use App\Models\Invoice;

class TerminControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $invoice;
    protected $terminData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Create test invoice
        $this->invoice = Invoice::create([
            'customer_id' => 1,
            'invoice_number' => 'INV-001',
            'invoice_date' => '2024-03-20',
            'due_date' => '2024-04-20',
            'total_amount' => 1000000,
            'status' => 'pending'
        ]);

        // Sample termin data
        $this->terminData = [
            'invoice_id' => $this->invoice->id,
            'termin_number' => 1,
            'due_date' => '2024-04-20',
            'amount' => 500000,
            'status' => 'pending',
            'description' => 'Test Termin'
        ];
    }

    /**
     * Test creating a new termin
     */
    public function test_can_create_termin()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/termins', $this->terminData);

        $response->assertStatus(201)
            ->assertJson([
                'invoice_id' => $this->invoice->id,
                'termin_number' => 1,
                'amount' => 500000,
                'status' => 'pending',
                'description' => 'Test Termin'
            ]);

        $this->assertDatabaseHas('termins', [
            'invoice_id' => $this->invoice->id,
            'termin_number' => 1,
            'amount' => 500000,
            'status' => 'pending',
            'description' => 'Test Termin'
        ]);
    }

    /**
     * Test retrieving termin list
     */
    public function test_can_get_termin_list()
    {
        $this->actingAs($this->user);

        // Create a test termin
        Termin::create($this->terminData);

        $response = $this->getJson('/api/termins');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'invoice_id',
                    'termin_number',
                    'due_date',
                    'amount',
                    'status',
                    'description',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test retrieving single termin
     */
    public function test_can_get_single_termin()
    {
        $this->actingAs($this->user);

        // Create a test termin
        $termin = Termin::create($this->terminData);

        $response = $this->getJson('/api/termins/' . $termin->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $termin->id,
                'invoice_id' => $this->invoice->id,
                'termin_number' => 1,
                'amount' => 500000,
                'status' => 'pending',
                'description' => 'Test Termin'
            ]);
    }

    /**
     * Test updating termin
     */
    public function test_can_update_termin()
    {
        $this->actingAs($this->user);

        // Create a test termin
        $termin = Termin::create($this->terminData);

        $updateData = [
            'amount' => 600000,
            'status' => 'paid',
            'description' => 'Updated Termin'
        ];

        $response = $this->putJson('/api/termins/' . $termin->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $termin->id,
                'amount' => 600000,
                'status' => 'paid',
                'description' => 'Updated Termin'
            ]);

        $this->assertDatabaseHas('termins', [
            'id' => $termin->id,
            'amount' => 600000,
            'status' => 'paid',
            'description' => 'Updated Termin'
        ]);
    }

    /**
     * Test deleting termin
     */
    public function test_can_delete_termin()
    {
        $this->actingAs($this->user);

        // Create a test termin
        $termin = Termin::create($this->terminData);

        $response = $this->deleteJson('/api/termins/' . $termin->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('termins', [
            'id' => $termin->id
        ]);
    }

    /**
     * Test validation rules for termin creation
     */
    public function test_validation_rules_for_termin_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'invoice_id' => '', // required
            'termin_number' => '', // required
            'due_date' => '', // required
            'amount' => '', // required
            'status' => '', // required
            'description' => '' // optional
        ];

        $response = $this->postJson('/api/termins', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'invoice_id',
                    'termin_number',
                    'due_date',
                    'amount',
                    'status'
                ]
            ]);
    }

    /**
     * Test validation rules for termin update
     */
    public function test_validation_rules_for_termin_update()
    {
        $this->actingAs($this->user);

        // Create a test termin
        $termin = Termin::create($this->terminData);

        $invalidData = [
            'amount' => 'not_a_number', // invalid amount
            'status' => 'invalid_status', // invalid status
            'description' => '' // optional
        ];

        $response = $this->putJson('/api/termins/' . $termin->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'amount',
                    'status'
                ]
            ]);
    }

    /**
     * Test unique termin number validation for same invoice
     */
    public function test_unique_termin_number_validation()
    {
        $this->actingAs($this->user);

        // Create a test termin
        Termin::create($this->terminData);

        // Try to create another termin with the same termin number for the same invoice
        $response = $this->postJson('/api/termins', $this->terminData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'termin_number'
                ]
            ]);
    }
} 