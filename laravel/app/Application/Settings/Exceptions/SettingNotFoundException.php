<?php

declare(strict_types=1);

namespace App\Application\Settings\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

/**
 * Exception thrown when a setting is not found.
 */
final class SettingNotFoundException extends ApplicationException
{
    /**
     * @param string $key Setting key that was not found
     */
    public static function byKey(string $key): self
    {
        return new self("Setting not found: {$key}");
    }

    /**
     * @param string $id Setting ID that was not found
     */
    public static function byId(string $id): self
    {
        return new self("Setting not found by ID: {$id}");
    }

    /**
     * @return non-empty-string
     */
    public function getErrorType(): string
    {
        return 'setting_not_found';
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return ['resource' => 'setting'];
    }
}