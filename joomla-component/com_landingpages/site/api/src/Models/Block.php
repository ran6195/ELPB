<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'blocks';

    protected $fillable = [
        'page_id',
        'type',
        'content',
        'styles',
        'order',
        'position'
    ];

    protected $casts = [
        'content' => 'array',
        'styles' => 'array',
        'position' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
