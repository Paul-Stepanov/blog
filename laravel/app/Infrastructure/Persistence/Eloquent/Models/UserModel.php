<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Casts\EmailCast;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Eloquent Model for User persistence.
 *
 * Represents users in the system (admin, editor, author).
 */
final class UserModel extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var class-string<Factory>
     */
    protected static $factory = UserFactory::class;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role',
        'remember_token',
    ];

    /**
     * @var array<string, class-string<CastsAttributes>|string>
     */
    protected $casts = [
        'email' => EmailCast::class,
        'role' => UserRole::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get articles authored by this user.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ArticleModel::class, 'author_id', 'id');
    }

    /**
     * Scope for admin users.
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for editor users.
     */
    public function scopeEditors(Builder $query): Builder
    {
        return $query->where('role', 'editor');
    }

    /**
     * Scope for author users.
     */
    public function scopeAuthors(Builder $query): Builder
    {
        return $query->where('role', 'author');
    }

    /**
     * Scope for users with a specific role.
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find by email.
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Search by name or email.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role->value === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is editor.
     */
    public function isEditor(): bool
    {
        return $this->role === UserRole::EDITOR;
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
