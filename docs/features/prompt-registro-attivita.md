# Prompt per l'agente: registro attività (audit log) in Laravel

> Copia da qui in giù. Sostituisci i segnaposto tra `<…>` con i nomi del tuo progetto
> (es. `<Tenant>` = Company/Team/Organization, `<Area admin>` = /admin). Se il progetto non è multi-tenant,
> togli le parti su tenant e sito.

---

Implementa in questo progetto Laravel un **registro attività interno (audit log)**: chi ha fatto che cosa, quando, da dove e con quale esito. Prima di scrivere codice leggi la struttura del progetto (modelli, middleware registrati in `bootstrap/app.php`, autenticazione, eventuale multi-tenancy, frontend usato) e adatta il piano; poi proponimelo in breve e procedi.

## Obiettivi e vincoli

1. **Non deve mai rallentare né rompere l'applicazione.** Le voci si raccolgono in memoria e si scrivono con un solo INSERT multiplo *dopo* l'invio della risposta (`terminate`). Ogni errore di scrittura va solo nel log di Laravel (`Log::error`), mai propagato.
2. **Immutabile.** Il modello `AuditLog` lancia `LogicException` su `updating` e `deleting`. L'unica cancellazione è la pulizia periodica di massa (`MassPrunable`, conservazione `MONTHS = 24`).
3. **Autosufficiente.** Nessuna foreign key: le voci sopravvivono all'eliminazione di utenti e record. Per questo nome, email e ruolo dell'utente e un'etichetta leggibile del record (`subject_label`) si copiano nella voce.
4. **Niente segreti.** Mascherare con `•••` qualunque chiave che corrisponde a `/password|token|secret|signature|api_key|recovery_code/i`, anche negli array annidati (profondità massima 3, poi `[…]`); testi oltre 2000 caratteri troncati con `… [troncato]`; oggetti convertiti con `__toString` o `[NomeClasse]`; file caricati registrati solo per nome. Nell'URL togliere i parametri di rotta segreti (token dei link monouso, sostituiti con `•••`) e dalla query `signature`, `token`, `code`, `state` (ritorni OAuth).
5. **Nel database solo codici stabili** (nome della rotta, `tipo.evento`, categoria, esito). I testi leggibili si calcolano alla lettura in un catalogo: rinominare un'etichetta non richiede migrazioni.

## Tabella `audit_logs`

Migrazione senza `updated_at`:

| colonna | tipo | note |
|---|---|---|
| id | bigIncrements | |
| created_at | dateTime(3) | millisecondi; per le richieste è l'**ora di inizio** |
| request_id | char(26) null | ULID per richiesta: lega la voce della richiesta alle modifiche che ha prodotto |
| channel | string(10) | `web`, `api`, `console`, `job` |
| category | string(10) | `view`, `action`, `change`, `access`, `publish`, `system` |
| action | string(100) | nome rotta, `tipo.evento`, `login`, `command`… |
| user_id, user_name, user_email, user_role | null | copia dell'autore |
| `<tenant>_id`, `<sotto-tenant>_id` | unsignedBigInteger null | contesto (es. azienda e sito) |
| subject_type (40), subject_id, subject_label | null | record coinvolto |
| method (8), url (1000), status (smallint), outcome (12), duration_ms | null | dati HTTP |
| ip (45), user_agent | null | user agent limitato a 250 caratteri |
| properties | json null | dettagli: parametri, input, errori, old/new… |

Indici: `created_at`, `(user_id, created_at)`, `(<tenant>_id, created_at)`, `(category, created_at)`, `request_id`.

## Componenti

### 1. `App\Audit\Auditor` (singleton)
- Costanti di categoria: `VIEW`, `ACTION`, `CHANGE`, `ACCESS`, `PUBLISH`, `SYSTEM`.
- Stato: `buffer` (voci in attesa), `request` (request_id, channel, ip, user_agent, flag `staff_view`), `sources` (pila di comandi/job in corso), `enabled`.
- `beginRequest(channel, ip, userAgent): string` genera l'ULID e salva il contesto della richiesta.
- `markStaffView()`: (se c'è) segna che un admin sta guardando l'area di un cliente; finisce in `properties.staff_view`.
- `enterSource(channel, name)` / `leaveSource()`: comandi e job annidati; la voce prende il canale della sorgente più interna e `properties.source = name`.
- `withoutAuditing(callable)`: sospende la registrazione (seeder, dati demo), con `try/finally`.
- `record(category, action, data)`: costruisce la riga e la mette nel buffer. `data` accetta `user` (autore esplicito, anche `null` = sistema; se la chiave manca si usa l'utente autenticato), contesto tenant, `subject_*`, `method`, `url`, `status`, `outcome`, `duration_ms`, `properties`, `at`. Se il tenant non è indicato, prenderlo dal resolver del tenant corrente. Oltre 200 voci nel buffer fa `flush()` subito (comandi lunghi).
- `modelChanged(Model, event)`: per `created`/`restored` salva gli attributi come `new`, per `deleted` come `old`, per `updated` solo i campi cambiati (`getChanges()`) con il valore precedente da `getRawOriginal()`. Esclude sempre `created_at`/`updated_at` più `auditIgnored()` del modello; se un update non cambia nulla di rilevante non registra. Ricava tenant/sotto-tenant dagli attributi (`<tenant>_id`) o dal modello stesso. **Registra dentro `DB::afterCommit`**: le modifiche annullate da un rollback non compaiono.
- `flush()`: svuota il buffer e inserisce a blocchi di 100 con `DB::table('audit_logs')->insert()`, in `try/catch`.
- `clean(array)`: la sanificazione del punto 4, pubblica (la usano anche middleware e provider).
- Utente corrente: fuori da una richiesta, in console, non chiamare `Auth::user()` (eviterebbe di avviare la sessione) e restituire `null`.

### 2. Trait `App\Audit\Auditable`
`bootAuditable()` registra `created`, `updated`, `deleted` e, se il modello usa `SoftDeletes`, `restored`, chiamando `Auditor::modelChanged`. Il modello può definire `auditType()` (default: nome della classe in snake_case), `auditLabel()` (es. email dell'utente, nome dell'azienda) e `auditIgnored()` (es. `remember_token`, colonne tecniche). Documenta nel trait che le query di massa (`Model::where()->update()/delete()`) non passano dagli eventi: sui modelli auditati cancellare/aggiornare uno per uno quando serve traccia.
Applicalo ai modelli di dominio importanti (utenti, tenant, membership/ruoli, impostazioni, collegamenti a servizi esterni, contenuti pubblicati). Non ai dati sincronizzati in massa o alle tabelle di metriche.

### 3. Middleware `RecordActivity` (con parametro channel)
Aggiungilo in coda ai gruppi `web` (`RecordActivity::class`) e `api` (`RecordActivity::class.':api'`).
- `handle`: salta le rotte tecniche in `SKIP_ROUTES` (polling automatici, compresa la rotta "diretta" del registro stesso, altrimenti ogni controllo aggiunge una voce); chiama `beginRequest`; salva l'utente in `$request->attributes` (dopo il logout l'utente c'è solo all'inizio, dopo il login solo alla fine).
- `terminate`: ignora HEAD/OPTIONS e le richieste senza rotta. Le GET anonime non si registrano; i POST anonimi sì (login, reset password, inviti). Categoria `VIEW` per GET, `ACTION` per il resto; `action` = nome della rotta (`unnamed` se manca).
  - `at` = `LARAVEL_START` (o `REQUEST_TIME_FLOAT`): una richiesta lenta resta in ordine prima delle modifiche che ha fatto. `duration_ms` = ora − inizio.
  - Contesto dai parametri di rotta: i parametri `<tenant>`/`<sotto-tenant>` (o i modelli di quelle classi) vanno nelle colonne dedicate, gli altri in `properties.params` (solo l'id dei modelli), saltando quelli segreti.
  - `properties.input` (solo non-GET) = input senza `_token` e `_method`, passato da `clean()`, file per nome.
  - Errori di validazione: nomi dei campi presi dalla sessione flash `errors` (sia come `ViewErrorBag` sia nella forma serializzata `[bag => ['messages' => [campo => …]]]`), in `properties.errors`; se ci sono, outcome `invalid`.
  - `outcome` dallo status: ≥500 `error`, 401/403 `denied`, 404 `not_found`, 419 `expired`, 422 `invalid`, 429 `throttled`, altri ≥400 `error`, altrimenti `ok`.
  - Se usi Inertia: le GET con header `X-Inertia-Partial-Data` riuscite (<400) non si registrano (sono ricaricamenti parziali, non nuove consultazioni); se falliscono sì, con l'header in `properties.partial`.

### 4. `AuditServiceProvider`
- `register`: `singleton(Auditor::class)`.
- `boot`: `$this->app->terminating(fn () => Auditor->flush())`.
- Accessi (categoria `ACCESS`, subject = utente, label = email): `Login` (guard, remember), `Logout`, `PasswordReset`, `Failed` (outcome `denied`, email tentata, `known_user`), `Lockout` (outcome `throttled`). Se usi Sanctum: `PersonalAccessToken::created/deleted` come `api_token.created/deleted`.
- Comandi: su `CommandStarting` (esclusi i comandi rumorosi `schedule:run`, `schedule:work`, `schedule:finish`, `queue:work`, `queue:listen`, `list`, `help`, `package:discover`, `completion`, `inspire`, `about`) memorizza l'ora d'inizio e `enterSource('console', nome)`; su `CommandFinished` registra `SYSTEM`/`command` con argomenti e opzioni puliti, exit code, durata, outcome; poi `leaveSource()` e `flush()`.
- Job: `JobProcessing` → `enterSource('job', class_basename(resolveName()))`; `JobProcessed` e `JobExceptionOccurred` → `leaveSource()` + `flush()` (i worker sono processi lunghi: senza flush per job le voci resterebbero in memoria).

### 5. Eventi di dominio espliciti
Per le azioni importanti per il business (pubblicazione di un contenuto per il cliente, esito di un'elaborazione in coda) crea piccole classi dedicate (es. `App\Audit\PublicationEvents`) che chiamano `record()` con una categoria propria (`PUBLISH` o `SYSTEM`) e dati leggibili in `properties` (testo pubblicato, testo precedente, origine, motivo). Si aggiungono a quello che il registro salva già in automatico.

### 6. `App\Audit\AuditCatalog`
Mappe da codice a etichetta: categorie, esiti, ruoli, canali, rotte (`'nome.rotta' => 'Descrizione'`), tipi di record (`tipo => [nome, femminile]`, per comporre "Azienda creata" / "Utente modificato"), azioni di accesso, pubblicazioni, eventi di sistema. `describe(AuditLog)` sceglie il testo in base alla categoria; se la rotta non è in catalogo mostra `METODO url`. Ricorda nel CLAUDE.md/README di aggiungere qui ogni nuova rotta.

### 7. Pulizia
Nello scheduler, una volta al giorno: `(new AuditLog)->pruneAll()`, poi registra `SYSTEM`/`audit_prune` con il numero di voci cancellate e `flush()`.

### 8. Pagina di consultazione (solo per il ruolo più alto, es. super admin)
Rotte `GET <area admin>/registro-attivita` (index), `/esporta` (CSV), `/nuove` (diretta). Controller con:
- **Filtri dalla query string**, validati uno per uno: quelli non validi si ignorano invece di dare errore. Tenant, utente (LIKE su nome, email e `subject_label`), categoria, esito, giorno dal/al (giorni nel fuso locale convertiti in UTC per la query), `request_id`, IP.
- **Ordinamento** `created_at DESC, id DESC`, 50 per pagina.
- **Righe** con descrizione dal catalogo, utente (nome, email, ruolo), tenant (con nome anche se archiviato o eliminato: "Azienda #id"), record, dati HTTP, request_id e `properties`.
- **Diretta** stile `tail -f`: la pagina chiama `/nuove?dopo=<ultimo id>` con gli stessi filtri ogni 4 secondi; massimo 200 voci per risposta e flag `truncated` (se è vero, la pagina si ricarica); al massimo 500 voci tenute in pagina; preferenza on/off in `localStorage` (con try/catch).
- **Export CSV** per Excel con gli stessi filtri: `streamDownload`, BOM UTF-8, separatore `;`, `lazy(500)`, date nel fuso locale.
- **Frontend**: righe espandibili (`<details>`) con i campi modificati prima → dopo, input, errori, metadati; link nella voce per filtrare "stessa richiesta", "stesso IP", "stesso utente"; filtri testuali con debounce di 400 ms.

## Test (feature) da scrivere
1. La consultazione di una pagina da utente autenticato si registra con tenant e sotto-tenant.
2. Le GET anonime non si registrano.
3. Una modifica si registra campo per campo (old/new) con lo stesso `request_id` della richiesta.
4. La voce della richiesta prende l'ora d'inizio e in ordine cronologico precede le sue modifiche.
5. I ricaricamenti parziali riusciti non si registrano (se usi Inertia).
6. Creare un record produce un solo evento di creazione (niente "creato" seguito da "modificato" per colonne impostate subito dopo).
7. I segreti sono mascherati nell'input e gli errori di validazione registrati con outcome `invalid`.
8. Login, logout e login fallito si registrano.
9. Le richieste negate (403) si registrano con outcome `denied`.
10. Le modifiche annullate da un rollback non si registrano.
11. I comandi artisan si registrano come `system` con il canale `console`.
12. Solo il ruolo autorizzato apre la pagina; filtri ed export funzionano.
13. Le voci non si modificano né cancellano (`LogicException`) e la pulizia toglie quelle oltre 24 mesi.
14. La diretta restituisce solo le voci con id successivo e non finisce nel registro.

Esegui i test e l'analisi statica/formattazione del progetto (es. Pint, PHPStan/Larastan se presenti) e correggi gli errori. Alla fine elencami i file creati e modificati.
