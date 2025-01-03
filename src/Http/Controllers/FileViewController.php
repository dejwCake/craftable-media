<?php

declare(strict_types=1);

namespace Brackets\Media\Http\Controllers;

use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;

use function assert;

class FileViewController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws FilesystemException
     */
    public function view(Request $request): ?Response
    {
        $this->validate($request, [
            'path' => 'required|string',
        ]);

        [$fileId] = explode('/', $request->get('path'), 2);

        $medium = app(MediaModel::class)->find($fileId);
        if ($medium !== null) {
            $model = $medium->model;
            // PHPStorm sees it as an error - Spatie should fix this using PHPDoc
            assert($model instanceof HasMediaCollectionsTrait);

            $mediaCollection = $model->getMediaCollection($medium->collection_name);
            if ($mediaCollection !== null) {
                if ($mediaCollection->getViewPermission()) {
                    $this->authorize($mediaCollection->getViewPermission(), [$model]);
                }

                $storagePath = $request->get('path');
                $fileSystem = Storage::disk($mediaCollection->getDisk());

                if (! $fileSystem->exists($storagePath)) {
                    abort(404);
                }

                return ResponseFacade::make($fileSystem->get($storagePath), 200, [
                    'Content-Type' => $fileSystem->mimeType($storagePath),
                    'Content-Disposition' => 'inline; filename="' . basename($request->get('path')) . '"',
                ]);
            }
        }

        abort(404);
    }
}
