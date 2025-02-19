<?php

declare(strict_types=1);

namespace Brackets\Media\HasMedia;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\MediaCollection as ParentMediaCollection;

class MediaCollection extends ParentMediaCollection
{
    protected bool $isImage = false;

    protected ?int $maxNumberOfFiles = null;

    protected int $maxFileSize;

    /** @var array<string>|null */
    protected ?array $acceptedFileTypes = null;

    protected ?string $viewPermission = null;

    protected ?string $uploadPermission = null;

    private readonly Config $config;

    /**
     * MediaCollection constructor.
     */
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->config = app(Config::class);
        $this->diskName = $this->config->get('media-collections.public_disk', 'media');
        $this->maxFileSize = $this->config->get('media-library.max_file_size', 1024 * 1024 * 10);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public static function create($name): self
    {
        return new self($name);
    }

    /**
     * Specify a disk where to store this collection
     *
     * @deprecated deprecated since version 3.0, remove in version 4.0
     */
    public function disk(string $disk): self
    {
        $this->diskName = $disk;

        return $this;
    }

    /**
     * Alias to setting default private disk
     *
     * @return $this
     */
    public function private(): self
    {
        $this->diskName = $this->config->get('media-collections.private_disk');

        return $this;
    }

    /**
     * Set the file count limit
     *
     * @return $this
     */
    public function maxNumberOfFiles(int $maxNumberOfFiles): self
    {
        $this->maxNumberOfFiles = $maxNumberOfFiles;

        return $this;
    }

    /**
     * Set the file size limit
     *
     * @return $this
     */
    public function maxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * Set the accepted file types (in MIME type format)
     *
     * @return $this
     */
    public function accepts(string ...$acceptedFileTypes): self
    {
        $this->acceptedFileTypes = $acceptedFileTypes;
        if ((new Collection($this->acceptedFileTypes))->count() > 0) {
            $this->isImage = (new Collection($this->acceptedFileTypes))->reject(
                static fn ($fileType) => str_starts_with($fileType, 'image'),
            )->count() === 0;
        }

        return $this;
    }

    /**
     * Set the ability (Gate) which is required to view the medium
     *
     * In most cases you would want to call private() to use default private disk.
     *
     * Otherwise, you may use other private disk for your own. Just be sure, your file is not accessible
     *
     * @return $this
     */
    public function canView(string $viewPermission): self
    {
        $this->viewPermission = $viewPermission;

        return $this;
    }

    /**
     * Set the ability (Gate) which is required to upload & attach new files to the model
     *
     * @return $this
     */
    public function canUpload(string $uploadPermission): self
    {
        $this->uploadPermission = $uploadPermission;

        return $this;
    }

    public function isImage(): bool
    {
        return $this->isImage;
    }

    //FIXME: metoda disk by mohla mat druhy nepovinny paramater private,
    // ktory len nastavi interny flag na true. Aby sme vedeli presnejsie ci ide o private alebo nie
    public function isPrivate(): bool
    {
        return $this->diskName === $this->config->get('media-collections.private_disk');
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDisk(): ?string
    {
        return $this->diskName;
    }

    public function getMaxNumberOfFiles(): ?int
    {
        return $this->maxNumberOfFiles;
    }

    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    /**
     * @return array|null
     */
    public function getAcceptedFileTypes(): ?array
    {
        return $this->acceptedFileTypes;
    }

    public function getViewPermission(): ?string
    {
        return $this->viewPermission;
    }

    public function getUploadPermission(): ?string
    {
        return $this->uploadPermission;
    }
}
