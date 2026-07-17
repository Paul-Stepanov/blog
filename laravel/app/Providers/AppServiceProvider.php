<?php

namespace App\Providers;

use App\Domain\Article\Repositories\ArticleRepositoryInterface;
use App\Domain\Article\Repositories\CategoryRepositoryInterface;
use App\Domain\Article\Repositories\TagRepositoryInterface;
use App\Domain\Contact\Repositories\ContactRepositoryInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Settings\Repositories\SettingsRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Cache\Repositories\CachedArticleRepository;
use App\Infrastructure\Persistence\Cache\Repositories\CachedCategoryRepository;
use App\Infrastructure\Persistence\Cache\Repositories\CachedSettingsRepository;
use App\Infrastructure\Persistence\Cache\Repositories\CachedTagRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentArticleRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContactRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentMediaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSettingsRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTagRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, EloquentContactRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, EloquentSettingsRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        $this->app->extend(
            ArticleRepositoryInterface::class,
            static function (ArticleRepositoryInterface $repository, $app): ArticleRepositoryInterface {
                return new CachedArticleRepository(
                    $repository,
                    $app->make(Repository::class)
                );
            }
        );

        $this->app->extend(
            SettingsRepositoryInterface::class,
            static function (SettingsRepositoryInterface $repository, $app): SettingsRepositoryInterface {
                return new CachedSettingsRepository($repository, $app->make(Repository::class));
            }
        );

        $this->app->extend(
            CategoryRepositoryInterface::class,
            static function (CategoryRepositoryInterface $repository, $app): CategoryRepositoryInterface {
                return new CachedCategoryRepository($repository, $app->make(Repository::class));
            }
        );

        $this->app->extend(
            TagRepositoryInterface::class,
            static function (TagRepositoryInterface $repository, $app): TagRepositoryInterface {
                return new CachedTagRepository($repository, $app->make(Repository::class));
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Per-account brute-force protection on admin login.
        // Keyed by email|ip so an attacker cannot bypass via IP rotation alone.
        RateLimiter::for('login', static function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->input('email').'|'.$request->ip());
        });
    }
}
