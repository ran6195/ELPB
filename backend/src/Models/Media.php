<?php

namespace App\Models;

use App\Storage\MediaStorageFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'disk',
        'path',
        'thumbnail_path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'alt_text',
        'parent_id',
        'legacy'
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'legacy' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // URL calcolati dal driver di storage, inclusi nella risposta JSON
    protected $appends = ['url', 'thumbnail_url'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Media::class, 'parent_id');
    }

    public function versions()
    {
        return $this->hasMany(Media::class, 'parent_id');
    }

    public function getUrlAttribute(): string
    {
        return MediaStorageFactory::disk($this->disk)->url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return MediaStorageFactory::disk($this->disk)->url($this->thumbnail_path);
    }

    /**
     * Cartella radice del proprietario: media/c{company_id} oppure media/u{user_id}
     */
    public static function ownerFolder(User $user): string
    {
        return $user->company_id
            ? 'media/c' . $user->company_id
            : 'media/u' . $user->id;
    }

    /**
     * Visibilità coerente con canViewPage: admin tutto, company la sua azienda,
     * user la sua azienda (o solo i propri se senza azienda).
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }
        return $query->where('user_id', $user->id);
    }
}
