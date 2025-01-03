<?php

declare(strict_types=1);

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class FileIsTooBig extends FileCannotBeAdded
{
    public static function create(string $file, float $maxSize, string $collectionName): self
    {
        $actualFileSize = filesize($file);

        return new self(trans(
            'brackets/media::media.exceptions.thumbs_does_not_exists',
            ['actualFileSize' => $actualFileSize, 'collectionName' => $collectionName, 'maxSize' => $maxSize],
        ));
    }
}
