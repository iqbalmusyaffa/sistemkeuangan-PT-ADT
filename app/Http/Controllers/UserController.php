<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Hanya bisa diakses oleh superadmin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     * Hanya bisa diakses oleh superadmin
     */
    public function store(Request $request)
    {
        $authUser = $request->user();
        if ($authUser->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'required|in:admin,superadmin',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Simpan file ke storage/app/public/profile_pictures
            $file->storeAs('profile_pictures', $filename, 'public');
            $data['profile_picture'] = $filename;
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified user.
     * Superadmin bisa lihat semua user, admin hanya bisa lihat profilnya sendiri
     */
    public function show(Request $request, string $id)
    {
        $authUser = $request->user();
        
        // Jika bukan superadmin, hanya bisa lihat profil sendiri
        if ($authUser->role !== 'superadmin' && $authUser->id !== (int) $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the specified user.
     * Superadmin bisa update semua user, admin hanya bisa update profilnya sendiri
     */
    public function update(Request $request, string $id)
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Cari user yang akan diupdate
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Cek hak akses
        $isSelfUpdate = $authUser->id === (int) $id;
        $isSuperAdmin = $authUser->role === 'superadmin';

        if (!$isSuperAdmin && !$isSelfUpdate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Atur rules validasi berdasarkan role dan tipe update
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'profile_picture' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Tambahan rules untuk superadmin
        if ($isSuperAdmin) {
            $rules['role'] = 'sometimes|in:admin,superadmin';
            $rules['status'] = 'sometimes|in:active,inactive,suspended';
        }

        // Validasi input
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Siapkan data update
        $data = $request->only(['name', 'email', 'username']);

        // Handle password update
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Handle profile picture
        if ($request->hasFile('profile_picture')) {
            // Hapus foto lama jika ada
            if ($user->profile_picture) {
                Storage::disk('public')->delete('profile_pictures/' . $user->profile_picture);
            }

            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Simpan file ke storage/app/public/profile_pictures
            $file->storeAs('profile_pictures', $filename, 'public');
            $data['profile_picture'] = $filename;
        }

        // Hanya superadmin yang bisa update role dan status
        if ($isSuperAdmin) {
            if ($request->has('role')) {
                // Prevent changing own role from superadmin
                if ($isSelfUpdate && $request->role !== 'superadmin') {
                    return response()->json([
                        'message' => 'Superadmin cannot change their own role'
                    ], 403);
                }
                $data['role'] = $request->role;
            }
            if ($request->has('status')) {
                // Prevent deactivating own account
                if ($isSelfUpdate && $request->status !== 'active') {
                    return response()->json([
                        'message' => 'Superadmin cannot deactivate their own account'
                    ], 403);
                }
                $data['status'] = $request->status;
            }
        }

        // Update user
        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete a user (superadmin only).
     */
    public function destroy(Request $request, string $id)
    {
        $authUser = $request->user();
        
        // Hanya superadmin yang bisa menghapus user
        if ($authUser->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Tidak bisa menghapus diri sendiri
        if ($authUser->id === (int) $id) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Hapus foto profil jika ada
        if ($user->profile_picture) {
            Storage::disk('public')->delete('profile_pictures/' . $user->profile_picture);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Display the user's profile info.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
            'status' => $user->status,
        ];

        if ($user->profile_picture) {
            $profileData['profile_picture'] = $user->profile_picture;
        }

        return response()->json($profileData);
    }

    /**
     * Login a user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is not active'], 403);
        }

        // Create token with expiry
        $token_expiry = now()->addHours(12);
        $token = $user->createToken('auth-token', ['*'], $token_expiry)->plainTextToken;

        // Update token expiry in database
        $user->tokens()->latest()->first()->update([
            'expires_at' => $token_expiry,
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role' => $user->role,
            'token_expiry' => $token_expiry,
        ]);
    }

    /**
     * Logout a user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Handle the forgot password request.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Simulate sending a reset link (in a real app, send an email)
        // Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Password reset link sent to your email.']);
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Password reset logic
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password updated']);
        }

        return response()->json(['message' => 'Invalid token'], 400);
    }
}
