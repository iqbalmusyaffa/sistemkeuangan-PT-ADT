<?php

namespace Tests\Feature;

use App\Models\Proyek;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ProyekControllerTest extends TestCase
{
    use DatabaseTransactions ;

    protected function setUp(): void
    {
        parent::setUp();
        // Create and authenticate a user for all tests
        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        Sanctum::actingAs($user);
    }

    public function test_can_create_proyek()
    {
        $data = [
            'nama_proyek' => 'Pembangunan Gudang',
            'nama_customer' => 'Budi',
            'nama_perusahaan' => 'PT. Maju Jaya',
            'alamat' => 'Jl. Contoh No. 123',
            'no_telp' => '081234567890',
            'email' => 'budi@example.com',
            'tanggal_mulai' => '2024-03-20',
            'tanggal_selesai' => '2024-06-20',
            'anggaran_kontrak' => 500000000,
            'status_project' => 'Berjalan',
            'deskripsi' => 'Pembangunan gudang baru'
        ];

        $response = $this->postJson('/api/proyeks', $data);

        $response->assertStatus(201)
                ->assertJsonFragment(['nama_proyek' => 'Pembangunan Gudang']);

        $this->assertDatabaseHas('proyeks', ['nama_customer' => 'Budi']);
    }

    public function test_can_get_all_proyeks()
    {
        Proyek::factory()->count(3)->create();

        $response = $this->getJson('/api/proyeks');

        $response->assertStatus(200)
                ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_show_single_proyek()
    {
        $proyek = Proyek::factory()->create();

        $response = $this->getJson("/api/proyeks/{$proyek->id}");

        $response->assertStatus(200)
                ->assertJson(['id' => $proyek->id]);
    }

    public function test_can_update_proyek()
    {
        $proyek = Proyek::factory()->create();

        $updateData = [
            'nama_proyek' => 'Renovasi Kantor',
            'nama_customer' => 'Andi',
            'nama_perusahaan' => 'PT. Karya Abadi',
            'alamat' => 'Jl. Baru No. 456',
            'no_telp' => '089876543210',
            'email' => 'andi@example.com',
            'tanggal_mulai' => '2024-03-21',
            'tanggal_selesai' => '2024-07-21',
            'anggaran_kontrak' => 750000000,
            'status_project' => 'Berjalan',
            'deskripsi' => 'Renovasi kantor baru'
        ];

        $response = $this->putJson("/api/proyeks/{$proyek->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment(['nama_proyek' => 'Renovasi Kantor']);

        $this->assertDatabaseHas('proyeks', ['nama_customer' => 'Andi']);
    }

    public function test_can_delete_proyek()
    {
        $proyek = Proyek::factory()->create();

        $response = $this->deleteJson("/api/proyeks/{$proyek->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Proyek berhasil dihapus']);

        $this->assertDatabaseMissing('proyeks', ['id' => $proyek->id]);
    }
}
