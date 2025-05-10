<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProyekFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_customer' => $this->faker->name,
            'nama_proyek' => $this->faker->company . ' Project',
            'nama_perusahaan' => $this->faker->company,
            'alamat' => $this->faker->address,
            'no_telp' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'lokasi' => $this->faker->city,
            'anggaran_kontrak' => $this->faker->numberBetween(10000000, 50000000),
            'tanggal_mulai' => $this->faker->date(),
            'tanggal_selesai' => $this->faker->date(),
            'status_project' => $this->faker->randomElement(['Berjalan', 'Selesai', 'Batal']),
            'deskripsi' => $this->faker->sentence,
        ];
    }
}
