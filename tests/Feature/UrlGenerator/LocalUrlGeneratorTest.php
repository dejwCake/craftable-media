<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\UrlGenerator;

use Brackets\Media\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class LocalUrlGeneratorTest extends TestCase
{
    public function testGetUrlReturnsParentUrlForPublicDisk(): void
    {
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

        $url = $media->first()->getUrl();

        // Public disk URL should be a direct path (from parent), not contain the view route
        self::assertStringNotContainsString('view?path=', $url);
        self::assertStringContainsString('/media/', $url);
    }

    public function testGetUrlReturnsViewRouteForPrivateDisk(): void
    {
        $this->disableAuthorization();

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

        $media = $this->testModelWithCollections->getMedia('documents');
        self::assertCount(1, $media);

        $url = $media->first()->getUrl();

        // Private disk URL should use the view route with path parameter
        self::assertStringContainsString('view?path=', $url);
    }

    private function getRequest(array $data): Request
    {
        return Request::create('test', 'GET', $data);
    }
}
