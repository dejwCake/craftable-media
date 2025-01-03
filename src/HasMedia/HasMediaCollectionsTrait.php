<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\InteractsWithMedia as ParentHasMediaTrait;

trait HasMediaCollectionsTrait
{
    use ParentHasMediaTrait;

    /**
     * Register new Media Collection
     *
     * Adds new collection to model and set its name.
     */
    public function addMediaCollection(string $name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->mediaCollections[] = $mediaCollection;

        return $mediaCollection;
    }

    /**
     * Returns a collection of Media Collections
     */
    public function getMediaCollections(): Collection
    {
        $this->registerMediaCollections();

        return (new Collection($this->mediaCollections))->keyBy('name');
    }

    /**
     * Returns a Media Collection according to the name
     *
     * If Media Collection was not registered on this model, null is returned
     */
    public function getMediaCollection(string $name): ?MediaCollection
    {
        return $this->getMediaCollections()->get($name);
    }
}
