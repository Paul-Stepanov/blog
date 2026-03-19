<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\User\DTOs\UserDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource.
 *
 * Transforms UserDTO for API responses.
 */
final class UserResource extends JsonResource
{
    /**
     * @param UserDTO $resource
     */
    public function __construct(UserDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var UserDTO $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
        ];
    }
}