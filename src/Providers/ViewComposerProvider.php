<?php

declare(strict_types=1);

namespace Brackets\Media\Providers;

use Brackets\Media\ViewComposers\UploadUrlComposer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider;

final class ViewComposerProvider extends ServiceProvider
{
    public function boot(): void
    {
        $viewFactory = $this->app->get(Factory::class);
        $viewFactory->composer('*', UploadUrlComposer::class);
    }

    public function register(): void
    {
        //do nothing
    }
}
