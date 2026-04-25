<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\SettingsRequest;
use App\Infrastructure\Http\Resources\SettingsResource;
use Illuminate\Http\{JsonResponse, Request};

/**
 * Admin Settings Management Controller.
 *
 * Handles site settings operations in the admin panel.
 */
final class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Get all settings.
     *
     * @OA\Get(
     *     path="/api/admin/settings",
     *     summary="Get all settings (admin)",
     *     tags={"Admin Settings"},
     *     @OA\Response(response=200, description="List of all settings")
     * )
     */
    public function getAllSettings(): JsonResponse
    {
        $settings = $this->settingsService->getAllSettings();

        return response()->json([
            'success' => true,
            'data' => SettingsResource::collection($settings),
        ]);
    }

    /**
     * Get settings by group.
     *
     * @OA\Get(
     *     path="/api/admin/settings/group/{group}",
     *     summary="Get settings by group (admin)",
     *     tags={"Admin Settings"},
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Settings for the specified group")
     * )
     */
    public function getSettingsByGroup(string $group): JsonResponse
    {
        $settings = $this->settingsService->getSettingsByGroup($group);

        return response()->json([
            'success' => true,
            'data' => SettingsResource::collection($settings),
        ]);
    }

    /**
     * Get a single setting by key.
     *
     * @OA\Get(
     *     path="/api/admin/settings/{key}",
     *     summary="Get setting by key (admin)",
     *     tags={"Admin Settings"},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Setting details"),
     *     @OA\Response(response=404, description="Setting not found")
     * )
     */
    public function getSetting(string $key): JsonResponse
    {
        try {
            $setting = $this->settingsService->getSetting($key);

            return response()->json([
                'success' => true,
                'data' => new SettingsResource($setting),
            ]);
        } catch (\App\Application\Settings\Exceptions\SettingNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'setting_not_found',
                'message' => "Setting not found with key: {$key}",
            ], 404);
        }
    }

    /**
     * Set a single setting value.
     *
     * @OA\Put(
     *     path="/api/admin/settings/{key}",
     *     summary="Set setting value",
     *     tags={"Admin Settings"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"value"},
     *         @OA\Property(property="value", type="string")
     *     )),
     *     @OA\Response(response=200, description="Setting updated successfully")
     * )
     */
    public function setSetting(Request $request, string $key): JsonResponse
    {
        $request->validate(['value' => 'required']);

        try {
            $setting = $this->settingsService->setSetting(
                keyString: $key,
                value: $request->input('value')
            );

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully.',
                'data' => new SettingsResource($setting),
            ]);
        } catch (\App\Application\Settings\Exceptions\SettingNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'setting_not_found',
                'message' => "Setting not found with key: {$key}",
            ], 404);
        }
    }

    /**
     * Set multiple settings at once.
     *
     * @OA\Post(
     *     path="/api/admin/settings/batch",
     *     summary="Set multiple settings",
     *     tags={"Admin Settings"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"settings"},
     *         @OA\Property(
     *             property="settings",
     *             type="object",
     *             additionalProperties={"type":"string"},
     *             example={"site_name": "My Blog", "posts_per_page": "10"}
     *         )
     *     )),
     *     @OA\Response(response=200, description="Settings updated successfully")
     * )
     */
    public function setManySettings(SettingsRequest $request): JsonResponse
    {
        $settings = $this->settingsService->setMany(
            $request->validated('settings')
        );

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => SettingsResource::collection($settings),
        ]);
    }

    /**
     * Delete a setting.
     *
     * @OA\Delete(
     *     path="/api/admin/settings/{key}",
     *     summary="Delete setting",
     *     tags={"Admin Settings"},
     *     @OA\Response(response=200, description="Setting deleted successfully")
     * )
     */
    public function deleteSetting(string $key): JsonResponse
    {
        $this->settingsService->deleteSetting($key);

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully.',
        ]);
    }

    /**
     * Delete all settings in a group.
     *
     * @OA\Delete(
     *     path="/api/admin/settings/group/{group}",
     *     summary="Delete settings group",
     *     tags={"Admin Settings"},
     *     @OA\Response(response=200, description="Settings group deleted successfully")
     * )
     */
    public function deleteGroup(string $group): JsonResponse
    {
        $this->settingsService->deleteGroup($group);

        return response()->json([
            'success' => true,
            'message' => 'Settings group deleted successfully.',
        ]);
    }
}