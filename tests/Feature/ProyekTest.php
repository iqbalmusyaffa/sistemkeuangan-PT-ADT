<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Proyek;
use App\Models\User;
use App\Models\Expense;
use App\Models\Kategori;
use Carbon\Carbon;

class ProyekTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $proyekData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Sample project data
        $this->proyekData = [
            'nama_customer' => 'Test Customer',
            'nama_proyek' => 'Test Project',
            'nama_perusahaan' => 'Test Company',
            'alamat' => 'Test Address',
            'no_telp' => '081234567890',
            'email' => 'test@example.com',
            'lokasi' => 'Test Location',
            'anggaran_kontrak' => 100000000,
            'tanggal_mulai' => Carbon::now()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addMonths(3)->format('Y-m-d'),
            'status_project' => 'Berjalan',
            'deskripsi' => 'Test Description'
        ];
    }

    /**
     * Test creating a new project
     */
    public function test_can_create_project()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/proyeks', $this->proyekData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Proyek berhasil dibuat'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'alamat',
                    'no_telp',
                    'email',
                    'lokasi',
                    'anggaran_kontrak',
                    'tanggal_mulai',
                    'tanggal_selesai',
                    'status_project',
                    'deskripsi',
                    'total_expenses'
                ]
            ]);

        $this->assertDatabaseHas('proyeks', [
            'nama_customer' => 'Test Customer',
            'nama_proyek' => 'Test Project',
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test retrieving project list
     */
    public function test_can_get_project_list()
    {
        $this->actingAs($this->user);

        // Create a test project
        Proyek::create($this->proyekData);

        $response = $this->getJson('/api/proyeks');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'nama_customer',
                        'nama_proyek',
                        'nama_perusahaan',
                        'total_expenses'
                    ]
                ]
            ]);
    }

    /**
     * Test retrieving single project
     */
    public function test_can_get_single_project()
    {
        $this->actingAs($this->user);

        // Create a test project
        $proyek = Proyek::create($this->proyekData);

        $response = $this->getJson('/api/proyeks/' . $proyek->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'alamat',
                    'no_telp',
                    'email',
                    'lokasi',
                    'anggaran_kontrak',
                    'tanggal_mulai',
                    'tanggal_selesai',
                    'status_project',
                    'deskripsi',
                    'total_expenses',
                    'budget_percentage'
                ]
            ]);
    }

    /**
     * Test updating project
     */
    public function test_can_update_project()
    {
        $this->actingAs($this->user);

        // Create a test project
        $proyek = Proyek::create($this->proyekData);

        $updateData = [
            'nama_customer' => 'Updated Customer',
            'nama_proyek' => 'Updated Project',
            'nama_perusahaan' => 'Updated Company',
            'alamat' => 'Updated Address',
            'no_telp' => '081234567890',
            'email' => 'updated@example.com',
            'lokasi' => 'Updated Location',
            'anggaran_kontrak' => 150000000,
            'tanggal_mulai' => Carbon::now()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'status_project' => 'Berjalan',
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/proyeks/' . $proyek->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Proyek berhasil diperbarui'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'anggaran_kontrak',
                    'total_expenses',
                    'budget_percentage'
                ]
            ]);

        $this->assertDatabaseHas('proyeks', [
            'id' => $proyek->id,
            'nama_customer' => 'Updated Customer',
            'nama_proyek' => 'Updated Project'
        ]);
    }

    /**
     * Test deleting project
     */
    public function test_can_delete_project()
    {
        $this->actingAs($this->user);

        // Create a test project
        $proyek = Proyek::create($this->proyekData);

        $response = $this->deleteJson('/api/proyeks/' . $proyek->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('proyeks', [
            'id' => $proyek->id
        ]);
    }

    /**
     * Test project budget validation
     */
    public function test_cannot_update_project_with_insufficient_budget()
    {
        $this->actingAs($this->user);

        // Create a test project
        $proyek = Proyek::create($this->proyekData);

        // Create a test category
        $category = Kategori::create([
            'nama_kategori' => 'Test Category',
            'jenis' => 'Pengeluaran'
        ]);

        // Create an expense for the project
        Expense::create([
            'user_id' => $this->user->id,
            'proyek_id' => $proyek->id,
            'category_id' => $category->id,
            'amount' => 50000000,
            'description' => 'Test Expense',
            'transaction_date' => Carbon::now(),
            'status' => 'Lunas',
            'payment_method' => 'Cash',
            'prepared_fund' => 50000000
        ]);

        // Try to update project with budget less than total expenses
        $updateData = $this->proyekData;
        $updateData['anggaran_kontrak'] = 40000000; // Less than total expenses

        $response = $this->putJson('/api/proyeks/' . $proyek->id, $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Nilai kontrak lebih kecil dari total pengeluaran proyek. Silakan periksa kembali.'
            ]);
    }

    /**
     * Test validation rules for project creation
     */
    public function test_validation_rules_for_project_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'nama_customer' => '', // required
            'nama_proyek' => '', // required
            'nama_perusahaan' => '', // required
            'alamat' => '', // required
            'no_telp' => '', // required
            'email' => 'invalid-email', // must be valid email
            'anggaran_kontrak' => -1000, // must be positive
            'status_project' => 'Invalid' // must be one of: Berjalan,Selesai,Batal
        ];

        $response = $this->postJson('/api/proyeks', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'alamat',
                    'no_telp',
                    'email',
                    'anggaran_kontrak',
                    'status_project'
                ]
            ]);
    }

    /**
     * Test validation rules for project update
     */
    public function test_validation_rules_for_project_update()
    {
        $this->actingAs($this->user);

        // Create a test project
        $proyek = Proyek::create($this->proyekData);

        $invalidData = [
            'nama_customer' => '', // required
            'nama_proyek' => '', // required
            'nama_perusahaan' => '', // required
            'alamat' => '', // required
            'no_telp' => '', // required
            'email' => 'invalid-email', // must be valid email
            'anggaran_kontrak' => -1000, // must be positive
            'status_project' => 'Invalid' // must be one of: Berjalan,Selesai,Batal
        ];

        $response = $this->putJson('/api/proyeks/' . $proyek->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'alamat',
                    'no_telp',
                    'email',
                    'anggaran_kontrak',
                    'status_project'
                ]
            ]);
    }
} 