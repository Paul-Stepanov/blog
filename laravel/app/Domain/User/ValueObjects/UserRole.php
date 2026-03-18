<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;

/**
 * User Role Enum.
 *
 * Represents the role/permission level of a user.
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case AUTHOR = 'author';

    /**
     * Check if user has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if user can manage content (create/edit/delete articles).
     */
    public function canManageContent(): bool
    {
        return in_array($this, [self::ADMIN, self::EDITOR], true);
    }

    /**
     * Check if user can publish articles.
     */
    public function canPublish(): bool
    {
        return in_array($this, [self::ADMIN, self::EDITOR], true);
    }

    /**
     * Check if user can create articles.
     */
    public function canCreateArticles(): bool
    {
        return true; // All roles can create articles
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return true; // All roles can access admin
    }

    /**
     * Get all available role values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Администратор',
            self::EDITOR => 'Редактор',
            self::AUTHOR => 'Автор',
        };
    }

    /**
     * Get description of the role.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => 'Полный доступ ко всем функциям',
            self::EDITOR => 'Управление контентом и публикациями',
            self::AUTHOR => 'Создание и редактирование своих статей',
        };
    }

    /**
     * Get CSS class for UI styling.
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::ADMIN => 'role-admin',
            self::EDITOR => 'role-editor',
            self::AUTHOR => 'role-author',
        };
    }

    /**
     * Create from string value or throw ValidationException.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        $role = self::tryFrom(strtolower(trim($value)));

        if ($role === null) {
            throw ValidationException::forField(
                'role',
                sprintf('Invalid role "%s". Valid values: %s', $value, implode(', ', self::values()))
            );
        }

        return $role;
    }

    /**
     * Get default role for new users.
     */
    public static function default(): self
    {
        return self::AUTHOR;
    }
}