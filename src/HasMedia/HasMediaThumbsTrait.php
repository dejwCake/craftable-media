<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Illuminate\Support\Collection;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\ConversionCollection;

/**
 * @property-read bool $autoProcessMedia
 */
trait HasMediaThumbsTrait
{
    public function getThumbs200ForCollection(string $mediaCollectionName): Collection
    {
        $mediaCollection = $this->getMediaCollection($mediaCollectionName);

        return $this->getMedia($mediaCollectionName)->filter(
            static function ($medium) use ($mediaCollectionName, $mediaCollection) {
                //We also want all files (PDF, Word, Excell etc.)
                if (!$mediaCollection->isImage()) {
                    return true;
                }

                return ConversionCollection::createForMedia(
                    $medium,
                )->filter(static fn ($conversion) => $conversion->shouldBePerformedOn($mediaCollectionName))->filter(
                    static fn ($conversion) => $conversion->getName() === 'thumb_200',
                )->count() > 0;
            },
        )->map(static fn ($medium) => [
                'id' => $medium->id,
                'url' => $medium->getUrl(),
                'thumb_url' => $mediaCollection->isImage() ? $medium->getUrl('thumb_200') : $medium->getUrl(),
                'type' => $medium->mime_type,
                'mediaCollection' => $mediaCollection->getName(),
                'name' => $medium->hasCustomProperty('name') ? $medium->getCustomProperty('name') : $medium->file_name,
                'size' => $medium->size,
            ]);
    }

    /**
     * Register thumb with size 200x200 fot all media collections
     */
    public function autoRegisterThumb200(): void
    {
        $this->getMediaCollections()->filter(
            static fn (MediaCollection $mediaCollection) => $mediaCollection->isImage(),
        )->each(
            function (MediaCollection $mediaCollection): void {
                $this->addMediaConversion('thumb_200')
                    ->performOnCollections($mediaCollection->getName())
                    ->width(200)
                    ->height(200)
                    ->fit(Fit::Crop, 200, 200)
                    ->optimize();
            },
        );
    }
}
