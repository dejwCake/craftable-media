<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\HasMedia;

use Brackets\Media\Tests\TestCase;

final class AutoProcessMediaTraitTest extends TestCase
{
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

        $media = $this->app->make('db')->connection()->table('media')->first();

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

        self::assertEmpty($this->app->make('db')->connection()->table('media')->first());
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
}
