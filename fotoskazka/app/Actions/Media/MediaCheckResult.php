<?php

namespace App\Actions\Media;

final readonly class MediaCheckResult
{
    public const VALID = 'valid';

    public const MISSING_ORIGINAL = 'missing_original';

    public const MISSING_THUMBNAIL = 'missing_thumbnail';

    public const MISSING_IMAGE_CACHE = 'missing_image_cache';

    public const METADATA_MISMATCH = 'metadata_mismatch';

    public const ERROR = 'error';

    public function __construct(
        public string $status,
        public ?string $detail = null,
    ) {}

    public function isValid(): bool
    {
        return $this->status === self::VALID;
    }

    public function isMissingOriginal(): bool
    {
        return $this->status === self::MISSING_ORIGINAL;
    }

    public function isMissingThumbnail(): bool
    {
        return $this->status === self::MISSING_THUMBNAIL;
    }

    public function isMissingImageCache(): bool
    {
        return $this->status === self::MISSING_IMAGE_CACHE;
    }

    public function isMetadataMismatch(): bool
    {
        return $this->status === self::METADATA_MISMATCH;
    }

    public function isError(): bool
    {
        return $this->status === self::ERROR;
    }
}
