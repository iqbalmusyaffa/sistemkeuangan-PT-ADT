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

    /**
     * Test creating a new kategori transaksi
     */
    public function test_can_create_kategori_transaksi()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/kategori', $this->kategoriData);

        $response->assertStatus(201)
            ->assertJson([
                'nama_kategori' => 'Test Kategori',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Test Description'
            ]);

        $this->assertDatabaseHas('kategoris', [
            'nama_kategori' => 'Test Kategori',
            'jenis' => 'pemasukan'
        ]);
    }

    /**
     * Test retrieving kategori transaksi list
     */
    public function test_can_get_kategori_transaksi_list()
    {
        $this->actingAs($this->user);

        // Create a test kategori
        Kategori::create($this->kategoriData);

        $response = $this->getJson('/api/kategori');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'nama_kategori',
                    'jenis',
                    'deskripsi'
                ]
            ]);
    }

    /**
     * Test retrieving single kategori transaksi
     */
    public function test_can_get_single_kategori_transaksi()
    {
        $this->actingAs($this->user);

        // Create a test kategori
        $kategori = Kategori::create($this->kategoriData);

        $response = $this->getJson('/api/kategori/' . $kategori->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $kategori->id,
                'nama_kategori' => 'Test Kategori',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Test Description'
            ]);
    }

    /**
     * Test updating kategori transaksi
     */
    public function test_can_update_kategori_transaksi()
    {
        $this->actingAs($this->user);

        // Create a test kategori
        $kategori = Kategori::create($this->kategoriData);

        $updateData = [
            'nama_kategori' => 'Updated Kategori',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Updated Description'
        ];

        $response = $this->putJson('/api/kategori/' . $kategori->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $kategori->id,
                'nama_kategori' => 'Updated Kategori',
                'jenis' => 'pengeluaran',
                'deskripsi' => 'Updated Description'
            ]);

        $this->assertDatabaseHas('kategoris', [
            'id' => $kategori->id,
            'nama_kategori' => 'Updated Kategori',
            'jenis' => 'pengeluaran'
        ]);
    }

    /**
     * Test deleting kategori transaksi
     */
    public function test_can_delete_kategori_transaksi()
    {
        $this->actingAs($this->user);

        // Create a test kategori
        $kategori = Kategori::create($this->kategoriData);

        $response = $this->deleteJson('/api/kategori/' . $kategori->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('kategoris', [
            'id' => $kategori->id
        ]);
    }

    /**
     * Test validation rules for kategori transaksi creation
     */
    public function test_validation_rules_for_kategori_transaksi_creation()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'nama_kategori' => '', // required
            'jenis' => 'invalid', // must be pemasukan or pengeluaran
            'deskripsi' => '' // optional
        ];

        $response = $this->postJson('/api/kategori', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'nama_kategori'
                ]
            ]);
    }

    /**
     * Test validation rules for kategori transaksi update
     */
    public function test_validation_rules_for_kategori_transaksi_update()
    {
        $this->actingAs($this->user);

        // Create a test kategori
        $kategori = Kategori::create($this->kategoriData);

        $invalidData = [
            'nama_kategori' => '', // required
            'jenis' => 'invalid', // must be pemasukan or pengeluaran
            'deskripsi' => '' // optional
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

    /**
     * Test unique nama_kategori validation for kategori transaksi update
     */
    public function test_unique_nama_kategori_validation_for_kategori_transaksi_update()
    {
        $this->actingAs($this->user);

        // Create two test kategoris
        $kategori1 = Kategori::create($this->kategoriData);
        $kategori2 = Kategori::create([
            'nama_kategori' => 'Another Kategori',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Another Description'
        ]);

        $updateData = [
            'nama_kategori' => 'Another Kategori', // trying to use nama_kategori from kategori2
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
