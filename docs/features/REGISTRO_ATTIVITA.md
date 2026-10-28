# Registro attività (audit log)

Chi ha fatto che cosa, quando, da dove e con quale esito. Consultabile solo dagli
admin: Pannello admin → **Registro attività**. Conservazione 24 mesi.

Adattamento a Slim 4 + Eloquent standalone del prompt pensato per Laravel
(`docs/features/prompt-registro-attivita.md`). Implementato il 2026-10-27, fasi 0-10.

## Principi

1. **Non rallenta e non rompe l'app.** Le voci restano in memoria e si scrivono con un
   INSERT multiplo dopo l'invio della risposta (`public/index.php`: `fastcgi_finish_request()`
   e poi `Auditor::flush()`). Gli errori di scrittura vanno solo in `error_log`: l'API
   funziona anche senza la tabella (test `ResilienceTest`).
2. **Immutabile.** `AuditLog` lancia `LogicException` su modifica ed eliminazione.
   Unica eccezione: il salvataggio dell'editor (sotto).
3. **Autosufficiente.** Nessuna foreign key; nome, email, ruolo dell'utente ed etichetta
   del record sono copiati nella voce.
4. **Niente segreti né dati personali dei visitatori.** Chiavi `password|token|secret|
   signature|api_key|recovery_code|authorization` → `•••` (anche annidate), profondità
   massima 3, testi oltre 2000 caratteri troncati, file per nome. Dei lead non si
   registrano nome, email, telefono, messaggio e metadata.
5. **Nel database solo codici stabili**; i testi in italiano vengono da `AuditCatalog`.

## Componenti (backend/src)

| File | Ruolo |
|---|---|
| `Audit/Auditor.php` | Buffer, `request_id` (ULID), `record()`, `modelChanged()` (dopo il commit), `flush()`, `clean()`, risoluzione di azienda/pagina/etichetta, unione dei salvataggi, pulizia automatica |
| `Audit/Auditable.php` | Trait sui modelli: created, updated (campo per campo), deleted, force_deleted, restored |
| `Audit/AuditCatalog.php` | Etichette: categorie, esiti, ruoli, canali, **rotte**, tipi di record, eventi |
| `Audit/AccessEvents.php` | login, login_failed, logout |
| `Audit/PublicationEvents.php` | page.published/unpublished, page.reassigned, lead.received |
| `Audit/DomainEvents.php` | page.domains_updated (sync/attach/detach non emettono eventi) |
| `Audit/ExportEvents.php` | lead.exported (export CSV dei lead fatto nel browser) |
| `Audit/ScriptAudit.php` | Script di `backend/scripts` (canale console) |
| `Middleware/RecordActivity.php` | Voce per ogni richiesta all'API |
| `Controllers/AuditLogController.php` | API di consultazione (solo admin) |
| `Models/AuditLog.php` | Modello immutabile, `prune()` |

Configurazione: `config/database.php` imposta il dispatcher degli eventi (richiede
`illuminate/events`) e il gestore delle transazioni (`afterCommit`).
`bootstrap/app.php`: ordine dei middleware CORS → RecordActivity → ErrorMiddleware →
routing → `RecordActivity::captureRoute` → rotta.

Modelli con il trait: User, Company, Page, Domain, Media, MediaFolder, Lead.
**Non** Block (riassunti nel salvataggio) né le tabelle di visite e renderer.

## Cosa si registra

| Categoria | Quando | `action` |
|---|---|---|
| view | GET autenticate | `GET /api/pages/{id}` (pattern della rotta) |
| action | POST/PUT/DELETE, anche anonime (login, form dei lead) | `PUT /api/pages/{id}` |
| change | Modifiche ai modelli, domini delle pagine | `page.updated`, `page.domains_updated` |
| access | Accessi | `login`, `login_failed`, `logout` |
| publish | Pubblicazione e assegnazione | `page.published`, `page.unpublished`, `page.reassigned` |
| system | Lead ricevuto, script, pulizia | `lead.received`, `script`, `audit_prune` |
| export | Export dei lead dai pannelli admin e aziendale | `lead.exported` |

Non si registrano: GET anonime (pagine servite ai renderer), OPTIONS/HEAD, rotte
inesistenti, `POST /api/track/view`, `POST /api/pages/check-slug`, `GET /api/media/{id}/file`,
`GET /api/admin/audit-logs/new` (`RecordActivity::SKIP_ROUTES`).

Esito dallo status: 400/422 `invalid` (qui la validazione risponde 400), 401/403 `denied`,
404 `not_found`, 429 `throttled`, altri ≥400 e ≥500 `error`, altrimenti `ok`. Per gli errori
`properties.error` contiene il `message`/`error` della risposta.

### Export dei lead

Il CSV dei lead si crea nel browser, quindi il server non lo vedrebbe: prima del download
il frontend chiama `POST /api/leads/exports` con gli id dei lead, il pannello e i filtri
(`pageStore.logLeadExport`). Il backend tiene solo gli id visibili all'utente (stessa regola
di `GET /api/leads`) e registra `export/lead.exported`: numero, id scartati, id dei lead
(fino a 1000, oltre solo l'intervallo), pagine con il numero di lead (prime 20), aziende se
più di una, filtri. Della ricerca libera si registra solo `text_search: true` (può contenere
email o telefono di un visitatore); nessun dato dei lead. Azienda e pagina della voce sono
valorizzate solo se l'export ne riguarda una sola. Se la chiamata fallisce l'export procede
comunque (il registro non blocca l'app). Un nuovo export di dati personali fatto nel browser
deve passare da una rotta simile.

### Dettaglio di un lead e calendario

Anche scheda del lead e calendario usano i lead già caricati. Per la traccia:
- all'apertura della scheda (lista o calendario) il frontend chiama `GET /api/leads/{id}`
  (`pageStore.viewLead`): voce `view` con lead, pagina e azienda; 403 se il lead non è
  visibile all'utente. La risposta non viene usata dalla scheda.
- il calendario segnala il mese mostrato all'apertura e a ogni cambio
  (`month-shown` di `LeadsCalendar`), il frontend chiama dopo 1 secondo di pausa
  `GET /api/leads/calendar?mese=AAAA-MM&pannello=admin|company` (`pageStore.logCalendarView`),
  che risponde 204: il mese si legge dall'URL della voce e il dettaglio del registro lo mostra.

**Staff:** un admin che agisce su un'azienda diversa dalla propria → `properties.staff_view`.

### Salvataggio dell'editor (autosalvataggio)

`PUT /api/pages/{id}` non registra l'input (tutta la pagina) ma un riepilogo dei blocchi
(numero, aggiunti e tolti per tipo). I salvataggi dello stesso utente sulla stessa pagina
entro **10 minuti** dal primo che toccano **solo i blocchi** si sommano in una voce
(`saves`, `last_saved_at`, blocchi cumulati): è l'unico aggiornamento di una voce scritta
(solo `properties`, con il Query Builder). Se il salvataggio cambia campi della pagina o la
pubblicazione resta una voce a sé, collegata alle sue modifiche dallo stesso `request_id`.

## Regola: ogni nuova rotta va nel catalogo

Aggiungendo una rotta in `config/routes.php` aggiungere la descrizione in
`AuditCatalog::ROUTES` (`'METODO /pattern' => 'Descrizione'`). Lo verifica
`AuditCatalogTest::test_ogni_rotta_dell_api_ha_una_descrizione`. Se la rotta individua un
record con `{id}`, aggiungerla anche a `RecordActivity::SUBJECT_ROUTES`. Se è tecnica o
molto frequente, a `SKIP_ROUTES`; se il corpo contiene dati personali o molto grandi, a
`NO_INPUT_ROUTES`.

Query di massa (`Model::where()->update()`, `sync()`) non passano dagli eventi: se serve
traccia, aggiornare un record alla volta o chiamare `Auditor::get()->record()`.

Nuovo script in `backend/scripts`: dopo `config/database.php` aggiungere
`\App\Audit\ScriptAudit::start(basename(__FILE__, '.php'), $argv ?? []);`.

## API (solo admin)

- `GET /api/admin/audit-logs?pagina=N&…` — 50 voci per pagina, dalla più recente, con
  descrizioni, nomi di aziende e pagine (anche eliminate: "Azienda #id"), etichette
- `GET /api/admin/audit-logs/new?dopo=<id>&…` — diretta: max 200 voci, `truncated`
- `GET /api/admin/audit-logs/export?…` — CSV per Excel (BOM, `;`, celle-formula neutralizzate)

Filtri (non validi = ignorati): `azienda`, `pagina_id`, `utente` (nome, email, etichetta
record), `categoria`, `esito`, `dal`, `al` (giorni in ora italiana), `richiesta`, `ip`,
`senza_consultazioni`.

## Pulizia

Automatica, senza attività pianificata: dopo ogni scrittura una query sull'indice di
`created_at` controlla se ci sono voci oltre 24 mesi; solo allora `AuditLog::prune()` le
cancella e registra `system/audit_prune` con il numero.

## Test

`cd backend && ./tests/create-test-db.sh && vendor/bin/phpunit` (database
`<DB_DATABASE>_test` con la struttura di quello locale; i test rifiutano altri database).
Ricreare il DB dei test dopo ogni nuova migration.

## Deploy (Aruba/Plesk, senza SSH)

1. **Backup** del database (phpMyAdmin → Esporta).
2. **phpMyAdmin**: eseguire `backend/database/sql/registro_attivita.sql`.
3. **vendor/**: `./scripts/deploy-vendor.sh` (prova) poi `--execute`. Carica
   `illuminate/bus`, `illuminate/events`, `illuminate/pipeline`, poi `vendor/composer/`,
   per ultimo `autoload.php`. Va fatto **prima** del backend: il nuovo
   `config/database.php` usa il dispatcher degli eventi.
4. **Verifica**: caricare `backend/scripts/check-audit-requirements.php` e lanciarlo da
   Plesk (attività pianificata "Esegui uno script PHP" → "Esegui ora"). Atteso: nessun
   errore bloccante, solo i due avvisi "normale prima del deploy del backend".
5. **Backend e frontend**: `./scripts/deploy-ftp.sh` (prova) poi `--execute`.
   `backend/public/index.php` viene caricato per ultimo.
6. Rilanciare `check-audit-requirements.php`: "tutto pronto". Aprire Pannello admin →
   Registro attività e verificare che compaiano le proprie consultazioni.

Rollback: ripristinare i file precedenti (`git checkout <rev>` + deploy) e, se serve,
`DROP TABLE audit_logs`. I pacchetti aggiunti a vendor/ sono innocui con il codice vecchio.
