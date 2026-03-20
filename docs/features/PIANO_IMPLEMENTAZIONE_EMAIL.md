# Piano Implementazione Notifiche Email

## Obiettivo
Implementare sistema di notifiche email centralizzato per avvisare gli owner delle landing page quando arriva un nuovo lead.

**Server centralizzato**: edysma.net/ELPB/backend (unico punto di invio email)

---

## Architettura

```
Form Submit → LeadController::store() → Lead salvato → EmailService::sendLeadNotification()
                                                              ↓
                                                      PHPMailer → SMTP Aruba
                                                              ↓
                                                      Email a destinatari configurati
```

---

## Fase 1: Setup Base (30-45 min)

### 1.1 Installare PHPMailer
```bash
cd backend
composer require phpmailer/phpmailer
```

### 1.2 Configurare .env
✅ **FATTO** — Variabili email aggiunte:
```
MAIL_HOST=vu000816.arubabiz.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=       # DA COMPILARE
MAIL_PASSWORD=       # DA COMPILARE
MAIL_FROM_ADDRESS=   # DA COMPILARE
MAIL_FROM_NAME="Landing Page Builder"
```

### 1.3 Migration: aggiungere campo notification_settings
**File**: `backend/database/migrations/add_notification_settings_to_pages.php`

```php
<?php
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->table('pages', function ($table) {
    $table->json('notification_settings')->nullable()->after('legal_info');
});

echo "✓ Campo notification_settings aggiunto alla tabella pages\n";
```

**Eseguire**: `php backend/database/migrations/add_notification_settings_to_pages.php`

---

## Fase 2: Backend — EmailService (1-1.5h)

### 2.1 Creare EmailService.php
**File**: `backend/src/Services/EmailService.php`

**Responsabilità**:
- Carica config da .env
- Wrapper PHPMailer con gestione errori
- Template HTML per le email
- Metodo `sendLeadNotification($lead, $page)`

**Struttura**:
```php
class EmailService {
    private $mailer;

    public function __construct() {
        // Setup PHPMailer con config .env
    }

    public function sendLeadNotification($lead, $page) {
        // 1. Carica notification_settings dalla pagina
        // 2. Determina destinatari (owner + custom)
        // 3. Componi email HTML
        // 4. Invia via PHPMailer
        // 5. Log errori
    }

    private function getLeadEmailTemplate($lead, $page) {
        // Ritorna HTML responsive
    }
}
```

### 2.2 Integrare in LeadController
**File**: `backend/src/Controllers/LeadController.php`

**Modifica nel metodo `store()`**:
```php
// Dopo: $lead = Lead::create([...]);

// Invia notifica email
try {
    $page = Page::with('user', 'company')->find($lead->page_id);
    if ($page && $page->notification_settings) {
        $emailService = new EmailService();
        $emailService->sendLeadNotification($lead, $page);
    }
} catch (Exception $e) {
    // Log ma non bloccare risposta (email non è critica)
    error_log("Errore invio email lead: " . $e->getMessage());
}

// Continua con: return $response->withJson($lead, 201);
```

### 2.3 Aggiornare Model Page
**File**: `backend/src/Models/Page.php`

Aggiungere `notification_settings` a `$fillable` e `$casts`:
```php
protected $fillable = [
    // ... campi esistenti ...
    'notification_settings',
];

protected $casts = [
    // ... cast esistenti ...
    'notification_settings' => 'array',
];
```

---

## Fase 3: Template Email HTML (30 min)

**File**: `backend/src/Services/templates/lead-notification.html`

**Contenuto**: Email HTML responsive con:
- Logo/brand landing page
- Titolo "Nuovo contatto ricevuto"
- Dati lead in tabella (Nome, Email, Telefono, Messaggio)
- Link per vedere tutti i lead nel backend
- Footer con info landing page

**Inline CSS** per compatibilità client email.

---

## Fase 4: Frontend — UI Configurazione (1-1.5h)

### 4.1 Aggiungere sezione in PageSettings.vue
**File**: `frontend/src/components/PageSettings.vue`

**Nuova sezione "Notifiche Email"**:
```vue
<div class="bg-white rounded-lg shadow p-6">
    <h3>Notifiche Email</h3>
    <div class="space-y-4">
        <!-- Toggle attiva/disattiva -->
        <label>
            <input type="checkbox" v-model="notificationSettings.enabled">
            Ricevi email per nuovi contatti
        </label>

        <!-- Email owner (automatica) -->
        <div>
            <label>Email proprietario pagina</label>
            <input type="email" :value="currentUser.email" disabled>
            <small>Riceverai sempre notifiche a questo indirizzo</small>
        </div>

        <!-- Email aggiuntive -->
        <div>
            <label>Email aggiuntive (opzionale)</label>
            <textarea v-model="notificationSettings.additional_emails"
                      placeholder="email1@example.com, email2@example.com"></textarea>
            <small>Separare con virgola per inviare a più destinatari</small>
        </div>
    </div>
</div>
```

### 4.2 Store action per salvare notification_settings
**File**: `frontend/src/stores/pageStore.js`

Aggiungere metodo:
```javascript
async updateNotificationSettings(pageId, settings) {
    const response = await api.put(`/pages/${pageId}/notification-settings`, settings);
    return response.data;
}
```

### 4.3 Backend endpoint per salvare
**File**: `backend/public/index.php`

Aggiungere route:
```php
$app->put('/api/pages/{id}/notification-settings', function ($request, $response, $args) {
    // Simile a updateLegalInfo
    // Valida e salva notification_settings
});
```

---

## Fase 5: Testing (30-45 min)

### 5.1 Test locale
1. Compilare MAIL_USERNAME/PASSWORD in .env
2. Creare pagina di test
3. Abilitare notifiche con email valida
4. Compilare form su renderer standalone
5. Verificare ricezione email

### 5.2 Test produzione
1. Deploy backend su edysma.net/ELPB
2. Test da ilprodotto.it (renderer remoto)
3. Verificare email arrivi correttamente

### 5.3 Troubleshooting
- Log PHPMailer per debug SMTP
- Verificare firewall non blocca porta 587
- Test credenziali Aruba con script standalone

---

## Fase 6: Documentazione (15 min)

### 6.1 Aggiornare CLAUDE.md
Aggiungere entry sessione con riepilogo implementazione

### 6.2 Creare README_EMAIL.md
Guida per:
- Configurare credenziali email
- Testare invio
- Troubleshooting errori comuni
- Log e monitoring

---

## File da creare/modificare

### Nuovi file
- ✅ `PIANO_IMPLEMENTAZIONE_EMAIL.md` (questo file)
- `backend/database/migrations/add_notification_settings_to_pages.php`
- `backend/src/Services/EmailService.php`
- `backend/src/Services/templates/lead-notification.html`
- `README_EMAIL.md`

### File da modificare
- ✅ `backend/.env` — variabili email
- `backend/src/Models/Page.php` — fillable + casts
- `backend/src/Controllers/LeadController.php` — integrazione EmailService
- `backend/public/index.php` — endpoint notification-settings
- `frontend/src/components/PageSettings.vue` — UI notifiche
- `frontend/src/stores/pageStore.js` — action update
- `CLAUDE.md` — documentazione

---

## Stima Tempo Totale

| Fase | Tempo |
|---|---|
| Setup base (Composer + migration) | 30-45 min |
| EmailService + integrazione backend | 1-1.5h |
| Template HTML email | 30 min |
| Frontend UI + API | 1-1.5h |
| Testing | 30-45 min |
| Documentazione | 15 min |
| **TOTALE** | **3.5-4.5h** |

---

## Priorità Features

### Must Have (MVP)
- [x] Config SMTP in .env
- [ ] EmailService base con PHPMailer
- [ ] Notifica "Nuovo lead" all'owner pagina
- [ ] Template HTML base
- [ ] Migration notification_settings

### Nice to Have (v2)
- [ ] Email aggiuntive custom
- [ ] Log invii email (tabella email_log)
- [ ] Retry automatico su fallimento
- [ ] Preview email in UI
- [ ] Template personalizzabili per pagina

### Future
- [ ] Riepilogo giornaliero lead
- [ ] Email conferma al visitatore
- [ ] Notifiche Telegram/Slack
- [ ] Queue asincrona (Redis)

---

## Note Implementazione

### Sicurezza
- ✅ Credenziali SMTP solo in .env backend (mai esporre a frontend/renderer)
- Rate limiting su endpoint lead (max 10/min per IP)
- Sanitizzazione HTML in template email (htmlspecialchars)
- Validazione email destinatari

### Performance
- Invio sincrono va bene per MVP (1-3s per email)
- Non bloccare risposta API se invio fallisce (try/catch con log)
- Considerare queue per volumi >100 lead/giorno

### Monitoring
- Log errori PHPMailer in `backend/logs/email.log`
- Tracciare invii riusciti/falliti
- Alert se tasso fallimento >10%

---

## Next Steps

1. ✅ Configurare .env con credenziali Aruba
2. Installare PHPMailer via Composer
3. Creare migration notification_settings
4. Implementare EmailService.php
5. Integrare in LeadController
6. Testare invio email

**Pronto per iniziare l'implementazione?**
