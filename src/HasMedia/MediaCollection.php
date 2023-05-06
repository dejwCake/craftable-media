<?php

namespace Brackets\Media\HasMedia;

use Spatie\MediaLibrary\MediaCollections\MediaCollection as ParentMediaCollection;

class MediaCollection extends ParentMediaCollection
{
    /** @var bool */
    protected bool $isImage = false;
    
    /** @var int|null */
    protected ?int $maxNumberOfFiles;
    
    /** @var int */
    protected int $maxFileSize;
    
    /** @var array|null */
    protected ?array $acceptedFileTypes;
    
    /** @var string|null */
    protected ?string $viewPermission;
    
    /** @var string|null */
    protected ?string $uploadPermission;

    /**
     * MediaCollection constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->diskName = config('media-collections.public_disk', 'media');
        $this->maxFileSize = config('media-library.max_file_size', 1024*1024*10);
    }


    /**
     * Specify a disk where to store this collection
     *
     * @param $disk
     * @return $this
     * @deprecated deprecated since version 3.0, remove in version 4.0
     */
    public function disk($disk): self
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
        $this->diskName = config('media-collections.private_disk');

        return $this;
    }

    /**
     * Set the file count limit
     *
     * @param int $maxNumberOfFiles
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
     * @param int $maxFileSize
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
     * @param array ...$acceptedFileTypes
     *
     * @return $this
     */
    public function accepts(...$acceptedFileTypes): self
    {
        $this->acceptedFileTypes = $acceptedFileTypes;
        if (collect($this->acceptedFileTypes)->count() > 0) {
            $this->isImage = collect($this->acceptedFileTypes)->reject(static function ($fileType) {
                return strpos($fileType, 'image') === 0;
            })->count() === 0;
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
     * @param string $viewPermission
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
     * @param string $uploadPermission
     *
     * @return $this
     */
    public function canUpload(string $uploadPermission): self
    {
        $this->uploadPermission = $uploadPermission;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->isImage;
    }

    //FIXME: metoda disk by mohla mat druhy nepovinny paramater private, ktory len nastavi interny flag na true. Aby sme vedeli presnejsie ci ide o private alebo nie
    public function isPrivate(): bool
    {
        return $this->diskName === config('media-collections.private_disk');
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDisk(): ?string
    {
        return $this->diskName;
    }

    /**
     * @return int|null
     */
    public function getMaxNumberOfFiles(): ?int
    {
        return $this->maxNumberOfFiles;
    }

    /**
     * @return int|null
     */
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

    /**
     * @return string|null
     */
    public function getViewPermission(): ?string
    {
        return $this->viewPermission;
    }

    /**
     * @return string|null
     */
    public function getUploadPermission(): ?string
    {
        return $this->uploadPermission;
    }
}
