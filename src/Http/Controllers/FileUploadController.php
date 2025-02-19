<?php

declare(strict_types=1);

namespace Brackets\Media\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

final class FileUploadController extends BaseController
{
    /**
     * @throws AuthorizationException
     */
    public function upload(Request $request, Gate $gate): JsonResponse
    {
        $gate->authorize('admin.upload');

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('', ['disk' => 'uploads']);

            return new JsonResponse(['path' => $path]);
        }

        return new JsonResponse(trans('brackets/media::media.file.not_provided'), 422);
    }
}
