<?php

declare(strict_types=1);

namespace App\Domain\Contact\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * IP Address Value Object.
 *
 * Represents a validated IPv4 or IPv6 address.
 */
final class IPAddress extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = $value;
    }

    /**
     * Create from string.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    /**
     * Validate IP address format.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('ip_address', 'IP address must be a string');
        }

        if (empty($value)) {
            throw ValidationException::forField('ip_address', 'IP address cannot be empty');
        }

        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            throw ValidationException::forField('ip_address', sprintf('Invalid IP address: "%s"', $value));
        }
    }

    /**
     * Check if this is an IPv4 address.
     */
    public function isIPv4(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check if this is an IPv6 address.
     */
    public function isIPv6(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Check if this is a private/local IP.
     */
    public function isPrivate(): bool
    {
        return !$this->isPublic();
    }

    /**
     * Check if this is a public IP.
     */
    public function isPublic(): bool
    {
        return filter_var(
            $this->value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Check if this is localhost.
     */
    public function isLocalhost(): bool
    {
        return in_array($this->value, ['127.0.0.1', '::1'], true);
    }

    /**
     * Get anonymized version for logging (mask last octet).
     */
    public function getAnonymized(): string
    {
        if ($this->isIPv4()) {
            $parts = explode('.', $this->value);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // IPv6 - mask last 64 bits
        $parts = explode(':', $this->value);
        for ($i = 4, $iMax = count($parts); $i < $iMax; $i++) {
            $parts[$i] = '0';
        }
        return implode(':', $parts);
    }

    /**
     * Check equality with another IPAddress.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the IP address string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return ['ip_address' => $this->value];
    }
}