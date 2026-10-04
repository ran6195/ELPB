# Piano: libreria media per cliente

> Creato: 2026-10-04 — Stato: **Fasi 1-4 completate in locale** — Fase 3 da eseguire in produzione dopo revisione del dry-run

Gestione centralizzata di immagini e video per ogni cliente (company), con possibilità futura di modificare le immagini (ridimensionamento, ritaglio, filtri).

## Situazione di partenza

- **Upload senza proprietario.** `UploadController` salva tutto in due cartelle condivise (`uploads/images/`, `uploads/videos/`) con nomi `uniqid()`. Nel database non resta traccia di chi ha caricato cosa (al 2026-10-04: 31 immagini / 13 MB, 2 video / 50 MB).
- **URL assoluti.** Nel JSON dei blocchi finisce l'URL completo (`APP_URL/uploads/...`): i renderer standalone e Joomla non vanno toccati.
- **Upload sparsi.** 7 handler in `BlockEditor.vue` (immagine, sfondo hero, video hero, video, mappa, servizi, slide) e 1 in `PageSettings.vue`, ognuno con il suo `<input type="file">`: un file già caricato non si può riusare.
- **Sicurezza.** Il tipo del file viene da `getClientMediaType()` (dichiarato dal browser, falsificabile). Ammessi anche SVG (possibile XSS) e HEIC/TIFF (non mostrati dai browser).
- **Ambiente.** `gd` ed `exif` presenti in locale (bastano per miniature e ridimensionamento); da verificare su Aruba. Imagick e ffmpeg probabilmente assenti sul condiviso.
- **"Cliente" = company.** `users.company_id` può essere vuoto (admin, utenti senza azienda).

## Architettura

### Tabella `media`
```
id, company_id (FK, nullable), user_id (FK, nullable),
type (image|video), disk, path, thumbnail_path,
original_name, mime_type, size,
width, height, duration (video), alt_text,
parent_id (FK media, versioni modificate),
created_at, updated_at, deleted_at
```

L'URL **non** viene salvato: è calcolato da `disk` + `path`, così cambiare storage o dominio non richiede di riscrivere la tabella.

### Struttura percorsi
```
media/c{company_id}/images/2026/10/<random>.jpg
media/c{company_id}/images/2026/10/thumbs/<random>.jpg
media/c{company_id}/videos/2026/10/<random>.mp4
media/u{user_id}/...            ← utenti senza azienda
```

### Astrazione storage
- Interfaccia `App\Storage\MediaStorage` (`putFile`, `delete`, `exists`, `url`, `size`).
- Driver `LocalMediaStorage` (filesystem, `backend/public/uploads/`).
- `MediaStorageFactory` sceglie il driver da `.env` (`MEDIA_DISK`).
- Un futuro driver S3-compatibile si aggiunge senza toccare controller e frontend.

### Visibilità (stessa logica di `canViewPage`)
- admin: tutto, con filtro per azienda;
- company: tutti i media della sua azienda;
- user: media della sua azienda (o solo i suoi se senza azienda) — **da confermare**.

## Object storage cloud: valutazione

**Decisione: non ora, ma l'architettura è pronta.**

Motivi per rimandare:
- volumi attuali piccoli (~63 MB);
- un provider esterno aggiunge credenziali, costi, CORS e un punto di guasto in più;
- gli URL assoluti già salvati nei blocchi restano validi solo se i file esistenti non si spostano.

Quando conviene passare:
- i **video** crescono: lo streaming dal condiviso Aruba consuma banda e regge male più visitatori;
- serve backup/ridondanza dei media separato dal server;
- più server o renderer remoti in crescita (ilprodotto.it ecc.).

Provider candidati (tutti S3-compatibili):
| Provider | Pro | Contro |
|---|---|---|
| Cloudflare R2 | Nessun costo di traffico in uscita, CDN integrata | Account Cloudflare |
| Hetzner Object Storage | UE (GDPR), economico | Nessuna CDN inclusa |
| Backblaze B2 | Molto economico, egress gratuito via Cloudflare | Server UE da scegliere esplicitamente |
| AWS S3 | Standard di riferimento | Egress costoso |

Per il passaggio: driver `S3MediaStorage` (via `league/flysystem-aws-s3-v3` o `aws/aws-sdk-php`), `MEDIA_DISK=s3` per i nuovi file; i file esistenti restano sul disco locale (campo `disk` per record). Stima: 2-3 ore.

## Fasi

### Fase 1 – Backend (~4-5 h)
1. ✅ Migration `create_media_table.php` + model `Media` (SoftDeletes).
2. ✅ Astrazione storage (`MediaStorage`, `LocalMediaStorage`, factory, config `.env`).
3. ✅ `MediaService`: tipo reale via `finfo`, dimensioni via `getimagesize`, correzione orientamento EXIF, miniatura 400px con GD. Nessuna quota per azienda (decisione 2026-10-04).
4. ✅ `MediaController`: `GET /api/media` (filtri `type`, `search`, `company_id` solo admin, `archived`, paginazione), `POST /api/media` (`file`, opz. `poster`/`width`/`height`/`duration`), `GET|PUT|POST /api/media/{id}`, `GET /api/media/{id}/usage`, `DELETE /api/media/{id}` (archivia; 409 con elenco pagine se in uso, salvo `?confirm=1`), `POST /api/media/{id}/restore`, `DELETE /api/media/{id}/force` (solo archiviati, rimuove i file).
5. ✅ Formati: JPEG, PNG, GIF, WebP, AVIF / MP4, WebM, MOV. SVG rimosso.
6. ✅ Le route `/api/upload/image|video` restano, delegate a `MediaService` (stessa risposta + campo `media`).
7. ✅ `EmailService`: l'immagine allegata alla email di conferma viene risolta tramite `MediaService::localPathFromUrl()` (libreria + vecchi upload).

### Fase 2 – Frontend ✅
1. ✅ `stores/mediaStore.js` (Pinia, solo azioni API: le liste vivono nei componenti) + `utils/media.js` (validazione client, formattazione, `readVideoInfo`).
2. ✅ Vista `/media` "Libreria Media" (`views/MediaLibrary.vue`): filtri Tutti/Immagini/Video/Archiviati, ricerca, filtro azienda (admin), upload multiplo drag&drop con avanzamento, pannello dettaglio (anteprima, info, URL copiabile, nome/alt, utilizzo con link all'editor, archivia con avviso se in uso, ripristina, elimina definitivamente). Pulsante "Media" nella dashboard.
3. ✅ `components/media/`: `MediaPicker.vue` (finestra Libreria / Carica nuovo, doppio clic o "Inserisci"), `MediaField.vue` (pulsante che apre il picker), `MediaDropzone.vue`, `MediaThumb.vue`.
4. ✅ Sostituiti i 10 campi upload di `BlockEditor.vue` (rimossi 7 handler) e l'upload allegato di `PageSettings.vue`; il campo URL manuale resta.
5. ✅ Miniatura video catturata nel browser (`<video>` + canvas) e inviata come `poster` con dimensioni e durata.

### Fase 3 – Migrazione file esistenti ✅ (script pronto)
Script `backend/scripts/migrate-existing-uploads.php`. **Non sposta né modifica file, non tocca blocchi/pagine**: crea solo record `media` (`legacy = 1`) con `path = images/<file>` → URL calcolato identico a quello già salvato nelle LP (verificato in locale su tutti i 9 file in uso, compreso l'allegato email).
- Default **dry-run**: report a video + JSON in `storage/logs/media-migration-*-dryrun.json` (da importare, non usati → admin, condivisi tra proprietari, formati non supportati, citati ma assenti su disco).
- Riferimenti cercati in `blocks.content/styles` e nei campi JSON di `pages`, abbinati per nome file ignorando l'host (`localhost`, http/https, vecchi domini).
- Proprietario: azienda/utente più frequente tra le pagine attive (pubblicate pesano di più); non usati → primo admin.
- `--execute` crea i record (data = data del file) e le miniature in `uploads/images/thumbs/`; `--no-thumbnails` per saltarle. Rieseguibile (salta i file già presenti).
- Rollback: `DELETE FROM media WHERE legacy = 1;` + `rm -r public/uploads/images/thumbs`.

**Procedura produzione**
1. Backup DB.
2. `php database/migrations/create_media_table.php` e `php database/migrations/add_legacy_to_media.php`.
3. `php scripts/migrate-existing-uploads.php` (dry-run) → revisione del report.
4. `php scripts/migrate-existing-uploads.php --execute`.

### Fase 4 – Eliminazione sicura ✅
- Archiviazione: 409 con elenco pagine se il file è in uso, salvo conferma esplicita.
- Eliminazione definitiva: **rifiutata dal backend (409) se il file è ancora usato** in qualsiasi pagina (anche archiviata).
- File `legacy`: eliminazione definitiva **solo admin** (403 per gli altri), perché potrebbero essere usati fuori da questo database (pagine esportate, email, siti esterni).
- UI: pulsante sostituito da spiegazione quando l'eliminazione non è consentita; avviso specifico per i file legacy.

**Totale MVP: ~12-14 h**

### Fase 5 – Modifica immagini (futuro)
Modifiche **non distruttive**: ogni modifica crea una nuova versione (`parent_id`).

| Funzione | Approccio |
|---|---|
| Ridimensionamento | GD lato server, preset 1920/1280/800 |
| Ritaglio (16:9, 4:3, 1:1, libero) | `cropperjs` + ritaglio lato server |
| Filtri (B/N, seppia, luminosità, contrasto, saturazione, sfocatura) | Anteprima con filtri CSS, canvas → nuovo file |
| WebP / compressione | GD `imagewebp`, anche automatico all'upload |

Futuro: versioni 800/1280/1920px automatiche + `srcset` nei renderer.

## Decisioni prese (2026-10-04)
1. Utenti semplici: vedono tutti i media dell'azienda.
2. Nessuna quota spazio per azienda.
3. SVG rimosso dai formati ammessi.

## Domande aperte
1. Admin: libreria unica con filtro o anche media "condivisi" con tutti?
2. Verificare GD (con supporto WebP) attivo su Aruba (`phpinfo()`).
