<?php

namespace App\Actions\Media;

final readonly class MediaMigrationResult
{
    public const MIGRATED = 'migrated';

    public const SKIPPED = 'skipped';

    public const FAILED = 'failed';

    public function __construct(
        public string $status,
        public ?string $reason = null,
        public bool $localDeleted = false,
    ) {}

    public function isMigrated(): bool
    {
        return $this->status === self::MIGRATED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::SKIPPED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
