<?php

declare(strict_types=1);

namespace Brackets\Media\Http\Controllers;

use Brackets\Media\HasMedia\HasMediaCollections;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function assert;

final class FileViewController extends BaseController
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws FilesystemException
     */
    public function view(Request $request, FilesystemManager $filesystemManager, Gate $gate): Response
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);
        $storagePath = $validated['path'];

        [$fileId] = explode('/', $storagePath, 2);

        $medium = MediaModel::find($fileId);
        if ($medium !== null) {
            $model = $medium->model;
            assert($model instanceof HasMediaCollections);

            $mediaCollection = $model->getMediaCollection($medium->collection_name);
            if ($mediaCollection !== null) {
                if ($mediaCollection->getViewPermission()) {
                    $gate->authorize($mediaCollection->getViewPermission(), [$model]);
                }

                $fileSystem = $filesystemManager->disk($mediaCollection->getDisk());

                if (!$fileSystem->exists($storagePath)) {
                    throw new NotFoundHttpException('File not found');
                }

                return new Response(
                    $fileSystem->get($storagePath),
                    200,
                    [
                        'Content-Type' => $fileSystem->mimeType($storagePath),
                        'Content-Disposition' => 'inline; filename="' . basename($request->get('path')) . '"',
                    ],
                );
            }
        }

        throw new NotFoundHttpException('Medium not found');
    }
}
