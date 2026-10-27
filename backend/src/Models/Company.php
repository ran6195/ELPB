<?php

namespace App\Models;

use App\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Auditable;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'email'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    // Registro attività (App\Audit\Auditable)
    public function auditType(): string
    {
        return 'company';
    }

    public function auditLabel(): ?string
    {
        return $this->name;
    }

    public function auditIgnored(): array
    {
        return [];
    }
}
