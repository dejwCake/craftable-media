<?php

declare(strict_types=1);

namespace Brackets\Media\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class TooManyFiles extends FileCannotBeAdded
{
    public static function create(?int $maxFileCount = null, ?string $collectionName = null): self
    {
        return new self(trans(
            'brackets/media::media.exceptions.too_many_files',
            ['collectionName' => $collectionName, 'maxFileCount' => $maxFileCount],
        ));
    }
}
