<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Settings\Exceptions\SettingNotFoundException;
use App\Application\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Public Settings API Controller.
 *
 * Exposes public site settings for frontend.
 * Only whitelisted settings are accessible.
 */
final class SettingsController extends Controller
{
    private const array PUBLIC_SETTINGS = [
        'site.title',
        'site.description',
        'site.author',
        'site.url',
        'seo.meta_title',
        'seo.meta_description',
        'seo.meta_keywords',
        'social.github',
        'social.twitter',
        'social.linkedin',
    ];

    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Get all public settings.
     *
     * Returns key-value pairs of whitelisted public settings.
     *
     * @OA\Get(
     *     path="/api/settings",
     *     summary="Get public settings",
     *     tags={"Settings"},
     *     @OA\Response(response=200, description="Public settings")
     * )
     */
    public function getPublicSettings(): JsonResponse
    {
        $allSettings = $this->settingsService->getAllAsKeyValue();

        $publicSettings = array_intersect_key(
            $allSettings,
            array_flip(self::PUBLIC_SETTINGS)
        );

        return response()->json([
            'success' => true,
            'data' => $publicSettings,
        ]);
    }

    /**
     * Get a specific public setting by key.
     *
     * @OA\Get(
     *     path="/api/settings/{key}",
     *     summary="Get setting by key",
     *     tags={"Settings"},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Setting value"),
     *     @OA\Response(response=404, description="Setting not found or not public")
     * )
     */
    public function getSettingByKey(string $key): JsonResponse
    {
        if (!in_array($key, self::PUBLIC_SETTINGS, true)) {
            return response()->json([
                'success' => false,
                'error' => 'setting_not_public',
                'message' => 'The requested setting is not publicly accessible.',
            ], 404);
        }

        try {
            $setting = $this->settingsService->getSetting($key);

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'value_type' => $setting->valueType,
                ],
            ]);
        } catch (SettingNotFoundException) {
            return response()->json([
                'success' => false,
                'error' => 'setting_not_found',
                'message' => "Setting not found: {$key}",
            ], 404);
        }
    }
}