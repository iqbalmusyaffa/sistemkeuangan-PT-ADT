<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     * Only accessible by superadmin (via route/middleware).
     */
    public function store(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validation and user storage
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role' => 'nullable|in:admin,superadmin',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_pictures', $filename, 'public');
            $data['profile_picture'] = $filename;
        }

        $user = User::create($data);

        return response()->json(['user' => $user], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $authUser = $request->user();

        // Admin can only update their own data or superadmin can update any user
        if ($authUser->role !== 'superadmin' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'password' => 'sometimes|string|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role' => 'nullable|in:admin,superadmin',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->all();

        // Admin cannot change role or status
        if ($authUser->role !== 'superadmin') {
            unset($data['role']);
            unset($data['status']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_pictures', $filename, 'public');
            $data['profile_picture'] = $filename;
        }

        $user->update($data);
        return response()->json($user);
    }

    /**
     * Delete a user (superadmin only).
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Login a user.
     */
    public function login(Request $request)
    {
        // Check if there are any registered users
        $userCount = User::count();
        if ($userCount === 0) {
            return response()->json([
                'message' => 'No users are registered.',
                'canLogin' => false,
            ], 403);
        }

        // Validate email and password input
        $validator = Validator::make($request->only('email', 'password'), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user is found and if password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect email or password.'], 401);
        }

        // Check if user's status is active
        if ($user->status === 'inactive') {
            return response()->json(['message' => 'Your account is inactive. Contact Superadmin.'], 403);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Your account is suspended. Contact Superadmin.'], 403);
        }

        // Delete any existing tokens
        $user->tokens->each(function ($token) {
            $token->delete();
        });

        // Create new token with 1 hour expiration
        $token = $user->createToken('auth_token')->plainTextToken;

        // Set token expiration
        $token_expiry = now()->addHour();

        // Update the token expiration field
        $user->tokens()->latest()->first()->update([
            'expires_at' => $token_expiry,
        ]);

        // Return token and expiration
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'token_expiry' => $token_expiry,
        ]);
    }

    /**
     * Logout a user.
     */
    public function logout(Request $request)
    {
        // Delete all tokens associated with the user
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Display the user's profile info.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'profile_picture' => $user->profile_picture,
            'role' => $user->role,
            'status' => $user->status,
        ]);
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
