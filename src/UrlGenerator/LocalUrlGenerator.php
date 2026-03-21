<?php

declare(strict_types=1);

namespace Brackets\Media\UrlGenerator;

use Illuminate\Contracts\Routing\UrlGenerator;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator as SpatieUrlGenerator;

final class LocalUrlGenerator extends SpatieUrlGenerator
{
    #[\Override]
    public function getUrl(): string
    {
        if ($this->media->disk !== 'media_private') {
            return parent::getUrl();
        }

        $url = $this->getPathRelativeToRoot();

        $urlGenerator = app(UrlGenerator::class);

        return $urlGenerator->route('brackets/media::view', [], false)
            . '?path=' . $this->makeCompatibleForNonUnixHosts($url);
    }

    private function makeCompatibleForNonUnixHosts(string $url): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
        }

        return $url;
    }
}
