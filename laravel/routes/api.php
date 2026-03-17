<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoint (no authentication required)
Route::get('/health', HealthController::class)->name('api.health');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Sanctum)
|--------------------------------------------------------------------------
*/

// GET /api/user - Get authenticated user
// POST /api/login - Login
// POST /api/logout - Logout

/*
|--------------------------------------------------------------------------
| Public Content Routes
|--------------------------------------------------------------------------
*/

// Articles
Route::apiResource('articles', \App\Infrastructure\Http\Controllers\Api\ArticleController::class);
Route::get('articles/{slug}', [\App\Infrastructure\Http\Controllers\Api\ArticleController::class, 'show'])->name('api.articles.show');

// Categories
Route::apiResource('categories', \App\Infrastructure\Http\Controllers\Api\CategoryController::class);

// Tags
Route::apiResource('tags', \App\Infrastructure\Http\Controllers\Api\TagController::class);

// Settings (public)
Route::get('settings', [\App\Infrastructure\Http\Controllers\Api\SettingsController::class, 'index'])->name('api.settings.index');

// Contact form
Route::post('contact', [\App\Infrastructure\Http\Controllers\Api\ContactController::class, 'store'])->name('api.contact.store');
