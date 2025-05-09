<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;

class KategoriTransaksiTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    public function test_store_creates_new_kategori()
    {
        $kategoriData = [
            'nama_kategori' => 'Transportasi',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Biaya transportasi'
        ];

        $response = $this->postJson('/api/kategori', $kategoriData);

        $response->assertStatus(201)
                ->assertJsonFragment($kategoriData);

        $this->assertDatabaseHas('kategoris', $kategoriData);
    }

    public function test_show_returns_specific_kategori()
    {
        $kategori = Kategori::create([
            'nama_kategori' => 'Bonus',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Pendapatan bonus'
        ]);

        $response = $this->getJson("/api/kategori/{$kategori->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'nama_kategori' => 'Bonus',
                    'jenis' => 'pemasukan'
                ]);
    }

    public function test_update_modifies_existing_kategori()
    {
        $kategori = Kategori::create([
            'nama_kategori' => 'Belanja',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Pengeluaran belanja'
        ]);

        $updateData = [
            'nama_kategori' => 'Belanja Bulanan',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Pengeluaran belanja bulanan'
        ];

        $response = $this->putJson("/api/kategori/{$kategori->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('kategoris', $updateData);
    }

    public function test_destroy_deletes_kategori()
    {
        $kategori = Kategori::create([
            'nama_kategori' => 'Hiburan',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Pengeluaran hiburan'
        ]);

        $response = $this->deleteJson("/api/kategori/{$kategori->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('kategoris', [
            'id' => $kategori->id
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->postJson('/api/kategori', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nama_kategori', 'jenis']);
    }

    public function test_update_validates_unique_nama_kategori()
    {
        // Create first kategori
        Kategori::create([
            'nama_kategori' => 'Gaji',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Pendapatan gaji'
        ]);

        // Create second kategori to update
        $kategori2 = Kategori::create([
            'nama_kategori' => 'Bonus',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Pendapatan bonus'
        ]);

        // Try to update second kategori with first kategori's name
        $response = $this->putJson("/api/kategori/{$kategori2->id}", [
            'nama_kategori' => 'Gaji',
            'jenis' => 'pemasukan',
            'deskripsi' => 'Updated description'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nama_kategori']);
    }
}
