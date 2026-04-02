<?php

declare(strict_types=1);

namespace Brackets\Media\ViewComposers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\View;

final readonly class UploadUrlComposer
{
    public function __construct(private UrlGenerator $urlGenerator)
    {
    }

    public function compose(View $view): void
    {
        $view->with('mediaUploadUrl', $this->urlGenerator->route('brackets/media::upload'));
    }
}
