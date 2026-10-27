<?php

namespace App\Models;

use App\Audit\Auditable;
use App\Utils\Hostname;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use Auditable;

    protected $table = 'leads';

    protected $fillable = [
        'page_id',
        'name',
        'email',
        'phone',
        'message',
        'metadata',
        'origin_host',
        'domain_id',
        'origin_inferred'
    ];

    protected $casts = [
        'metadata' => 'array',
        'domain_id' => 'integer',
        'origin_inferred' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Stato dell'origine mostrato in interfaccia (calcolato, non salvato):
     * - unknown:    origine non registrata (lead precedenti ai domini, header assente)
     * - inferred:   dominio stimato a posteriori per le pagine vecchie, non certo
     * - preview:    inviato dall'anteprima dell'applicazione
     * - associated: dominio associato alla pagina
     * - unexpected: la pagina ha domini associati ma il lead arriva da un altro sito
     * - external:   sito noto ma la pagina non ha domini associati
     * Richiede la relazione page.domains caricata.
     */
    public function originStatus(): string
    {
        if ($this->origin_inferred) {
            return 'inferred';
        }
        if (!$this->origin_host) {
            return 'unknown';
        }
        if (Hostname::isAppHost($this->origin_host)) {
            return 'preview';
        }
        $pageHosts = $this->page ? $this->page->domains->pluck('hostname')->all() : [];
        if (in_array($this->origin_host, $pageHosts, true)) {
            return 'associated';
        }
        return $pageHosts ? 'unexpected' : 'external';
    }

    // Registro attività (App\Audit\Auditable)
    /** Dati personali del visitatore esclusi dal registro: restano solo nella tabella leads. */
    public function auditType(): string
    {
        return 'lead';
    }

    public function auditLabel(): ?string
    {
        return null;
    }

    public function auditIgnored(): array
    {
        return ['name', 'email', 'phone', 'message', 'metadata'];
    }
}
