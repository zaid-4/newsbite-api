<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'source_id',
        'title',
        'slug',
        'description',
        'source_url',
        'thumbnail_url',
        'published_at',
        'author',
    ];

    public static function booted()
    {
        static::addGlobalScope('user_preferences', function (Builder $builder) {
            if (auth()->user() && auth()->user()->preferences) {
                $preferences = json_decode(auth()->user()->preferences, true);
                if (sizeOf($preferences['favorite_sources']))
                    $builder->whereIn('source_id', $preferences['favorite_sources']);
                if (sizeOf($preferences['favorite_categories']))
                    $builder->WhereIn('category_id', $preferences['favorite_categories']);
                if (sizeOf($preferences['favorite_authors']))
                    $builder->orWhereIn('author', $preferences['favorite_authors']);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
