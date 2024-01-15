<?php

namespace App\Models\Panel;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory , Sluggable;

    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'slug_en',
        'summary',
        'summary_en',
        'content',
        'content_en',
        'multi_image',
        'multi_image_en',
        'video_url',
        'video_url_en',
        'price',
        'price_en',
        'status_price',
        'user_id'
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['title', 'slug']
            ]
        ];
    }
    protected $casts = [
        'multi_image'=> 'array',
        'multi_image_en'=> 'array',
        'video_url'=> 'array',
        'video_url_en'=> 'array',
    ];
    const STATUS_PRICE_DISABLE = 'disable';
    const STATUS_PRICE_ANSIBLE = 'ansible';

    static $status_price = [
        self::STATUS_PRICE_DISABLE,
        self::STATUS_PRICE_ANSIBLE
    ];

}
