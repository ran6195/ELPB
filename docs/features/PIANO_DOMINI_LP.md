# Piano: domini associati alle landing page

> Creato: 2026-10-26 — Stato: **Fase 1 completata in locale**, da testare — fasi 2-4 da fare. Niente deploy finché non è testato in locale.

Associare una LP a uno o più domini (i siti del cliente su cui è pubblicata), per dare ai clienti delle agenzie dati per sito: lead per dominio, visite e conversioni. L'associazione è **facoltativa**: le pagine senza domini, comprese tutte quelle già online, continuano a funzionare esattamente come oggi.

## Situazione di partenza

- **Caricamento della pagina da server a server.** I renderer (standalone `page.php`, componente Joomla) chiamano `GET /api/page/{slug}` con cURL. L'API vede solo l'IP del server del renderer: oggi non sa su quale dominio la pagina viene mostrata.
- **Lead inviati dal browser.** Il form fa `fetch(apiUrl + '/leads')` direttamente dal sito del cliente: il browser aggiunge da solo l'header `Origin` (es. `https://www.cliente.it`). **L'origine dei lead è quindi registrabile subito, anche per le LP già online, senza aggiornare i renderer.**
- **Nessuna statistica visite.** Gli unici dati sono i lead (più GTM, esterno al sistema).
- **Renderer installati:** circa 20, gestiti via FTP (standalone in `/lp/`, Joomla `com_landingpages` v2.0.7).
- **Proprietà:** le risorse appartengono a un'azienda o, se l'utente non ne ha, al singolo utente. Si riusa il trait `Concerns/OwnedByCompany` della libreria media.
- **Lead:** elenco caricato tutto insieme da `GET /api/leads`, con filtri ed export CSV lato client in `CompanyPanel.vue` e `AdminPanel.vue`.

## Decisioni prese (2026-10-26)

| Tema | Decisione |
|---|---|
| Gestione domini | Anagrafica per azienda, riutilizzabile tra le pagine |
| Dati per i clienti | Lead per dominio; visite e conversioni |
| Visite | Conteggio **senza cookie** e senza dati personali |
| Vincolo | Rigido per pagina, attivato gradualmente; una pagina vincolata servita da un renderer **non aggiornato** viene mostrata comunque |
| Pagine future | Associazione facoltativa |
| Deploy | Solo dopo test in locale |
| Gestione anagrafica | Admin e responsabile aziendale; gli utenti associano alle proprie pagine i domini esistenti |
| Lead vecchi | Dominio stimato per le pagine con un solo dominio, con avvertenza "pagina vecchia, dato non certo" |
| Email di notifica | Indica il sito di provenienza |
| Conservazione statistiche | 16 mesi, come GA4 |

## Fasi

| Fase | Contenuto | Renderer da aggiornare |
|---|---|---|
| 1 | Anagrafica domini, associazione alle pagine, origine dei lead, filtri ed export per dominio | No |
| 2 | Renderer v2.1 (standalone + Joomla) inviano dominio e versione; registro degli accessi dei renderer | Sì |
| 3 | Visite senza cookie e conversioni per pagina e dominio | Sì |
| 4 | Vincolo per pagina, con avviso se la usano renderer non aggiornati | Sì |

Le fasi 2-4 escono insieme lato renderer (un solo aggiornamento dei ~20 siti), ma lato backend si possono rilasciare una alla volta.

---

## Fase 1 — Domini e origine dei lead

### Database

```
domains
  id, company_id (FK companies, nullable, cascade),
  user_id (FK users, nullable, set null),
  hostname VARCHAR(253)   -- normalizzato, vedi sotto
  label VARCHAR(100) NULL -- es. "Sito principale Rossi"
  created_at, updated_at
  INDEX (company_id, hostname)

domain_page
  page_id (FK pages, cascade), domain_id (FK domains, cascade)
  PRIMARY KEY (page_id, domain_id)

leads (+ colonne nullable)
  origin_host VARCHAR(253) NULL  -- dominio da cui è stato inviato il form, normalizzato
  domain_id (FK domains, nullable, set null)
  INDEX (domain_id)
```

- Unicità di `hostname` per proprietario controllata nel codice, perché MySQL non applica `UNIQUE` quando `company_id` è NULL.
- Lo stesso hostname può esistere in due aziende diverse (es. un cliente che cambia agenzia).
- Migration `create_domains_tables.php` + equivalente `backend/database/sql/domini_lp.sql` per phpMyAdmin, con rollback in testa (come `media_library.sql`).

### Normalizzazione dell'hostname

Funzione unica `App\Support\Hostname::normalize()`, usata ovunque:
- accetta anche URL completi (`https://www.Cliente.it:443/lp/`) → estrae solo l'host
- minuscolo, senza porta, senza punto finale
- **`www.` rimosso**: `www.cliente.it` e `cliente.it` sono lo stesso sito; gli altri sottodomini restano distinti (`shop.cliente.it` ≠ `cliente.it`)
- domini con caratteri accentati convertiti in punycode (`idn_to_ascii`, se l'estensione `intl` è presente su Aruba; altrimenti salvati come scritti)
- rifiuta IP, `localhost` e valori non validi

### Origine del lead (`LeadController::store`)

1. Legge `Origin`; se manca, `Referer`. Normalizza → `origin_host`.
2. Cerca `domain_id` prima tra i domini associati alla pagina, poi tra i domini dello stesso proprietario.
3. Non blocca mai l'invio: se l'header manca o non è valido, il lead viene salvato come oggi con `origin_host` NULL.

Stati mostrati in interfaccia, calcolati e non salvati:
- **dominio associato**: `domain_id` presente
- **anteprima**: `origin_host` è l'host dell'applicazione (`APP_URL`, `localhost`)
- **dominio non previsto**: la pagina ha domini associati ma `origin_host` non è tra questi (pagina copiata o pubblicata sul sito sbagliato)
- **origine sconosciuta**: lead precedenti al rilascio, o header assente

### API

| Metodo | Route | Note |
|---|---|---|
| GET | `/api/domains` | domini visibili (`visibleTo`), con numero di pagine e di lead |
| POST | `/api/domains` | `{hostname, label}`; 409 se l'hostname esiste già per lo stesso proprietario |
| PUT | `/api/domains/{id}` | modifica etichetta/hostname |
| DELETE | `/api/domains/{id}` | toglie le associazioni; i lead restano con `origin_host`, `domain_id` diventa NULL |
| PUT | `/api/pages/{id}/domains` | `{domain_ids: []}`; solo domini dello stesso proprietario della pagina |
| GET | `/api/pages/{id}/domain-suggestions` | hostname da cui la pagina ha ricevuto lead ma non ancora associati |

- `GET /api/pages/{id}` e la lista pagine includono i domini associati.
- `GET /api/leads` include `origin_host` e `domain`.
- **La risposta pubblica `/api/page/{slug}` non espone i domini.**

### Frontend

- **Anagrafica domini**: nuova sezione nel pannello aziendale (e nell'admin, con filtro per azienda). Tabella con hostname, etichetta, pagine e lead collegati; crea/modifica/elimina.
- **Impostazioni pagina (`PageSettings.vue`)**: sezione "Domini" con selezione multipla dall'anagrafica, "aggiungi nuovo dominio" senza uscire dalla pagina e domini suggeriti dai lead ricevuti ("Ricevuti lead da `cliente.it`: associare?").
- **Lead (`CompanyPanel.vue`, `AdminPanel.vue`)**: colonna Dominio con badge per anteprima/non previsto, filtro per dominio accanto a quello per pagina, colonna `Dominio` nell'export CSV.
- **Dashboard (`PageList.vue`)**: i domini associati sotto lo slug nelle card.

### Lead esistenti (facoltativo)

Script `scripts/backfill-lead-domains.php` (dry-run di default, `--execute`): per le pagine associate a **un solo** dominio, attribuisce quel dominio ai lead vecchi senza origine. Dato stimato, non misurato: va marcato (colonna `origin_inferred`) e mostrato come tale. Da decidere se farlo.

---

## Fase 2 — Renderer v2.1 e registro degli accessi

### Renderer

`page.php` (standalone) e `controller.php`/`default.php` (Joomla) aggiungono alla chiamata cURL:
```
X-ELPB-Host: <$_SERVER['HTTP_HOST']>
X-ELPB-Renderer: standalone/2.1.0   (oppure joomla/2.1.0)
```
Nessun'altra modifica al rendering. I pacchetti zip (`renderer-lp-elpb.zip`, `-nolegal.zip`) e il componente Joomla vanno rigenerati.

### Registro `renderer_hits`

```
renderer_hits
  id, page_id (FK, cascade), host VARCHAR(253) NULL,
  renderer VARCHAR(20) NULL, version VARCHAR(20) NULL,
  ip VARCHAR(45), hits INT, first_seen_at, last_seen_at
  UNIQUE (page_id, host, renderer, ip)
```

- Aggiornato in `showBySlug` con un upsert per chiamata: una query in più, senza effetti sulla risposta.
- Chiamata senza header → riga con `host` NULL = **renderer non aggiornato**, identificato dall'IP del server.
- In interfaccia, per ogni pagina: "Pubblicata su: `cliente.it` (standalone 2.1.0, ultimo accesso ieri) · 1 renderer non aggiornato (IP 1.2.3.4)". Serve a sapere quali dei ~20 siti mancano all'appello e a suggerire domini da associare.

---

## Fase 3 — Visite e conversioni senza cookie

### Raccolta

- I renderer v2.1 includono un piccolo script che, a pagina caricata, invia **una** segnalazione con `navigator.sendBeacon` a `POST /api/track/view` con `{page_id, host}`.
- Niente cookie, niente localStorage, nessun identificativo salvato nel browser.
- **Visitatori unici** stimati con `sha256(sale_del_giorno + IP + user agent + page_id)`. Il sale cambia ogni giorno e quello vecchio viene cancellato: il giorno dopo non è più possibile risalire a nulla (stesso approccio di Plausible).
- Bot esclusi: user agent noti (bot, crawler, spider, headless) e richieste senza JavaScript (il beacon parte solo se lo script gira).
- L'anteprima su `elpb.edysma.com` non viene conteggiata.

### Database

```
page_views_daily
  page_id (FK, cascade), host VARCHAR(253), day DATE,
  views INT, visitors INT
  PRIMARY KEY (page_id, host, day)

page_view_visitors   -- solo giorno corrente, svuotata ogni giorno
  day DATE, visitor_hash CHAR(64)
  PRIMARY KEY (day, visitor_hash)

tracking_salts
  day DATE PRIMARY KEY, salt CHAR(64)
```

Un upsert per visita, senza tabelle che crescono per evento: dimensione prevedibile anche su Aruba.

### Statistiche

- Conservazione: aggregati giornalieri cancellati dopo **16 mesi** (come GA4), con un'attività pianificata Plesk giornaliera che svuota anche `page_view_visitors` e i sali vecchi.
- Nuova vista "Statistiche" per pagina: visite, visitatori, lead e **tasso di conversione** (lead / visitatori), per giorno e per dominio, con filtro sul periodo.
- Riepilogo per dominio nell'anagrafica.
- Le visite partono dal giorno in cui il renderer del sito viene aggiornato: prima di allora le conversioni non sono calcolabili (lo mostriamo).

---

## Fase 4 — Vincolo per pagina

- Nuova colonna `pages.domain_lock` (boolean, default false). Attivabile solo se la pagina ha almeno un dominio associato.
- In `showBySlug`:
  - `domain_lock` disattivo → come oggi
  - attivo e header `X-ELPB-Host` presente ma **non** tra i domini associati → 404 come pagina non pubblicata, con registrazione nel log degli accessi
  - attivo e header assente (renderer vecchio) → **pagina mostrata comunque** e accesso registrato
  - richieste dall'anteprima dell'applicazione sempre consentite
- In `PageSettings`, l'interruttore mostra i renderer che usano la pagina e avvisa se qualcuno non è aggiornato o gira su un dominio non associato ("attivando il vincolo, `vecchio-sito.it` smetterà di mostrare la pagina").

### Passaggio per le pagine già online

1. Rilascio fase 1: i lead iniziano a registrare il dominio d'origine.
2. Associazione dei domini alle pagine vecchie, aiutata dai suggerimenti.
3. Aggiornamento dei renderer sito per sito, senza fretta: tutte le pagine restano online.
4. Attivazione del vincolo pagina per pagina, quando il registro mostra solo renderer aggiornati su domini associati.

---

## Criticità

1. **Il vincolo protegge dagli errori, non dagli abusi.** `Origin`, `Referer` e `X-ELPB-Host` si possono falsificare con uno script. Va bene per statistiche e per evitare pubblicazioni sul sito sbagliato, non come controllo di sicurezza.
2. **I dati per dominio partono dal rilascio.** I lead già ricevuti non hanno origine (salvo il backfill stimato); le visite partono dall'aggiornamento di ciascun renderer.
3. **`Origin` può mancare.** Alcune estensioni privacy o configurazioni di `Referrer-Policy` lo eliminano: quei lead risultano con origine sconosciuta. Il lead viene comunque salvato.
4. **Privacy delle visite.** Il conteggio senza cookie e con hash giornaliero è pensato per non richiedere consenso, ma l'IP viene comunque elaborato per un istante. Da confermare con chi segue la privacy, e da citare nella cookie/privacy policy generata (`menu-legali/`).
5. **Carico su Aruba.** Ogni visita è una richiesta PHP + 2-3 query. Per i volumi delle LP è trascurabile; in caso di campagne con molto traffico si può passare a un file di coda elaborato da un'attività pianificata Plesk.
6. **Estensione `intl`** per i domini accentati: da verificare in produzione (si aggiunge il controllo a `check-media-requirements.php` o a uno script dedicato).
7. **Deploy senza SSH.** Ogni fase porta un `.sql` da eseguire in phpMyAdmin **prima** di caricare il backend (il codice nuovo legge le nuove colonne).
8. **Aggiornamento dei renderer.** ~20 siti via FTP: da fare con calma dopo il rilascio delle fasi 2-4; il registro degli accessi dice quali mancano.

## File — Fase 1 (realizzata)

Backend: `database/migrations/create_domains_tables.php`, `database/sql/domini_lp.sql`, `src/Utils/Hostname.php`, `src/Models/Domain.php`, `src/Models/Page.php` (relazione `domains`), `src/Models/Lead.php` (`originStatus()`), `src/Controllers/DomainController.php`, `src/Controllers/PageController.php` (`updateDomains`, `domainSuggestions`, domini nelle risposte), `src/Controllers/LeadController.php` (origine dal browser), `src/Services/EmailService.php` (riga "Sito"), `scripts/backfill-lead-domains.php`, `public/index.php` (route).

Frontend: `stores/domainStore.js`, `utils/leadOrigin.js`, `components/domains/PageDomains.vue` (sezione nelle impostazioni pagina), `components/domains/DomainManager.vue` (scheda Domini), `components/PageSettings.vue`, `views/CompanyPanel.vue`, `views/AdminPanel.vue`, `views/PageList.vue`.

### Differenze rispetto al piano
- `Hostname` sta in `src/Utils/` (cartella già esistente) invece di `src/Support/`.
- `POST /api/domains` accetta anche `page_id`: crea il dominio con il proprietario della pagina e lo associa (pulsanti "Aggiungi" e "Aggiungi e associa" nelle impostazioni pagina). Serve anche all'admin che lavora su pagine di altre aziende.
- `DELETE /api/domains/{id}` non chiede conferma lato API: la conferma è nell'interfaccia, che elenca le pagine da cui il dominio verrà tolto.
- Associando un dominio a una pagina, i lead già arrivati da quel sito (origine misurata) vengono collegati al dominio.
- Il dominio "anteprima" è l'host di `APP_URL`: in locale (`localhost:8000`) `elpb.edysma.com` risulta "non previsto", in produzione è anteprima.

### Deploy fase 1 (quando approvato)
1. Backup del database, poi `backend/database/sql/domini_lp.sql` in phpMyAdmin.
2. `./scripts/deploy-ftp.sh` (prova) e `--execute`.
3. Facoltativo: `backend/scripts/backfill-lead-domains.php` come attività pianificata Plesk, prima senza argomenti e poi con `--execute`, **dopo** aver associato i domini alle pagine vecchie (stima solo le pagine con un solo dominio).
