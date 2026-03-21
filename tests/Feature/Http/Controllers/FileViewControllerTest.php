<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\Http\Controllers;

use Brackets\Media\Tests\TestCase;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class FileViewControllerTest extends TestCase
{
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

    public function testAuthorizedUserCanGetProtectedMedia(): void
    {
        $this->disableAuthorization();

        $gate = $this->app->make(Gate::class);
        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        $gate->define('vop.view', static fn ($user) => true);

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

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function testViewNonExistentMediaReturns404(): void
    {
        $this->disableAuthorization();

        $response = $this->json('GET', 'view', ['path' => '99999/nonexistent.pdf']);

        $response->assertStatus(404);
    }

    public function testViewRequiresPathParameter(): void
    {
        $this->disableAuthorization();

        $response = $this->json('GET', 'view');

        $response->assertStatus(422);
    }

    private function getRequest(array $data): Request
    {
        return Request::create('test', 'GET', $data);
    }
}
