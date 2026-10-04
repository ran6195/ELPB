<?php

namespace App\Models;

use App\Models\Concerns\OwnedByCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Cartella logica della libreria media: i file non vengono spostati su disco.
 */
class MediaFolder extends Model
{
    use OwnedByCompany;

    protected $table = 'media_folders';

    protected $fillable = [
        'company_id',
        'user_id',
        'parent_id',
        'name'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function parent()
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** true se $folderId è questa cartella o una sua discendente (evita cicli negli spostamenti) */
    public function containsFolder(int $folderId): bool
    {
        $current = MediaFolder::find($folderId);
        $guard = 0;
        while ($current && $guard++ < 100) {
            if ($current->id === $this->id) {
                return true;
            }
            $current = $current->parent_id ? MediaFolder::find($current->parent_id) : null;
        }
        return false;
    }
}
