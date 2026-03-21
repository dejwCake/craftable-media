<?php

declare(strict_types=1);

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

final class FileIsTooBig extends FileCannotBeAdded
{
    public static function create(string $file, float $maxSize, string $collectionName): self
    {
        $actualFileSize = filesize($file);

        return new self(trans(
            'brackets/media::media.exceptions.file_is_too_big',
            ['actualFileSize' => $actualFileSize, 'collectionName' => $collectionName, 'maxSize' => $maxSize],
        ));
    }
}
