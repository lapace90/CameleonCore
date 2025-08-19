<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $type = null,
        public ?string $description = null,
        public ?string $photo = null,
    ) {}

    public static function fromModel(\App\Models\Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            type: $category->type,
            description: $category->description,
            photo: $category->photo,
        );
    }
}