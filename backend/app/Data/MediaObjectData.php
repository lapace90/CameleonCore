<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MediaObjectData extends Data
{
    public function __construct(
        public ?string $file_name = null,
        public ?string $file_path = null,
        public ?int $file_size = null,
        public ?string $mime_type = null,
    ) {}
}