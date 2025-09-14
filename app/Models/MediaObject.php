<?php
// 1. Créer app/Models/MediaObject.php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\MediaObjectProcessor;
use App\Data\MediaObjectData;
use App\Data\MediaObjectOutputData;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/media_objects'
        ),
        new Get(
            uriTemplate: '/media_objects/{id}'
        ),
        new Post(
            uriTemplate: '/media_objects',
            processor: MediaObjectProcessor::class,
            input: MediaObjectData::class,
            output: MediaObjectOutputData::class,
            deserialize: false // Important pour gérer FormData
        )
    ]
)]
class MediaObject extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'mime_type'
    ];

    public function getContentUrlAttribute(): string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : '';
    }
}
