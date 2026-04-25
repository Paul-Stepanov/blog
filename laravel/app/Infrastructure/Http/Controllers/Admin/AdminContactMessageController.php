<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Contact\Services\ContactService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Resources\ContactMessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Contact Message Management Controller.
 *
 * Handles contact message operations in the admin panel.
 */
final class AdminContactMessageController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    /**
     * Get all contact messages.
     *
     * @OA\Get(
     *     path="/api/admin/messages",
     *     summary="Get all messages (admin)",
     *     tags={"Admin Messages"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of messages")
     * )
     */
    public function getAllMessages(Request $request): JsonResponse
    {
        $page = max((int) $request->input('page', 1), 1);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $result = $this->contactService->getAllMessages($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => ContactMessageResource::collection($result->items),
            'meta' => [
                'current_page' => $result->page,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'from' => ($result->page - 1) * $result->perPage + 1,
                'to' => min($result->page * $result->perPage, $result->total),
            ],
        ]);
    }

    /**
     * Get a single message by ID.
     *
     * @OA\Get(
     *     path="/api/admin/messages/{id}",
     *     summary="Get message by ID (admin)",
     *     tags={"Admin Messages"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Message details"),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function getMessageById(string $id): JsonResponse
    {
        $message = $this->contactService->getMessageById($id);

        if ($message === null) {
            return response()->json([
                'success' => false,
                'error' => 'message_not_found',
                'message' => "Message not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ContactMessageResource($message),
        ]);
    }

    /**
     * Mark a message as read.
     *
     * @OA\Post(
     *     path="/api/admin/messages/{id}/mark-read",
     *     summary="Mark message as read",
     *     tags={"Admin Messages"},
     *     @OA\Response(response=200, description="Message marked as read"),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function markAsRead(string $id): JsonResponse
    {
        $this->contactService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read.',
        ]);
    }

    /**
     * Mark a message as unread.
     *
     * @OA\Post(
     *     path="/api/admin/messages/{id}/mark-unread",
     *     summary="Mark message as unread",
     *     tags={"Admin Messages"},
     *     @OA\Response(response=200, description="Message marked as unread"),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function markAsUnread(string $id): JsonResponse
    {
        $this->contactService->markAsUnread($id);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as unread.',
        ]);
    }

    /**
     * Delete a message.
     *
     * @OA\Delete(
     *     path="/api/admin/messages/{id}",
     *     summary="Delete message",
     *     tags={"Admin Messages"},
     *     @OA\Response(response=200, description="Message deleted successfully"),
     *     @OA\Response(response=404, description="Message not found")
     * )
     */
    public function deleteMessage(string $id): JsonResponse
    {
        $deleted = $this->contactService->deleteMessage($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'message_not_found',
                'message' => "Message not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }
}