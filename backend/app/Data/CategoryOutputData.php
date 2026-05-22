<?php
// app/Data/CategoryOutputData.php

namespace App\Data;

use ApiPlatform\Metadata\ApiProperty;

class CategoryOutputData
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly int $id,
        
        #[ApiProperty(description: 'Category type')]
        public readonly string $type,
        
        #[ApiProperty(description: 'Category name')]
        public readonly string $name,
        
        #[ApiProperty(description: 'Category description')]
        public readonly ?string $description,
        
        #[ApiProperty(description: 'Category photo URL')]
        public readonly ?string $photo,
        
        #[ApiProperty(description: 'Full photo URL')]
        public readonly ?string $photoUrl,
        
        #[ApiProperty(description: 'Type label in French')]
        public readonly string $typeLabel,
        
        #[ApiProperty(description: 'Number of products in this category')]
        public readonly int $productCount,
        
        #[ApiProperty(description: 'Creation date')]
        public readonly \DateTimeInterface $createdAt,
        
        #[ApiProperty(description: 'Last update date')]
        public readonly \DateTimeInterface $updatedAt
    ) {}
}
