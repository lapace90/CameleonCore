<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taggable extends Model
{
    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type'
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable()
    {
        return $this->morphTo();
    }
    public function scopeOfTag($query, $tagId)
    {
        return $query->where('tag_id', $tagId);
    }
    public function scopeOfTaggable($query, $taggableId, $taggableType)
    {
        return $query->where('taggable_id', $taggableId)
                     ->where('taggable_type', $taggableType);
    }
}
