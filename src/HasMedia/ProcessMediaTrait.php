<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig as SpatieFileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MimeTypeNotAllowed;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;

/**
 * @property-read bool $autoProcessMedia
 */
trait ProcessMediaTrait
{
    /**
     * Attaches and/or detaches all defined media collection to the model according to the $media
     *
     * This method process data from structure:
     *
     * $request = [
     *      ...
     *      'collectionName' => [
     *          [
     *              'id' => null,
     *              'collection_name' => 'collectionName',
     *              'path' => 'test.pdf',
     *              'action' => 'add',
     *              'meta_data' => [
     *                  'name' => 'test',
     *                  'width' => 200,
     *                  'height' => 200,
     *              ],
     *          ],
     *      ],
     *      ...
     * ];
     *
     * Firstly it validates input for max files count for mediaCollection, ile mimetype and file size, amd if the
     * validation passes it will add/change/delete media object to model
     */
    public function processMedia(Collection $inputMedia): void
    {
        //Don't we want to use maybe some class to represent the data structure?
        //Maybe what we want is a MediumOperation class, which holds
        //{collection name, operation (detach, attach, replace), metadata, filepath)} what do you think?

        //First validate input
        $this->getMediaCollections()->each(function ($mediaCollection) use ($inputMedia): void {
            $this->validate(new Collection($inputMedia->get($mediaCollection->getName())), $mediaCollection);
        });

        //Then process each media
        $this->getMediaCollections()->each(function ($mediaCollection) use ($inputMedia): void {
            (new Collection($inputMedia->get($mediaCollection->getName())))->each(function ($inputMedium) use (
                $mediaCollection,
            ): void {
                $this->processMedium($inputMedium, $mediaCollection);
            });
        });
    }

    /**
     * Process single file metadata add/edit/delete to media library
     *
     * @throws FileDoesNotExist
     * @throws SpatieFileIsTooBig
     */
    public function processMedium(array $inputMedium, MediaCollection $mediaCollection): void
    {
        if (isset($inputMedium['id']) && $inputMedium['id']) {
            $medium = app(MediaModel::class)->find($inputMedium['id']);
            if ($medium !== null) {
                if (isset($inputMedium['action']) && $inputMedium['action'] === 'delete') {
                    $medium->delete();
                } else {
                    $medium->custom_properties = $inputMedium['meta_data'];
                    $medium->save();
                }
            }
        } elseif (isset($inputMedium['action']) && $inputMedium['action'] === 'add') {
            $mediumFileFullPath = Storage::disk('uploads')->path($inputMedium['path']);

            $this->addMedia($mediumFileFullPath)
                ->withCustomProperties($inputMedium['meta_data'])
                ->toMediaCollection($mediaCollection->getName(), $mediaCollection->getDisk());
        }
    }

    /**
     * Validate input data for media
     *
     * @throws FileCannotBeAdded
     */
    public function validate(Collection $inputMediaForMediaCollection, MediaCollection $mediaCollection): void
    {
        $this->validateCollectionMediaCount($inputMediaForMediaCollection, $mediaCollection);
        $inputMediaForMediaCollection->each(function ($inputMedium) use ($mediaCollection): void {
            if ($inputMedium['action'] === 'add') {
                $mediumFileFullPath = Storage::disk('uploads')->path($inputMedium['path']);
                $this->validateTypeOfFile($mediumFileFullPath, $mediaCollection);
                $this->validateSize($mediumFileFullPath, $mediaCollection);
            }
        });
    }

    /**
     * Validate uploaded files count in collection
     *
     * @throws TooManyFiles
     */
    public function validateCollectionMediaCount(
        Collection $inputMediaForMediaCollection,
        MediaCollection $mediaCollection,
    ): void {
        if ($mediaCollection->getMaxNumberOfFiles()) {
            $alreadyUploadedMediaCount = $this->getMedia($mediaCollection->getName())->count();
            $forAddMediaCount = $inputMediaForMediaCollection->filter(
                static fn ($medium) => $medium['action'] === 'add',
            )->count();
            $forDeleteMediaCount = $inputMediaForMediaCollection->filter(
                static fn ($medium) => $medium['action'] === 'delete' ? 1 : 0,
            )->count();
            $afterUploadCount = $forAddMediaCount + $alreadyUploadedMediaCount - $forDeleteMediaCount;

            if ($afterUploadCount > $mediaCollection->getMaxNumberOfFiles()) {
                throw TooManyFiles::create($mediaCollection->getMaxNumberOfFiles(), $mediaCollection->getName());
            }
        }
    }

    /**
     * Validate uploaded file mime type
     *
     * @throws MimeTypeNotAllowed
     */
    public function validateTypeOfFile(string $mediumFileFullPath, MediaCollection $mediaCollection): void
    {
        if ($mediaCollection->getAcceptedFileTypes()) {
            $this->guardAgainstInvalidMimeType($mediumFileFullPath, $mediaCollection->getAcceptedFileTypes());
        }
    }

    /**
     * Validate uploaded file size
     *
     * @throws FileIsTooBig
     */
    public function validateSize(string $mediumFileFullPath, MediaCollection $mediaCollection): void
    {
        if ($mediaCollection->getMaxFileSize()) {
            $this->guardAgainstFileSizeLimit(
                $mediumFileFullPath,
                $mediaCollection->getMaxFileSize(),
                $mediaCollection->getName(),
            );
        }
    }

    /**
     * maybe this could be PR to spatie/laravel-medialibrary
     *
     * @throws FileIsTooBig
     */
    protected function guardAgainstFileSizeLimit(string $filePath, float $maxFileSize, string $name): void
    {
        $validation = Validator::make(
            ['file' => new File($filePath)],
            ['file' => 'max:' . round($maxFileSize / 1024)],
        );

        if ($validation->fails()) {
            throw FileIsTooBig::create($filePath, $maxFileSize, $name);
        }
    }
}
