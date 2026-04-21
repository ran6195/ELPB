<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'page_id',
        'name',
        'email',
        'phone',
        'message',
        'privacy_accepted',
        'page_published',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'privacy_accepted' => 'boolean',
        'page_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
