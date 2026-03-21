<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\HasMedia;

use Brackets\Media\HasMedia\MediaCollection;
use Brackets\Media\Tests\TestCase;

final class HasMediaCollectionsTraitTest extends TestCase
{
    public function testEmptyCollectionReturnsLaravelCollection(): void
    {
        self::assertCount(0, $this->testModel->getMediaCollections());
    }

    public function testNotEmptyCollectionReturnsLaravelCollection(): void
    {
        self::assertCount(3, $this->testModelWithCollections->getMediaCollections());
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

    public function testGetCustomMediaCollectionReturnsNullForUnregistered(): void
    {
        self::assertNull($this->testModel->getCustomMediaCollection('nonexistent'));
    }

    public function testGetCustomMediaCollectionReturnsCollectionByName(): void
    {
        $collection = $this->testModelWithCollections->getCustomMediaCollection('gallery');

        self::assertInstanceOf(MediaCollection::class, $collection);
        self::assertSame('gallery', $collection->getName());
    }

    public function testAddMediaCollectionReturnsMediaCollection(): void
    {
        $collection = $this->testModel->addMediaCollection('test_collection');

        self::assertSame('test_collection', $collection->getName());
        self::assertCount(1, $this->testModel->getMediaCollections());
    }
}
