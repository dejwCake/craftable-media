<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\HasMedia;

use Brackets\Media\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class HasMediaThumbsTraitTest extends TestCase
{
    public function testUserCanGetThumbs(): void
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

        self::assertCount(1, $this->testModelWithCollections->getThumbs200ForCollection('documents'));
    }

    public function testGetThumbs200ReturnsCorrectStructure(): void
    {
        $request = $this->getRequest([
            'documents' => [
                [
                    'collection_name' => 'documents',
                    'path' => 'test.pdf',
                    'action' => 'add',
                    'meta_data' => [
                        'name' => 'test document',
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

        $thumbs = $this->testModelWithCollections->getThumbs200ForCollection('documents');
        self::assertCount(1, $thumbs);

        $thumb = $thumbs->first();
        self::assertArrayHasKey('id', $thumb);
        self::assertArrayHasKey('url', $thumb);
        self::assertArrayHasKey('thumb_url', $thumb);
        self::assertArrayHasKey('type', $thumb);
        self::assertArrayHasKey('mediaCollection', $thumb);
        self::assertArrayHasKey('name', $thumb);
        self::assertArrayHasKey('size', $thumb);
        self::assertSame('documents', $thumb['mediaCollection']);
        self::assertSame('test document', $thumb['name']);
    }

    private function getRequest(array $data): Request
    {
        return Request::create('test', 'GET', $data);
    }
}
