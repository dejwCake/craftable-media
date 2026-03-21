<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\HasMedia;

use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Brackets\Media\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MimeTypeNotAllowed;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;

final class ProcessMediaTraitTest extends TestCase
{
    public function testProcessMediaAddsFilesToMultipleCollections(): void
    {
        $this->testModel->addMediaCollection('documents');
        $this->testModel->addMediaCollection('video');

        self::assertCount(2, $this->testModel->getMediaCollections());
        self::assertCount(0, $this->testModel->getMedia());

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.docx',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
            ],
            'video' => [
                [
                    'collection_name' => 'video',
                    'path' => 'test.zip',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'video test',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(2, $this->testModel->getMedia('documents'));
        $firstMedia = $this->testModel->getMedia('documents')->first();
        self::assertStringStartsWith('application/pdf', $firstMedia->mime_type);
    }

    public function testUserCanRegisterNewFileCollectionAndUploadFiles(): void
    {
        $this->testModel->addMediaCollection('documents');

        self::assertCount(1, $this->testModel->getMediaCollections());
        self::assertCount(0, $this->testModel->getMedia());

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.docx',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(2, $this->testModel->getMedia('documents'));
        $firstMedia = $this->testModel->getMedia('documents')->first();
        self::assertStringStartsWith('application/pdf', $firstMedia->mime_type);
    }

    public function testUserCanDeleteFileFromCollection(): void
    {
        $this->testModel->addMediaCollection('documents')
            ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.txt',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 2',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();
        $media = $this->testModel->getMedia('documents');
        self::assertCount(2, $media);

        $request = $this->getRequest([
            'documents' => [
                [
                    'id' => $media->first()->id,
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'delete',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
            ],
        ]);

        $this->testModel->addMediaCollection('documents')
            ->maxNumberOfFiles(2);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();
        $media = $this->testModel->getMedia('documents');
        self::assertCount(1, $media);
        self::assertEquals('test.txt', $media->first()->file_name);
    }

    public function testUserCannotUploadNotAllowedFileTypes(): void
    {
        $this->expectException(MimeTypeNotAllowed::class);

        $this->testModel->addMediaCollection('documents')
                        ->accepts('application/pdf', 'application/msword');

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.psd',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(0, $this->testModel->getMedia('documents'));
    }

    public function testMultipleAllowedMimeTypesCanBeDefined(): void
    {
        $this->testModel->addMediaCollection('documents')
                        ->accepts('application/pdf', 'application/msword');

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(1, $this->testModel->getMedia('documents'));
    }

    public function testUserCannotUploadMoreFilesThanAllowed(): void
    {
        $this->expectException(TooManyFiles::class);

        $this->testModel->addMediaCollection('documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.txt',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 2',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.docx',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 3',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(0, $this->testModel->getMedia('documents'));
    }

    public function testUserCannotUploadMoreFilesThanIsAllowedInMultipleRequests(): void
    {
        $this->expectException(TooManyFiles::class);

        $this->testModel->addMediaCollection('documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.txt',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 2',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();
        // let's be sure we arranged this test correctly (so this is not a real test assertion)
        self::assertCount(0, $this->testModel->getMediaCollections());

        $this->testModel->addMediaCollection('documents')
                        ->maxNumberOfFiles(2);

        $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.docx',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 3',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        // finally we can assert
        self::assertCount(2, $this->testModel->getMedia('documents'));
    }

    public function testUserCanUploadExactNumberOfDefinedFiles(): void
    {
        $this->testModel->addMediaCollection('documents')
                        ->maxNumberOfFiles(2);

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
                [
                    'collection_name' => 'documents',
                    'path' => 'test.txt',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 2',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(2, $this->testModel->getMedia('documents'));
    }

    public function testUserCannotUploadFileExceedingMaxFileSize(): void
    {
        $this->expectException(FileIsTooBig::class);

        $this->testModel->addMediaCollection('documents')
                        //100kb
                        ->maxFileSize(100 * 1024);


        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.psd',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(0, $this->testModel->getMedia('documents'));
    }

    public function testUserCanUploadFilesInMaxFileSize(): void
    {
        $this->testModel->addMediaCollection('documents')
                        //1kb
                        ->maxFileSize(1 * 1024);

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.txt',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        self::assertCount(1, $this->testModel->getMedia('documents'));
    }

    public function testProcessMediumUpdatesCustomProperties(): void
    {
        $this->testModel->addMediaCollection('documents');

        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'original name',
                    ],
                ],
            ],
        ]);

        $this->testModel->processMedia(
            new Collection($request->only($this->testModel->getMediaCollections()->map->getName()->toArray())),
        );
        $this->testModel = $this->testModel->fresh();

        $media = $this->testModel->getMedia('documents');
        self::assertCount(1, $media);

        $this->testModel->addMediaCollection('documents');
        $mediaCollection = $this->testModel->getCustomMediaCollection('documents');

        $this->testModel->processMedium([
            'id' => $media->first()->id,
            'collection_name' => 'documents',
            'meta_data' => [
                'name' => 'updated name',
            ],
        ], $mediaCollection);

        $updatedMedia = MediaModel::find($media->first()->id);
        self::assertSame('updated name', $updatedMedia->getCustomProperty('name'));
    }

    public function testProcessMediumIgnoresNonExistentMediaId(): void
    {
        $this->testModel->addMediaCollection('documents');

        $mediaCollection = $this->testModel->getCustomMediaCollection('documents');

        $this->testModel->processMedium([
            'id' => 99999,
            'collection_name' => 'documents',
            'action' => 'delete',
            'meta_data' => [
                'name' => 'test',
            ],
        ], $mediaCollection);

        // No exception thrown - test passes if we get here
        self::assertCount(0, $this->testModel->getMedia('documents'));
    }

    private function getRequest(array $data): Request
    {
        return Request::create('test', 'GET', $data);
    }
}
