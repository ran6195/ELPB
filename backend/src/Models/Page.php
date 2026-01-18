<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'is_published',
        'styles',
        'recaptcha_settings',
        'tracking_settings',
        'quick_contacts',
        'company_id',
        'user_id'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'styles' => 'array',
        'recaptcha_settings' => 'array',
        'tracking_settings' => 'array',
        'quick_contacts' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Override toArray to include camelCase quickContacts for frontend compatibility
     */
    public function toArray()
    {
        $array = parent::toArray();
        // Add camelCase version for frontend (use array_key_exists to handle null values)
        if (array_key_exists('quick_contacts', $array)) {
            $array['quickContacts'] = $array['quick_contacts'];
        }
        return $array;
    }

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
