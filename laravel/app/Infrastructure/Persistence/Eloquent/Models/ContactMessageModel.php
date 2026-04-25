<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Casts\EmailCast;
use App\Infrastructure\Persistence\Casts\IPAddressCast;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model for ContactMessage persistence.
 *
 * Represents messages sent through the contact form.
 */
final class ContactMessageModel extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'contact_messages';

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory>
     */
    protected static $factory = ContactMessageFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'subject',
        'message',
        'ip_address',
        'user_agent',
        'is_read',
    ];

    /**
     * @var array<string, class-string<\Illuminate\Contracts\Database\Eloquent\CastsAttributes>|string>
     */
    protected $casts = [
        'email' => EmailCast::class,
        'ip_address' => IPAddressCast::class,
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for unread messages.
     */
    public function scopeUnread(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read messages.
     */
    public function scopeRead(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for recent messages.
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for search by name, email, or subject.
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%")
                ->orWhere('subject', 'LIKE', "%{$term}%")
                ->orWhere('message', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope for messages from specific email.
     */
    public function scopeByEmail(\Illuminate\Database\Eloquent\Builder $query, string $email): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope for messages from specific IP.
     */
    public function scopeByIpAddress(\Illuminate\Database\Eloquent\Builder $query, string $ipAddress): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for messages within date range.
     */
    public function scopeDateRange(
        \Illuminate\Database\Eloquent\Builder $query,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(\Illuminate\Database\Eloquent\Builder $query, string $uuid): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->save();
        }
    }

    /**
     * Mark message as unread.
     */
    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->is_read = false;
            $this->save();
        }
    }

    /**
     * Get message preview (truncated).
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->message) <= $length) {
            return $this->message;
        }

        return substr($this->message, 0, $length) . '...';
    }

    /**
     * Check if message is unread.
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}