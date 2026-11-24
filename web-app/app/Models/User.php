<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['mahasiswa', 'pengelola', 'admin'];

    protected $fillable = ['name', 'nim', 'email', 'password', 'role', 'is_active', 'must_change_password', 'last_login_at',
        'self_registered', 'approved_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'self_registered' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SurveySubmission::class);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('self_registered', true)->whereNull('approved_at')->where('is_active', false);
    }

    /** Akun hasil pendaftaran mandiri yang belum disetujui admin. */
    public function isPendingApproval(): bool
    {
        return $this->self_registered && $this->approved_at === null && ! $this->is_active;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'mahasiswa' => 'survey.index',
            default => 'dashboard',
        };
    }
}
