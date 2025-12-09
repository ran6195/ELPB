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
        'appointment_requested',
        'appointment_datetime',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'privacy_accepted' => 'boolean',
        'page_published' => 'boolean',
        'appointment_requested' => 'boolean',
        'appointment_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
