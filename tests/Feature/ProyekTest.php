<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use App\Models\Proyek;
use App\Models\User;
use App\Models\Expense;

class ProyekTest extends TestCase
{
    use DatabaseTransactions;

    protected $proyekData;

    protected function setUp(): void
    {
        parent::setUp();

        // Login user dummy
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Data proyek default
        $this->proyekData = [
            'nama_customer' => 'John Doe',
            'nama_proyek' => 'Gedung A',
            'nama_perusahaan' => 'PT ABC',
            'alamat' => 'Jl. Contoh No.123',
            'no_telp' => '08123456789',
            'email' => 'test@example.com',
            'lokasi' => 'Jakarta',
            'anggaran_kontrak' => 100000000,
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status_project' => 'Berjalan',
            'deskripsi' => 'Proyek pembangunan gedung',
        ];
    }

    public function test_can_create_project()
    {
        $response = $this->postJson('/api/proyeks', $this->proyekData);

        $response
            ->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Proyek berhasil dibuat',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'nama_customer',
                    'nama_proyek',
                    'total_expenses',
                    'budget_percentage'
                ]
            ]);
    }

    public function test_can_get_project_list()
    {
        Proyek::factory()->count(3)->create();

        $response = $this->getJson('/api/proyeks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'nama_customer',
                        'total_expenses',
                    ]
                ]
            ]);
    }

    public function test_can_get_single_project()
    {
        $proyek = Proyek::create($this->proyekData);

        $response = $this->getJson('/api/proyeks/' . $proyek->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'nama_proyek',
                    'total_expenses',
                    'budget_percentage'
                ]
            ]);
    }

    public function test_can_update_project()
    {
        $proyek = Proyek::create($this->proyekData);
        $updateData = $this->proyekData;
        $updateData['nama_proyek'] = 'Gedung B';

        $response = $this->putJson('/api/proyeks/' . $proyek->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Proyek berhasil diperbarui',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'nama_proyek',
                    'total_expenses',
                    'budget_percentage'
                ]
            ]);
    }

    public function test_can_delete_project()
    {
        $proyek = Proyek::create($this->proyekData);

        $response = $this->deleteJson('/api/proyeks/' . $proyek->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Proyek berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('proyeks', ['id' => $proyek->id]);
    }

    public function test_cannot_update_project_with_insufficient_budget()
    {
        $proyek = Proyek::create($this->proyekData);

        // Tambahkan pengeluaran melebihi anggaran
        Expense::create([
            'proyek_id' => $proyek->id,
            'amount' => 150000000,
            'status' => 'Lunas',
            'transaction_date' => now(),
            'kode_transaksi' => 'TRX-001',
        ]);

        $updateData = $this->proyekData;
        $updateData['anggaran_kontrak'] = 1000000;

        $response = $this->putJson('/api/proyeks/' . $proyek->id, $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Nilai kontrak lebih kecil dari total pengeluaran proyek. Silakan periksa kembali.',
            ]);
    }

    public function test_validation_rules_for_project_creation()
    {
        $response = $this->postJson('/api/proyeks', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_customer',
                    'nama_proyek',
                    'nama_perusahaan',
                    'alamat',
                    'no_telp',
                    'anggaran_kontrak',
                    'status_project',
                ]
            ]);
    }

    public function test_validation_rules_for_project_update()
    {
        $proyek = Proyek::create($this->proyekData);

        $invalidData = [
            'nama_customer' => '',
            'nama_proyek' => '',
            'nama_perusahaan' => '',
            'alamat' => '',
            'no_telp' => '',
            'email' => 'not-an-email',
            'anggaran_kontrak' => -50000,
            'status_project' => 'InvalidStatus',
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
                    'status_project',
                ]
            ]);
    }
}
