<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature;

use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;
use Brackets\Media\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MimeTypeNotAllowed;

class HasMediaCollectionsTest extends TestCase
{
    public function testEmptyCollectionReturnsLaravelCollection(): void
    {
        self::assertInstanceOf(Collection::class, $this->testModel->getMediaCollections());
    }

    public function testNotEmptyCollectionReturnsLaravelCollection(): void
    {
        self::assertInstanceOf(Collection::class, $this->testModelWithCollections->getMediaCollections());
    }

    public function testCheckMediaCollectionsCount(): void
    {
        self::assertCount(0, $this->testModel->getMediaCollections());
        self::assertCount(3, $this->testModelWithCollections->getMediaCollections());
    }

    public function testCheckImageMediaCollectionsCount(): void
    {
        self::assertCount(0, $this->testModel->getMediaCollections()->filter->isImage());
        self::assertCount(1, $this->testModelWithCollections->getMediaCollections()->filter->isImage());
    }

    public function testJustForDev(): void
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

    public function testMediaIsSavedAutomaticallyWhenModelIsSaved(): void
    {
        $response = $this->post('/test-model/create', [
            'name' => 'Test auto process',
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

        $response->assertStatus(201);

        $media = $this->app['db']->connection()->table('media')->first();

        self::assertStringStartsWith('test.pdf', $media->file_name);
        self::assertStringStartsWith('{"name":"test"}', $media->custom_properties);
    }

    public function testMediaIsNotSavedAutomaticallyWhileModelIsSavedIfThisFeatureIsDisabled(): void
    {
        $response = $this->post('/test-model-disabled/create', [
            'name' => 'Test auto process disabled',
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

        $response->assertStatus(201);

        self::assertEmpty($this->app['db']->connection()->table('media')->first());
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
        // TODO let's double-check that original two documents are attached (and not replaced by new one)
    }

    // FIXME this one is redundant, we already tested that in previous test, I think we can totally delete this one
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
                        ->maxFilesize(100 * 1024);


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
                        ->maxFilesize(1 * 1024);

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

    public function testNotAuthorizedUserCanGetPublicMedia(): void
    {
        self::assertCount(0, $this->testModelWithCollections->getMedia('gallery'));

        $request = $this->getRequest([
            'gallery' => [
                [
                    'collection_name' => 'gallery',
                    'path' => 'test.jpg',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                        'width' => 200,
                        'height' => 200,
                    ],
                ],
            ],
        ]);

        $this->testModelWithCollections->processMedia(
            new Collection(
                $request->only($this->testModelWithCollections->getMediaCollections()->map->getName()->toArray()),
            ),
        );
        $this->testModelWithCollections = $this->testModelWithCollections->fresh()->load('media');

        $media = $this->testModelWithCollections->getMedia('gallery');

        self::assertCount(1, $media);

        $response = $this->call('GET', $media->first()->getUrl());

        // let's assert that the access was not forbidden
        // (but as long as we don't have a real nginx serving the file, we cannot actually get the file
        self::assertNotEquals(403, $response->getStatusCode());
        // that's why we at least check if the final URL is correct
        // TODO
    }

    public function testNotAuthorizedUserCannotGetProtectedMedia(): void
    {
        $this->disableAuthorization();
        self::assertCount(0, $this->testModelWithCollections->getMedia('documents'));

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
             ],
        ]);

        $this->testModelWithCollections->processMedia(
            new Collection(
                $request->only($this->testModelWithCollections->getMediaCollections()->map->getName()->toArray()),
            ),
        );
        $this->testModelWithCollections = $this->testModelWithCollections->fresh();

        $media = $this->testModelWithCollections->getMedia('documents');

        self::assertCount(1, $media);

        $response = $this->json('GET', $media->first()->getUrl());

        $response->assertStatus(403);
    }

    public function testShouldSaveModelWithInAutoProcess(): void
    {
        $response = $this->post('/test-model/create', [
            'name' => 'Test small file',
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas(
            $this->testModelWithCollections->getTable(),
            ['id' => 2, 'name' => 'Test small file', 'width' => null],
        );
    }

    public function testShouldNotSaveModelIfMediaFailedInAutoProcess(): void
    {
        $response = $this->post('/test-model/create', [
            'name' => 'Test big file',
            'zip' => [
                [
                    'collection_name' => 'zip',
                    'path' => 'test.zip',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(500);

        $this->assertDatabaseMissing(
            $this->testModelWithCollections->getTable(),
            ['id' => 1, 'name' => 'Test big file', 'width' => null],
        );
    }

    //FIXME With spatie collection, you can have multiple collection with same name
//    /** @test */
//    public function model_cannot_have_multiple_collections_with_same_name()
//    {
//        $this->expectException(MediaCollectionAlreadyDefined::class);
//
//        $this->testModelWithCollections->addMediaCollection('documents');
//    }

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

    public function testUserCanGetThumbs(): void
    {
        $this->assertCount(0, $this->testModelWithCollections->getMedia('gallery'));

        $request = $this->getRequest([
            'gallery' => [
                [
                    'collection_name' => 'gallery',
                    'path' => 'test.jpg',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test 1',
                        'width' => 200,
                        'height' => 200,
                    ],
                ],
            ],
        ]);

        $this->testModelWithCollections->processMedia(
            new Collection(
                $request->only($this->testModelWithCollections->getMediaCollections()->map->getName()->toArray()),
            ),
        );
        $this->testModelWithCollections = $this->testModelWithCollections->fresh()->load('media');

        self::assertCount(1, $this->testModelWithCollections->getThumbs200ForCollection('gallery'));
    }

    public function testUserCanGetFileIfThumbsNotRegistered(): void
    {
        self::assertCount(0, $this->testModelWithCollections->getMedia('gallery'));

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
            ],
        ]);

        $this->testModelWithCollections->processMedia(
            new Collection(
                $request->only($this->testModelWithCollections->getMediaCollections()->map->getName()->toArray()),
            ),
        );
        $this->testModelWithCollections = $this->testModelWithCollections->fresh()->load('media');

        $this->assertCount(1, $this->testModelWithCollections->getThumbs200ForCollection('documents'));
    }

    public function testSystemAutomaticallyDetectsImageCollectionBasedOnMimeType(): void
    {
        self::assertCount(1, $this->testModelWithCollections->getMediaCollections()->filter->isImage());

        //collection without mimetype is not image
        $this->testModelWithCollections->addMediaCollection('without_mime_type')->accepts('');
        self::assertCount(1, $this->testModelWithCollections->getMediaCollections()->filter->isImage());

        //collection with only image mimetypes is image
        $this->testModelWithCollections->addMediaCollection('image_mime_type')->accepts('image/jpeg', 'image/png');
        self::assertCount(2, $this->testModelWithCollections->getMediaCollections()->filter->isImage());

        //collection with mixed mimetypes is not image
        $this->testModelWithCollections->addMediaCollection('mixed_mime_type')->accepts(
            'image/jpeg',
            'application/pdf',
            'application/msword',
        );
        self::assertCount(2, $this->testModelWithCollections->getMediaCollections()->filter->isImage());
    }

    private function getRequest(array $data): Request
    {
        return Request::create('test', 'GET', $data);
    }
}
