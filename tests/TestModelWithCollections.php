<?php

declare(strict_types=1);

namespace Brackets\Media\Tests;

use Brackets\Media\HasMedia\AutoProcessMediaTrait;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TestModelWithCollections extends TestModel
{
    use AutoProcessMediaTrait;

    /**
     * Media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->maxNumberOfFiles(20)
            ->maxFilesize(2 * 1024 * 1024)
            ->accepts('image/*');

        $this->addMediaCollection('documents')
            ->private()
            ->canView('vop.view')
            ->canUpload('vop.upload')
            ->maxNumberOfFiles(20)
            ->maxFilesize(2 * 1024 * 1024)
            ->accepts('application/pdf', 'application/msword');

        $this->addMediaCollection('zip')
            ->private()
            ->canView('vop.view')
            ->canUpload('vop.upload')
            ->maxNumberOfFiles(20)
            ->maxFilesize(2 * 1024 * 1024)
            ->accepts('application/octet-stream');
    }

    /**
     * Register the conversions that should be performed.
     *
     * @throws InvalidManipulation
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->autoRegisterThumb200();

        $this->addMediaConversion('thumb')
            ->performOnCollections('gallery')
            ->width(368)
            ->height(232)
            ->sharpen(10)
            ->optimize();
    }
}
