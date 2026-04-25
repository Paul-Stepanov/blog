<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Admin\{
    AdminArticleController,
    AdminCategoryController,
    AdminContactMessageController,
    AdminMediaController,
    AdminSettingsController,
    AdminTagController,
    AdminUserController
};
use App\Infrastructure\Http\Controllers\Admin\AuthController;
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
    Route::get('/articles', [ArticleController::class, 'getPublishedArticles'])->name('api.articles.getAll');
    Route::get('/articles/{slug}', [ArticleController::class, 'getArticleBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.articles.getBySlug');

    // Categories
    Route::get('/categories', [CategoryController::class, 'getCategoriesWithArticles'])->name('api.categories.getAll');
    Route::get('/categories/{slug}', [CategoryController::class, 'getCategoryBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.categories.getBySlug');

    // Tags
    Route::get('/tags', [TagController::class, 'getAllTags'])->name('api.tags.getAll');
    Route::get('/tags/popular', [TagController::class, 'getPopularTags'])->name('api.tags.getPopular');
    Route::get('/tags/{slug}', [TagController::class, 'getTagBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('api.tags.getBySlug');

    // Settings (public only)
    Route::get('/settings', [SettingsController::class, 'getPublicSettings'])->name('api.settings.getAll');
    Route::get('/settings/{key}', [SettingsController::class, 'getSettingByKey'])
        ->where('key', '[a-z]+\.[a-z_]+')
        ->name('api.settings.getByKey');
});

/*
|--------------------------------------------------------------------------
| Contact Form (strict rate limiting: 3/hour)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:3,60'])->group(function (): void {
    Route::post('/contact', [ContactController::class, 'submitMessage'])->name('api.contact.send');
});

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (login - strict rate limiting: 5/minute)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:5,1'])->prefix('admin/auth')->name('api.admin.auth.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum'])->prefix('admin/auth')->name('api.admin.auth.')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('user', [AuthController::class, 'getCurrentUser'])->name('user');
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
        // Articles
        Route::get('articles', [AdminArticleController::class, 'getAllArticles'])->name('articles.getAll');
        Route::get('articles/{id}', [AdminArticleController::class, 'getArticleById'])->name('articles.getById');
        Route::post('articles', [AdminArticleController::class, 'createArticle'])->name('articles.create');
        Route::put('articles/{id}', [AdminArticleController::class, 'updateArticle'])->name('articles.update');
        Route::delete('articles/{id}', [AdminArticleController::class, 'deleteArticle'])->name('articles.delete');
        Route::post('articles/{id}/publish', [AdminArticleController::class, 'publishArticle'])->name('articles.publish');
        Route::post('articles/{id}/archive', [AdminArticleController::class, 'archiveArticle'])->name('articles.archive');
        Route::put('articles/{id}/tags', [AdminArticleController::class, 'syncArticleTags'])->name('articles.syncTags');

        // Categories
        Route::get('categories', [AdminCategoryController::class, 'getAllCategories'])->name('categories.getAll');
        Route::get('categories/{id}', [AdminCategoryController::class, 'getCategoryById'])->name('categories.getById');
        Route::post('categories', [AdminCategoryController::class, 'createCategory'])->name('categories.create');
        Route::put('categories/{id}', [AdminCategoryController::class, 'updateCategory'])->name('categories.update');
        Route::delete('categories/{id}', [AdminCategoryController::class, 'deleteCategory'])->name('categories.delete');

        // Tags
        Route::get('tags', [AdminTagController::class, 'getAllTags'])->name('tags.getAll');
        Route::get('tags/{id}', [AdminTagController::class, 'getTagById'])->name('tags.getById');
        Route::post('tags', [AdminTagController::class, 'createTag'])->name('tags.create');
        Route::put('tags/{id}', [AdminTagController::class, 'updateTag'])->name('tags.update');
        Route::delete('tags/{id}', [AdminTagController::class, 'deleteTag'])->name('tags.delete');

        // Media
        Route::get('media/{id}', [AdminMediaController::class, 'getMediaFile'])->name('media.getById');
        Route::post('media/upload', [AdminMediaController::class, 'uploadFile'])->name('media.upload');
        Route::put('media/{id}/alt-text', [AdminMediaController::class, 'updateAltText'])->name('media.updateAltText');
        Route::put('media/{id}/rename', [AdminMediaController::class, 'renameFile'])->name('media.rename');
        Route::delete('media/{id}', [AdminMediaController::class, 'deleteFile'])->name('media.delete');

        // Messages
        Route::get('messages', [AdminContactMessageController::class, 'getAllMessages'])->name('messages.getAll');
        Route::get('messages/{id}', [AdminContactMessageController::class, 'getMessageById'])->name('messages.getById');
        Route::post('messages/{id}/mark-read', [AdminContactMessageController::class, 'markAsRead'])->name('messages.markRead');
        Route::post('messages/{id}/mark-unread', [AdminContactMessageController::class, 'markAsUnread'])->name('messages.markUnread');
        Route::delete('messages/{id}', [AdminContactMessageController::class, 'deleteMessage'])->name('messages.delete');

        // Settings
        Route::get('settings', [AdminSettingsController::class, 'getAllSettings'])->name('settings.getAll');
        Route::get('settings/group/{group}', [AdminSettingsController::class, 'getSettingsByGroup'])->name('settings.getByGroup');
        Route::get('settings/{key}', [AdminSettingsController::class, 'getSetting'])->name('settings.getByKey');
        Route::put('settings/{key}', [AdminSettingsController::class, 'setSetting'])->name('settings.set');
        Route::post('settings/batch', [AdminSettingsController::class, 'setManySettings'])->name('settings.setMany');
        Route::delete('settings/{key}', [AdminSettingsController::class, 'deleteSetting'])->name('settings.delete');
        Route::delete('settings/group/{group}', [AdminSettingsController::class, 'deleteGroup'])->name('settings.deleteGroup');

        // Users
        Route::get('users', [AdminUserController::class, 'getAllUsers'])->name('users.getAll');
        Route::get('users/{id}', [AdminUserController::class, 'getUserById'])->name('users.getById');
        Route::post('users', [AdminUserController::class, 'createUser'])->name('users.create');
        Route::put('users/{id}', [AdminUserController::class, 'updateUser'])->name('users.update');
        Route::delete('users/{id}', [AdminUserController::class, 'deleteUser'])->name('users.delete');
    });