<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'is_published',
        'styles'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'styles' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function blocks()
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }
}
