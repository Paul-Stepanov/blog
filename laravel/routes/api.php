<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Api\{
    ArticleController,
    CategoryController,
    ContactController,
    HealthController,
    SettingsController,
    TagController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check (no rate limiting)
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class)->name('api.health');

/*
|--------------------------------------------------------------------------
| Public API Routes (rate limited: 60/minute)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:60,1'])->group(function (): void {

    // Articles
    Route::get('/articles', [ArticleController::class, 'getPublishedArticles'])->name('api.articles.index');
    Route::get('/articles/{slug}', [ArticleController::class, 'getArticleBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.articles.show');

    // Categories
    Route::get('/categories', [CategoryController::class, 'getCategoriesWithArticles'])->name('api.categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'getCategoryBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.categories.show');

    // Tags
    Route::get('/tags', [TagController::class, 'getAllTags'])->name('api.tags.index');
    Route::get('/tags/popular', [TagController::class, 'getPopularTags'])->name('api.tags.popular');
    Route::get('/tags/{slug}', [TagController::class, 'getTagBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.tags.show');

    // Settings (public only)
    Route::get('/settings', [SettingsController::class, 'getPublicSettings'])->name('api.settings.index');
    Route::get('/settings/{key}', [SettingsController::class, 'getSettingByKey'])
        ->where('key', '[a-z]+\.[a-z_]+')
        ->name('api.settings.show');
});

/*
|--------------------------------------------------------------------------
| Contact Form (strict rate limiting: 3/hour)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:3,60'])->group(function (): void {
    Route::post('/contact', [ContactController::class, 'submitMessage'])->name('api.contact.store');
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (auth:sanctum, rate limited: 120/minute)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function (): void {
        // Admin routes will be added in Phase 7-10
    });