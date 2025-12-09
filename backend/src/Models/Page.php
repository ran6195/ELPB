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
        'styles',
        'recaptcha_settings',
        'company_id',
        'user_id'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'styles' => 'array',
        'recaptcha_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function blocks()
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
