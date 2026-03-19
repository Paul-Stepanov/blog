<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Domain\Article\Repositories\TagRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Resources\TagCollectionResource;
use Illuminate\Http\JsonResponse;

/**
 * Public Tag API Controller.
 *
 * Handles read-only tag operations.
 */
final class TagController extends Controller
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

    /**
     * Get all tags ordered by name.
     *
     * @OA\Get(
     *     path="/api/tags",
     *     summary="Get all tags",
     *     tags={"Tags"},
     *     @OA\Response(response=200, description="List of tags")
     * )
     */
    public function getAllTags(): JsonResponse
    {
        $tags = $this->tagRepository->findAllOrderedByName();

        return response()->json([
            'success' => true,
            'data' => new TagCollectionResource($tags),
        ]);
    }

    /**
     * Get popular tags (most used).
     *
     * @OA\Get(
     *     path="/api/tags/popular",
     *     summary="Get popular tags",
     *     tags={"Tags"},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", minimum=1, maximum=50)),
     *     @OA\Response(response=200, description="List of popular tags")
     * )
     */
    public function getPopularTags(): JsonResponse
    {
        $limit = min((int) request()->input('limit', 10), 50);
        $tags = $this->tagRepository->getPopular($limit);

        return response()->json([
            'success' => true,
            'data' => new TagCollectionResource($tags),
        ]);
    }

    /**
     * Get a single tag by slug.
     *
     * @OA\Get(
     *     path="/api/tags/{slug}",
     *     summary="Get tag by slug",
     *     tags={"Tags"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Tag details"),
     *     @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function getTagBySlug(string $slug): JsonResponse
    {
        $tag = $this->tagRepository->findBySlug($slug);

        if ($tag === null) {
            return response()->json([
                'success' => false,
                'error' => 'tag_not_found',
                'message' => "Tag not found with slug: {$slug}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tag->getId()->getValue(),
                'name' => $tag->getName(),
                'slug' => $tag->getSlug()->getValue(),
            ],
        ]);
    }
}