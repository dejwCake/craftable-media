<?php

declare(strict_types=1);

namespace Brackets\Media\Exceptions\Collections;

use Exception;

class ThumbsDoesNotExists extends Exception
{
    public static function thumbsConversionNotFound(): self
    {
        return new self(trans('brackets/media::media.exceptions.thumbs_does_not_exists'));
    }
}
