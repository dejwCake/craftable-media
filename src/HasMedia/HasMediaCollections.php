<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Illuminate\Support\Collection;

interface HasMediaCollections
{
    public function addMediaCollection(string $name): MediaCollection;

    public function getMediaCollections(): Collection;

    public function getMediaCollection(string $name): ?MediaCollection;
}
