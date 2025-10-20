<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCompany()
    {
        return $this->role === 'company';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function canViewPage($page)
    {
        // Admin vede tutto
        if ($this->isAdmin()) {
            return true;
        }

        // Company vede tutte le pagine della sua azienda
        if ($this->isCompany() && $page->company_id === $this->company_id) {
            return true;
        }

        // User vede solo le sue pagine
        if ($this->isUser() && $page->user_id === $this->id) {
            return true;
        }

        return false;
    }

    public function canEditPage($page)
    {
        // Admin può modificare tutto
        if ($this->isAdmin()) {
            return true;
        }

        // Company può modificare pagine della sua azienda
        if ($this->isCompany() && $page->company_id === $this->company_id) {
            return true;
        }

        // User può modificare solo le sue pagine
        if ($this->isUser() && $page->user_id === $this->id) {
            return true;
        }

        return false;
    }
}
