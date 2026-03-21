<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\HasMedia;

use Brackets\Media\HasMedia\MediaCollection;
use Brackets\Media\Tests\TestCase;

final class MediaCollectionTest extends TestCase
{
    public function testCreateReturnsMediaCollection(): void
    {
        $collection = MediaCollection::create('test');

        self::assertSame('test', $collection->getName());
    }

    public function testPrivateSetsPrivateDisk(): void
    {
        $collection = MediaCollection::create('test')->private();

        self::assertSame('media_private', $collection->getDisk());
    }

    public function testMaxNumberOfFilesSetsLimit(): void
    {
        $collection = MediaCollection::create('test')->maxNumberOfFiles(5);

        self::assertSame(5, $collection->getMaxNumberOfFiles());
    }

    public function testMaxFileSizeSetsLimit(): void
    {
        $collection = MediaCollection::create('test')->maxFileSize(2048);

        self::assertSame(2048, $collection->getMaxFileSize());
    }

    public function testAcceptsSetsFileTypes(): void
    {
        $collection = MediaCollection::create('test')->accepts('application/pdf', 'application/msword');

        self::assertSame(['application/pdf', 'application/msword'], $collection->getAcceptedFileTypes());
    }

    public function testAcceptsDetectsImageCollection(): void
    {
        $collection = MediaCollection::create('test')->accepts('image/jpeg', 'image/png');

        self::assertTrue($collection->isImage());
    }

    public function testAcceptsMixedTypesNotImage(): void
    {
        $collection = MediaCollection::create('test')->accepts('image/jpeg', 'application/pdf');

        self::assertFalse($collection->isImage());
    }

    public function testCanViewSetsPermission(): void
    {
        $collection = MediaCollection::create('test')->canView('media.view');

        self::assertSame('media.view', $collection->getViewPermission());
    }

    public function testCanUploadSetsPermission(): void
    {
        $collection = MediaCollection::create('test')->canUpload('media.upload');

        self::assertSame('media.upload', $collection->getUploadPermission());
    }

    public function testDefaultDiskIsPublicDisk(): void
    {
        $collection = MediaCollection::create('test');

        self::assertSame('media', $collection->getDisk());
    }

    public function testDefaultMaxFileSizeFromConfig(): void
    {
        $collection = MediaCollection::create('test');

        self::assertSame(1024 * 1024 * 10, $collection->getMaxFileSize());
    }
}
