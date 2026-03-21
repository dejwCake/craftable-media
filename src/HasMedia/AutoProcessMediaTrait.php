<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Illuminate\Support\Collection;

trait AutoProcessMediaTrait
{
    /**
     * Setup to auto process during saving
     */
    public static function bootAutoProcessMediaTrait(): void
    {
        static::saving(static function ($model): void {
            /** @var self $model */
            $model->processMedia(
                new Collection(request()->only($model->getMediaCollections()->map->getName()->toArray())),
            );
        });
    }
}
