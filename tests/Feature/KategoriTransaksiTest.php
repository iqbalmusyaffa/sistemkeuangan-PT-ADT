<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Kategori;
use App\Models\User;

class KategoriTransaksiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $kategoriData;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Sample kategori data
        $this->kategoriData = [
            'nama_kategori' => 'Test Kategori',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Test Description'
        ];
    }

    public function test_can_create_kategori_transaksi()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/kategori', $this->kategoriData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'nama_kategori' => 'Test Kategori',
                    'jenis' => 'pemasukan',
                    'deskripsi' => 'Test Description'
                ]
            ]);

        $this->assertDatabaseHas('kategoris', [
            'nama_kategori' => 'Test Kategori',
            'jenis' => 'pemasukan'
        ]);
    }

    public function test_can_get_kategori_transaksi_list()
    {
        $this->actingAs($this->user);

        Kategori::create($this->kategoriData);

        $response = $this->getJson('/api/kategori');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nama_kategori',
                        'jenis',
                        'deskripsi'
                    ]
                ]
            ]);
    }

    public function test_can_get_single_kategori_transaksi()
    {
        $this->actingAs($this->user);

        $kategori = Kategori::create($this->kategoriData);

        $response = $this->getJson('/api/kategori/' . $kategori->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $kategori->id,
                    'nama_kategori' => 'Test Kategori',
                    'jenis' => 'pemasukan',
                    'deskripsi' => 'Test Description'
                ]
            ]);
    }

    public function test_can_update_kategori_transaksi()
    {
        $this->actingAs($this->user);

        $kategori = Kategori::create($this->kategoriData);

        $updateData = [
            'nama_kategori' => 'Updated Kategori',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/kategori/' . $kategori->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $kategori->id,
                    'nama_kategori' => 'Updated Kategori',
                    'jenis' => 'pengeluaran',
                    'deskripsi' => 'Updated Description'
                ]
            ]);

        $this->assertDatabaseHas('kategoris', [
            'id' => $kategori->id,
            'nama_kategori' => 'Updated Kategori',
            'jenis' => 'pengeluaran'
        ]);
    }

    public function test_can_delete_kategori_transaksi()
    {
        $this->actingAs($this->user);

        $kategori = Kategori::create($this->kategoriData);

        $response = $this->deleteJson('/api/kategori/' . $kategori->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('kategoris', [
            'id' => $kategori->id
        ]);
    }

    public function test_validation_rules_for_kategori_transaksi_update()
    {
        $this->actingAs($this->user);

        $kategori = Kategori::create($this->kategoriData);

        $invalidData = [
            'nama_kategori' => '',
            'jenis' => 'invalid',
            'deskripsi' => ''
        ];

        $response = $this->putJson('/api/kategori/' . $kategori->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_kategori',
                    'jenis'
                ]
            ]);
    }

    public function test_unique_nama_kategori_validation_for_kategori_transaksi_update()
    {
        $this->actingAs($this->user);

        $kategori1 = Kategori::create($this->kategoriData);
        $kategori2 = Kategori::create([
            'nama_kategori' => 'Another Kategori',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Another Description'
        ]);

        $updateData = [
            'nama_kategori' => 'Another Kategori',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/kategori/' . $kategori1->id, $updateData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_kategori'
                ]
            ]);
    }
}
