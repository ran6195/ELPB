<?php

namespace App\Models;

use App\Audit\Auditable;
use App\Models\Concerns\OwnedByCompany;
use App\Utils\Hostname;
use Illuminate\Database\Eloquent\Model;

/**
 * Dominio (sito del cliente) su cui sono pubblicate una o più landing page.
 * L'hostname è sempre salvato normalizzato (vedi App\Utils\Hostname).
 */
class Domain extends Model
{
    use OwnedByCompany, Auditable;

    protected $table = 'domains';

    protected $fillable = [
        'company_id',
        'user_id',
        'hostname',
        'label'
    ];

    protected $appends = ['display_hostname'];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /** Hostname leggibile: i domini accentati sono salvati in punycode */
    public function getDisplayHostnameAttribute(): string
    {
        return Hostname::display($this->hostname);
    }

    public function pages()
    {
        return $this->belongsToMany(Page::class, 'domain_page');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Collega al dominio i lead della pagina già arrivati da questo sito (origine misurata) */
    public function linkExistingLeads(int $pageId): void
    {
        Lead::where('page_id', $pageId)
            ->whereNull('domain_id')
            ->where('origin_host', $this->hostname)
            ->update(['domain_id' => $this->id]);
    }

    /** Stesso hostname già registrato per lo stesso proprietario */
    public static function hostnameTaken(string $hostname, ?int $companyId, ?int $userId, ?int $exceptId = null): bool
    {
        $query = self::where('hostname', $hostname);
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id')->where('user_id', $userId);
        }
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        return $query->exists();
    }

    // Registro attività (App\Audit\Auditable)
    public function auditType(): string
    {
        return 'domain';
    }

    public function auditLabel(): ?string
    {
        return $this->hostname;
    }

    public function auditIgnored(): array
    {
        return [];
    }
}
