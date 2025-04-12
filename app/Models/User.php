<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'profile_picture',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the role of the user.
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Check if the user has the given role.
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if the user is a superadmin.
     */
    public function isSuperadmin()
    {
        return $this->hasRole(self::ROLE_SUPERADMIN);
    }

    /**
     * Check if the user is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user's token has expired.
     */
    public function hasExpiredToken()
    {
        $token = $this->tokens->last(); // Get the latest token
        return $token && $token->expires_at && $token->expires_at->isPast();
    }

    /**
     * Get all tokens associated with the user.
     */
    public function tokens()
    {
        return $this->hasMany(\Laravel\Sanctum\PersonalAccessToken::class);
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
