<?php

declare(strict_types=1);

use Brackets\Media\Http\Controllers\FileUploadController;
use Brackets\Media\Http\Controllers\FileViewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:' . config('admin-auth.defaults.guard')])
    ->group(static function (): void {
        Route::post('upload', [FileUploadController::class, 'upload'])
            ->name('brackets/media::upload');
        Route::get('view', [FileViewController::class, 'view'])
            ->name('brackets/media::view');
    });
