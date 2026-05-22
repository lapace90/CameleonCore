<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use App\Models\MediaObject;

class MediaObjectOutputData extends Data
{
    public function __construct(
        public int $id,
        public string $file_name,
        public string $content_url,
        public int $file_size,
        public string $mime_type,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromMediaObject(MediaObject $mediaObject): self
    {
        return new self(
            id: $mediaObject->id,
            file_name: $mediaObject->file_name,
            content_url: $mediaObject->content_url,
            file_size: $mediaObject->file_size,
            mime_type: $mediaObject->mime_type,
            created_at: $mediaObject->created_at->toISOString(),
            updated_at: $mediaObject->updated_at->toISOString(),
        );
    }
}