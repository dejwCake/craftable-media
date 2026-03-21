<?php

declare(strict_types=1);

namespace Brackets\Media\Tests\Feature\Http\Controllers;

use Brackets\Media\Tests\TestCase;
use Illuminate\Http\UploadedFile;

final class FileUploadControllerTest extends TestCase
{
    public function testAUserCanUploadFile(): void
    {
        $this->disableAuthorization();
        $data = [
            'name' => 'test',
            'path' => $this->getTestFilesDirectory('test.psd'),
        ];
        $file = new UploadedFile($data['path'], $data['name'], 'image/jpeg', null, true);
        $response = $this->call('POST', 'upload', $data, [], ['file' => $file]);

        $response->assertStatus(200);
        $response->assertSee('psd');
    }

    public function testUnauthenticatedUserCannotUploadFile(): void
    {
        $response = $this->postJson('upload', []);

        $response->assertStatus(401);
    }

    public function testUploadWithoutFileReturnsError(): void
    {
        $this->disableAuthorization();
        $response = $this->postJson('upload', []);

        $response->assertStatus(422);
    }
}
