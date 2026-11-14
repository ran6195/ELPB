<?php

namespace App\Models;

use App\Audit\Auditable;
use App\Audit\PublicationEvents;
use App\Services\PublicationHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes, Auditable;

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
        'legal_info',
        'notification_settings',
        'company_id',
        'user_id'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'styles' => 'array',
        'recaptcha_settings' => 'array',
        'tracking_settings' => 'array',
        'quick_contacts' => 'array',
        'legal_info' => 'array',
        'notification_settings' => 'array',
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

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_page')->orderBy('hostname');
    }

    /**
     * Per pubblicare una pagina serve almeno un dominio associato, e una pagina pubblicata
     * non può perdere l'ultimo. Controllo solo dell'applicazione: i renderer mostrano
     * la pagina su qualunque sito. Le pagine pubblicate prima della regola senza domini
     * restano pubblicate.
     */
    public const DOMAIN_REQUIRED = 'domain_required';
    public const MSG_PUBLISH_NEEDS_DOMAIN = 'Per pubblicare la pagina associala ad almeno un dominio';
    public const MSG_LAST_DOMAIN = 'La pagina è pubblicata: deve restare associata ad almeno un dominio. Ritira prima la pubblicazione';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pubblicazione e ritiro registrati come eventi propri, dopo il commit.
     * Storico della pubblicazione (fatturazione) aggiornato nella stessa transazione.
     */
    protected static function booted(): void
    {
        static::created(function (Page $page) {
            if ($page->is_published) {
                $page->getConnection()->afterCommit(fn() => PublicationEvents::pagePublicationChanged($page, true));
            }
            PublicationHistory::sync($page, PublicationHistory::END_UNPUBLISHED);
        });
        static::updated(function (Page $page) {
            if ($page->wasChanged('is_published')) {
                $published = (bool) $page->is_published;
                $page->getConnection()->afterCommit(fn() => PublicationEvents::pagePublicationChanged($page, $published));
            }
            if ($page->wasChanged(['is_published', 'company_id'])) {
                PublicationHistory::sync($page, PublicationHistory::END_UNPUBLISHED);
            }
        });
        // Archiviazione o eliminazione definitiva: la pagina non viene più mostrata
        static::deleted(function (Page $page) {
            $reason = $page->isForceDeleting() ? PublicationHistory::END_DELETED : PublicationHistory::END_ARCHIVED;
            PublicationHistory::sync($page, $reason, false);
        });
        static::restored(function (Page $page) {
            PublicationHistory::sync($page, PublicationHistory::END_UNPUBLISHED);
        });
    }

    // Registro attività (App\Audit\Auditable)
    /** I blocchi non sono registrati uno per uno: il salvataggio dell'editor li riassume (RecordActivity). */
    public function auditType(): string
    {
        return 'page';
    }

    public function auditLabel(): ?string
    {
        return $this->title;
    }

    public function auditIgnored(): array
    {
        return [];
    }
}
