<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test superadmin can create new user
     */
    public function test_superadmin_can_create_user()
    {
        // Create superadmin user
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active'
        ]);

        $this->actingAs($superadmin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'role',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'role' => 'admin',
            'status' => 'active'
        ]);
    }

    /**
     * Test admin cannot create new user
     */
    public function test_admin_cannot_create_user()
    {
        // Create admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);
    }

    /**
     * Test user login
     */
    public function test_user_can_login()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'username' => 'testuser',
            'role' => 'admin',
            'status' => 'active'
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'role',
                    'status'
                ],
                'role',
                'token_expiry'
            ]);
    }

    /**
     * Test user profile update
     */
    public function test_user_can_update_profile()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'username' => 'updateduser'
        ];

        $response = $this->putJson('/api/users/' . $user->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'user' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'username' => 'updateduser'
                ]
            ]);
    }

    /**
     * Test user profile retrieval
     */
    public function test_user_can_get_profile()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'username',
                'role',
                'status'
            ]);
    }
}
