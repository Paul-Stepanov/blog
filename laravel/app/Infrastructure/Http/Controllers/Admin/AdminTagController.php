<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Article\Commands\CreateTagCommand;
use App\Application\Article\Commands\UpdateTagCommand;
use App\Application\Article\Services\TagService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\TagRequest;
use App\Infrastructure\Http\Resources\TagResource;
use Illuminate\Http\JsonResponse;

/**
 * Admin Tag Management Controller.
 *
 * Handles CRUD operations for tags in the admin panel.
 */
final class AdminTagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService,
    ) {}

    /**
     * Get all tags.
     *
     * @OA\Get(
     *     path="/api/admin/tags",
     *     summary="Get all tags (admin)",
     *     tags={"Admin Tags"},
     *     @OA\Response(response=200, description="List of tags")
     * )
     */
    public function getAllTags(): JsonResponse
    {
        $tags = $this->tagService->getAllTags();

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
        ]);
    }

    /**
     * Get a single tag by ID.
     *
     * @OA\Get(
     *     path="/api/admin/tags/{id}",
     *     summary="Get tag by ID (admin)",
     *     tags={"Admin Tags"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Tag details"),
     *     @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function getTagById(string $id): JsonResponse
    {
        $tag = $this->tagService->getTagById($id);

        if ($tag === null) {
            return response()->json([
                'success' => false,
                'error' => 'tag_not_found',
                'message' => "Tag not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Create a new tag.
     *
     * @OA\Post(
     *     path="/api/admin/tags",
     *     summary="Create tag",
     *     tags={"Admin Tags"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string")
     *     )),
     *     @OA\Response(response=201, description="Tag created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createTag(TagRequest $request): JsonResponse
    {
        $command = CreateTagCommand::fromRequest($request);
        $tag = $this->tagService->createTag($command);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully.',
            'data' => new TagResource($tag),
        ], 201);
    }

    /**
     * Update an existing tag.
     *
     * @OA\Put(
     *     path="/api/admin/tags/{id}",
     *     summary="Update tag",
     *     tags={"Admin Tags"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string")
     *     )),
     *     @OA\Response(response=200, description="Tag updated"),
     *     @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function updateTag(TagRequest $request, string $id): JsonResponse
    {
        $command = UpdateTagCommand::fromRequest($request, $id);
        $tag = $this->tagService->updateTag($command);

        if ($tag === null) {
            return response()->json([
                'success' => false,
                'error' => 'tag_not_found',
                'message' => "Tag not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully.',
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Delete a tag.
     *
     * @OA\Delete(
     *     path="/api/admin/tags/{id}",
     *     summary="Delete tag",
     *     tags={"Admin Tags"},
     *     @OA\Response(response=200, description="Tag deleted"),
     *     @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function deleteTag(string $id): JsonResponse
    {
        $deleted = $this->tagService->deleteTag($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'tag_not_found',
                'message' => "Tag not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}