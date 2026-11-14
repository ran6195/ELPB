# Libreria media: come funziona

Questo documento spiega come funziona la libreria media dell'applicazione: dove si trovano
immagini e video, chi li vede, come si caricano, si organizzano, si modificano e si eliminano
senza rompere le landing page. Le sezioni dalla 1 alla 9 si leggono senza competenze
tecniche. Alla fine c'è un'appendice tecnica con tabelle, rotte e file del codice.

---

## 1. A cosa serve

La libreria media raccoglie in un unico posto tutte le immagini e i video usati nelle landing
page. Un file si carica una volta sola e si riusa in quante pagine e blocchi si vuole.

Si apre dalla voce **Media** della barra di navigazione. La stessa libreria compare anche
nell'editor delle pagine: in ogni campo che accetta un'immagine o un video, il pulsante
**"Scegli dalla libreria"** apre la finestra di scelta.

---

## 2. Chi vede quali file

Ogni file appartiene a un'**azienda** (o, per gli utenti senza azienda, a un singolo utente).

| Ruolo | Che cosa vede |
|---|---|
| **Admin** | Tutti i file, con un filtro per azienda |
| **Responsabile aziendale** | Tutti i file della propria azienda |
| **Utente di un'azienda** | Tutti i file della propria azienda, anche quelli caricati dai colleghi |
| **Utente senza azienda** | Solo i propri file |

Un file caricato dentro una cartella prende il proprietario della cartella. Per esempio, se
un admin carica un'immagine nella cartella di un'azienda, il file appartiene a quell'azienda
e i suoi utenti lo vedono.

Non ci sono limiti di spazio per azienda.

---

## 3. Formati ammessi

| Tipo | Formati | Dimensione massima |
|---|---|---|
| Immagini | JPEG, PNG, GIF, WebP, AVIF | 100 MB |
| Video | MP4, WebM, MOV | 100 MB |

Il server controlla il **contenuto reale** del file, non solo l'estensione o quello che
dichiara il browser: un file rinominato non passa. SVG non è ammesso perché può contenere
codice eseguibile. HEIC e TIFF non sono ammessi perché la maggior parte dei browser non li
mostra.

---

## 4. Caricare i file

Si caricano dalla pagina **Media** (pulsante o trascinando i file nella finestra, anche più di
uno insieme, con l'avanzamento di ciascuno) oppure dalla scheda **"Carica nuovo"** della
finestra di scelta nell'editor. Il file va nella cartella aperta in quel momento.

Al caricamento succede questo:

- **Foto da smartphone**: le foto scattate in verticale vengono raddrizzate automaticamente
  (orientamento EXIF), così appaiono girate nel verso giusto in tutti i browser.
- **Miniatura**: per ogni immagine si crea una copia piccola (400 px, formato WebP) usata
  nelle griglie della libreria. Nelle pagine si usa sempre il file originale.
- **Video**: il server non ha strumenti per elaborare i video, quindi è il browser di chi
  carica a ricavare durata, dimensioni e un fotogramma da usare come anteprima. Se il browser
  non riesce a leggere il video (capita con alcuni MOV), il file viene caricato comunque, senza
  anteprima.
- **Nome del file**: sul server il file prende un nome casuale; il nome originale resta
  visibile nella libreria e si può cambiare.

L'immagine originale **non viene compressa né ridimensionata**: conviene caricare immagini
già della dimensione giusta per il web (per esempio non oltre 1920 px di larghezza). Per
ridurle si può usare l'editor delle immagini (sezione 7).

---

## 5. Sfogliare e cercare

La libreria mostra i file in una griglia con:

- filtri **Tutti / Immagini / Video / Archiviati**;
- **ricerca** per nome, che cerca in tutte le cartelle e indica in quale si trova ogni file;
- filtro per **azienda** (solo admin).

Cliccando un file si apre il **pannello di dettaglio**:

- anteprima, tipo, dimensioni, peso, durata (video), data e chi l'ha caricato;
- **URL** copiabile, da incollare in un campo o in un altro sito;
- **nome** e **testo alternativo** (alt) modificabili. Il testo alternativo descrive
  l'immagine per chi usa un lettore di schermo e per i motori di ricerca;
- **cartella** in cui si trova, modificabile;
- **dove è usato**: l'elenco delle pagine e dei blocchi che usano il file, con link all'editor.

---

## 6. Cartelle

Le cartelle servono solo a mettere ordine e si possono annidare (cartelle dentro cartelle).

- **Nuova cartella**, **Rinomina** ed **Elimina** dal menu della cartella.
- I file e le cartelle si **spostano trascinandoli** su un'altra cartella o sul percorso in
  alto, oppure scegliendo la cartella nel pannello di dettaglio.
- Nella stessa cartella non possono esserci due cartelle con lo stesso nome (maiuscole e
  minuscole contano come uguali).
- **Eliminare una cartella non cancella nessun file**: file e sottocartelle risalgono nella
  cartella superiore.
- L'indirizzo della pagina ricorda la cartella aperta, quindi si può salvare o condividere il
  link a una cartella.

Le cartelle esistono solo nell'applicazione: sul server i file **non si spostano mai**.
Per questo spostare un file o rinominare una cartella non cambia l'URL del file e non rompe
nessuna landing page.

---

## 7. Modificare un'immagine

Dal pannello di dettaglio, **"Modifica immagine"** apre l'editor:

| Funzione | Opzioni |
|---|---|
| Ritaglio | Libero, 1:1, 4:3, 3:2, 16:9, 9:16 |
| Rotazione e specchiatura | ±90°, orizzontale e verticale |
| Filtri pronti | Bianco e nero, Seppia, Vintage, Vivace, Luminoso, Tenue |
| Regolazioni | Luminosità, contrasto, saturazione, bianco e nero, seppia, sfocatura |
| Dimensione | 1920, 1280, 800 px, originale o personalizzata (mai ingrandita) |
| Formato | JPEG, WebP o PNG, con qualità regolabile |

La modifica **non tocca mai l'originale**: il salvataggio crea una **nuova immagine** nella
stessa cartella e per lo stesso proprietario, con lo stesso testo alternativo e un link
"Versione modificata di…" all'originale. Le pagine che usano l'originale restano come sono;
per usare la nuova versione la si sceglie nel blocco.

L'elaborazione avviene nel browser di chi modifica: quello che si vede in anteprima è
esattamente il risultato.

---

## 8. Archiviare ed eliminare

Ci sono due livelli, pensati per non rompere mai una pagina per errore.

### Archiviare

Il file sparisce dalla libreria (resta nel filtro **Archiviati**) ma **rimane sul server**:
le pagine che lo usano continuano a mostrarlo. Se il file è usato in qualche pagina, prima di
archiviarlo compare l'elenco delle pagine e serve una conferma.

Un file archiviato si può **ripristinare** in qualsiasi momento.

### Eliminare definitivamente

Si può fare solo su un file già archiviato e cancella il file dal server. Non si può
annullare.

L'eliminazione definitiva è **bloccata** se il file è ancora usato in una pagina, anche
archiviata: prima va tolto dai blocchi. Al posto del pulsante compare la spiegazione.

### Dove si cerca l'utilizzo

Si controllano i contenuti di tutti i blocchi di tutte le pagine (anche archiviate) e
l'immagine allegata all'email di conferma delle impostazioni di notifica. Non si possono
controllare le copie fuori dall'applicazione: pagine esportate, email già inviate, link
incollati in altri siti.

---

## 9. File caricati prima della libreria

I file caricati prima dell'introduzione della libreria (ottobre 2026) sono stati importati
così com'erano, senza spostarli né rinominarli: le pagine che li usavano non sono cambiate.
Il proprietario è stato dedotto dalle pagine che li usavano; quelli non usati sono stati
assegnati all'admin.

Questi file hanno l'indicazione dell'origine nel dettaglio e possono essere eliminati
definitivamente **solo da un admin**, perché potrebbero essere usati anche fuori da questa
applicazione.

---

## Appendice tecnica

### Dove stanno i file

```
backend/public/uploads/media/c{azienda}/images/AAAA/MM/<nome casuale>.jpg
backend/public/uploads/media/c{azienda}/images/AAAA/MM/thumbs/<nome casuale>.webp
backend/public/uploads/media/c{azienda}/videos/AAAA/MM/<nome casuale>.mp4
backend/public/uploads/media/u{utente}/...        ← utenti senza azienda
backend/public/uploads/images/, videos/           ← file importati (legacy)
```

L'URL non è salvato nel database: si calcola da `disk` + `path`. Nei blocchi delle pagine
finisce l'URL assoluto (`APP_URL/uploads/...`), che è quello che usano i renderer.

Lo storage passa da un'interfaccia (`MediaStorage`, driver `LocalMediaStorage`, scelta con
`MEDIA_DISK` nel `.env`): un driver S3-compatibile (Cloudflare R2, Hetzner, Backblaze) si
può aggiungere senza toccare controller e frontend. Valutazione in
`docs/features/PIANO_MEDIA_LIBRARY.md`.

### Tabelle

| Tabella | Contenuto |
|---|---|
| `media` | `company_id`, `user_id`, `folder_id`, `type`, `disk`, `path`, `thumbnail_path`, `original_name`, `mime_type`, `size`, `width`, `height`, `duration`, `alt_text`, `parent_id` (versione modificata), `legacy`, `deleted_at` (archiviazione) |
| `media_folders` | `company_id`, `user_id`, `parent_id`, `name` |

### Rotte

| Rotta | Uso |
|---|---|
| `GET /api/media` | Elenco paginato: `type`, `search`, `company_id` (admin), `archived`, `folder=root\|{id}` |
| `POST /api/media` | Caricamento (`file`, `folder_id`; per i video `poster`, `width`, `height`, `duration`) |
| `GET`, `PUT /api/media/{id}` | Dettaglio, modifica di nome, alt, cartella |
| `GET /api/media/{id}/usage` | Pagine e blocchi che usano il file |
| `GET /api/media/{id}/file` | File servito dall'API, per l'editor delle immagini (evita il blocco CORS sul canvas) |
| `POST /api/media/{id}/versions` | Salvataggio di una versione modificata |
| `POST /api/media/move` | Spostamento di più file `{ids, folder_id}` |
| `DELETE /api/media/{id}` | Archiviazione (409 con le pagine se in uso, salvo `?confirm=1`) |
| `POST /api/media/{id}/restore` | Ripristino |
| `DELETE /api/media/{id}/force` | Eliminazione definitiva (409 se in uso, 403 sui legacy per i non admin) |
| `GET`, `POST /api/media-folders`, `PUT`, `DELETE /api/media-folders/{id}` | Cartelle |
| `POST /api/upload/image\|video` | Rotte storiche, ora delegate a `MediaService` |

### File

- Backend: `src/Services/MediaService.php` (controllo del tipo con `finfo`, orientamento,
  miniature con GD, ricerca degli utilizzi), `src/Controllers/MediaController.php`,
  `src/Controllers/MediaFolderController.php`, `src/Models/Media.php`,
  `src/Models/MediaFolder.php`, `src/Models/Concerns/OwnedByCompany.php`, `src/Storage/*`.
- Frontend: `views/MediaLibrary.vue`, `components/media/` (`MediaPicker`, `MediaField`,
  `MediaDropzone`, `MediaThumb`, `ImageEditor`, `FolderTile`, `FolderBreadcrumb`),
  `stores/mediaStore.js`, `composables/useMediaFolders.js`, `utils/media.js`,
  `utils/imageFilters.js`.
- Script: `scripts/migrate-existing-uploads.php` (importazione dei vecchi file),
  `scripts/repair-media-orientation.php`, `scripts/check-media-requirements.php`.
- Documento di progetto: `docs/features/PIANO_MEDIA_LIBRARY.md`.
