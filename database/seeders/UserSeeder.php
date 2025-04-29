<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Superadmin
        $superadmin = User::create([
            'name' => 'Adam Jaya Teknik',
            'email' => 'adamjayateknik@gmail.com',
            'username' => 'adamjayateknik',
            'password' => Hash::make('Adamjayateknik87'),
            'role' => 'superadmin',
            'status' => 'active',
            'profile_picture' => null,
        ]);
         // Superadmin
         $superadmin = User::create([
            'name' => 'Admincobaiqbal',
            'email' => 'admincobaiqbal@gmail.com',
            'username' => 'admincobaiqbal',
            'password' => Hash::make('1234556789'),
            'role' => 'superadmin',
            'status' => 'active',
            'profile_picture' => null,
        ]);

        // Membuat token untuk Superadmin
        $superadminToken = $superadmin->createToken('Superadmin Token')->plainTextToken;
        \Log::info('Superadmin Token: ' . $superadminToken);

        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'adminadt@gmail.com',
            'username' => 'adminuser',
            'password' => Hash::make('123456789'),
            'role' => 'admin',
            'status' => 'active',
            'profile_picture' => null,
        ]);

        // Membuat token untuk Admin
        $adminToken = $admin->createToken('Admin Token')->plainTextToken;
        \Log::info('Admin Token: ' . $adminToken);
    }
}
