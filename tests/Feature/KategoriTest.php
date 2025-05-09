<?php

namespace Tests\Feature;

use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_kategori_with_valid_data()
    {
        $kategoriData = [
            'nama_kategori' => 'Gaji',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Pendapatan dari gaji'
        ];

        $response = $this->postJson('/api/kategori', $kategoriData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'nama_kategori',
                'jenis',
                'deskripsi',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('kategoris', $kategoriData);
    }

    /** @test */
    public function it_validates_nama_kategori_is_required()
    {
        $response = $this->postJson('/api/kategori', [
            'jenis' => 'pemasukan',
            'deskripsi' => 'Test deskripsi'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_kategori']);
    }

    /** @test */
    public function it_validates_jenis_is_required_and_enum()
    {
        // Test jenis is required
        $response = $this->postJson('/api/kategori', [
            'nama_kategori' => 'Test Kategori',
            'deskripsi' => 'Test deskripsi'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['jenis']);

        // Test jenis must be pemasukan or pengeluaran
        $response = $this->postJson('/api/kategori', [
            'nama_kategori' => 'Test Kategori',
            'jenis' => 'invalid_value',
            'deskripsi' => 'Test deskripsi'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['jenis']);
    }

    /** @test */
    public function it_allows_null_deskripsi()
    {
        $kategoriData = [
            'nama_kategori' => 'Test Kategori',
            'jenis' => 'pemasukan'
        ];

        $response = $this->postJson('/api/kategori', $kategoriData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kategoris', $kategoriData);
    }

    /** @test */
    public function it_can_update_kategori_with_valid_data()
    {
        $kategori = Kategori::factory()->create();

        $updateData = [
            'nama_kategori' => 'Updated Kategori',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Updated deskripsi'
        ];

        $response = $this->putJson("/api/kategori/{$kategori->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('kategoris', $updateData);
    }

    /** @test */
    public function it_can_delete_kategori()
    {
        $kategori = Kategori::factory()->create();

        $response = $this->deleteJson("/api/kategori/{$kategori->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('kategoris', ['id' => $kategori->id]);
    }

    /** @test */
    public function it_can_list_all_kategoris()
    {
        Kategori::factory()->count(3)->create();

        $response = $this->getJson('/api/kategori');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_show_single_kategori()
    {
        $kategori = Kategori::factory()->create();

        $response = $this->getJson("/api/kategori/{$kategori->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $kategori->id,
                'nama_kategori' => $kategori->nama_kategori,
                'jenis' => $kategori->jenis,
                'deskripsi' => $kategori->deskripsi
            ]);
    }
} 