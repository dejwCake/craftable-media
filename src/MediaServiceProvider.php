<?php

declare(strict_types=1);

namespace Brackets\Media;

use Brackets\Media\MediaCollections\Filesystem as FixedFilesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Filesystem;

class MediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'brackets/media');

        $router = app(Router::class);
        if ($router->hasMiddlewareGroup('admin')) {
            $router->middleware(['web', 'admin'])
                ->group(__DIR__ . '/../routes/web.php');
        } else {
            $router->middleware(['web'])
                ->group(__DIR__ . '/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    public function register(): void
    {
        //FIXME it would be nice if you could somehow publish into filesystems
        $this->mergeConfigFrom(__DIR__ . '/../config/filesystems.php', 'filesystems.disks');

        $this->mergeConfigFrom(__DIR__ . '/../config/media-collections.php', 'media-collections');

        $this->mergeConfigFrom(__DIR__ . '/../config/admin-auth.php', 'admin-auth.defaults');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.guard.admin.php', 'auth.guards.admin');

        $this->mergeConfigFrom(__DIR__ . '/../config/auth.providers.admin_users.php', 'auth.providers.admin_users');

        $this->app->bind(Filesystem::class, FixedFilesystem::class);
    }

    private function publish(): void
    {
        $this->publishes([
            __DIR__ . '/../config/media-collections.php' => $this->app->configPath('media-collections.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/brackets/media'),
        ], 'lang');
    }
}
