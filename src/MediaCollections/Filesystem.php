<?php

declare(strict_types=1);

namespace Brackets\Media\MediaCollections;

use Spatie\MediaLibrary\MediaCollections\Filesystem as ParentFilesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Filesystem extends ParentFilesystem
{
    public function copyFromMediaLibrary(Media $media, string $targetFile): string
    {
        file_put_contents($targetFile, stream_get_contents($this->getStream($media)));

        return $targetFile;
    }
}
