<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Contact\Commands\SendMessageCommand;
use App\Application\Contact\Services\ContactService;
use App\Domain\Contact\ValueObjects\{Email, IPAddress};
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Api\ContactRequest;
use Illuminate\Http\JsonResponse;

/**
 * Public Contact API Controller.
 *
 * Handles contact form submissions.
 * Rate limited to prevent spam.
 */
final class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    /**
     * Submit a contact message.
     *
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Submit contact message",
     *     tags={"Contact"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "subject", "message"},
     *         @OA\Property(property="name", type="string", maxLength=100),
     *         @OA\Property(property="email", type="string", format="email", maxLength=254),
     *         @OA\Property(property="subject", type="string", maxLength=200),
     *         @OA\Property(property="message", type="string", minLength=10, maxLength=5000)
     *     )),
     *     @OA\Response(response=201, description="Message sent successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function submitMessage(ContactRequest $request): JsonResponse
    {
        $command = new SendMessageCommand(
            name: $request->validated('name'),
            email: Email::fromString($request->validated('email')),
            subject: $request->validated('subject'),
            message: $request->validated('message'),
            ipAddress: IPAddress::fromString($request->ip()),
            userAgent: $request->userAgent(),
        );

        $dto = $this->contactService->sendMessage($command);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $dto->id,
            ],
        ], 201);
    }
}