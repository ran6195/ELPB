# PRD - Product Requirements Document
## Landing Page Builder — Backlog Implementazioni Future

> **Documento creato:** 2026-03-26
> **Ultimo aggiornamento:** 2026-03-26 (rev 2)
> **Autore:** Francesco (committente) + Claude Code

---

## Formato delle voci

Ogni voce segue la struttura:
- **Priorità**: Alta / Media / Bassa
- **Complessità**: S (< 1h) / M (1-3h) / L (3-6h) / XL (> 6h)
- **Stato**: `[ ] Da fare` / `[x] Completato` / `[~] In corso`

---

## SPRINT 1 — Prossime implementazioni

### F-001 · Hero Block — Larghezza limitata dal container pagina
**Priorità**: Alta | **Complessità**: M (1-2h) | **Stato**: [ ] Da fare

**Problema**
Il blocco Hero è sempre a piena larghezza (full-width) indipendentemente dall'impostazione "Larghezza pagina" in PageSettings. Tutti gli altri blocchi (CTA, Text, Features…) rispettano il `containerWidth` della pagina; il Hero no.

**Contesto tecnico**
- Il Hero ha già un `contentMaxWidth` che limita l'area del *contenuto interno* (testo + pulsanti), ma il blocco esterno (sfondo/immagine) rimane sempre `100vw`.
- La larghezza pagina è definita in `Page.styles.containerWidth` (es. `max-w-7xl`).
- I renderer PHP (`BlockRenderer.php`, `blockrenderer.php` Joomla) non leggono `containerWidth` per il Hero.

**Soluzione proposta**
Aggiungere un toggle **"Larghezza limitata"** nelle impostazioni del blocco Hero:
- `OFF` (default): comportamento attuale — full-width sfondo + contentMaxWidth interno
- `ON`: applica il `containerWidth` della pagina anche al blocco Hero (wrapper esterno), con `overflow: hidden` e `border-radius` opzionale

**File da modificare**
- `frontend/src/components/blocks/HeroBlock.vue` — leggere prop e applicare classe/stile
- `frontend/src/components/BlockEditor.vue` — aggiungere toggle nella sezione Hero
- `standalone-renderer/BlockRenderer.php` — sezione `case 'hero'`
- `joomla-component/.../blockrenderer.php` — sezione hero

**Criteri di accettazione**
- [ ] Toggle visibile in BlockEditor sezione Hero
- [ ] Con toggle OFF: nessun cambiamento rispetto a oggi
- [ ] Con toggle ON: il Hero si comporta come gli altri blocchi rispettando la larghezza pagina
- [ ] Funziona sia nell'anteprima Vue che nei renderer PHP (standalone + Joomla)
- [ ] Retrocompatibile (pagine esistenti non cambiano aspetto)

---

### F-002 · Blocco CTA — Controllo interlinea (line-height)
**Priorità**: Alta | **Complessità**: S (30-45 min) | **Stato**: [ ] Da fare

**Problema**
Il blocco CTA non permette di regolare l'interlinea del testo. Il blocco Text ha già questa funzionalità (7 opzioni, da 1 a 2), ma non è stata replicata sul CTA.

**Contesto tecnico**
- `TextBlock.vue` applica `lineHeight` tramite computed `textStyles` con default `1.625`
- `BlockEditor.vue` ha già il selettore lineHeight per Text (righe ~443-456)
- `CtaBlock.vue` usa `v-html` per titolo e descrizione senza stili aggiuntivi
- `BlockRenderer.php` sezione CTA non include lineHeight

**Soluzione proposta**
Replicare esattamente il pattern già usato per il blocco Text:
- Aggiungere campo `lineHeight` in `CtaBlock.vue` con computed styles
- Aggiungere lo stesso selettore (7 opzioni) in `BlockEditor.vue` sezione CTA
- Aggiornare `BlockRenderer.php` e `blockrenderer.php` per renderizzare il lineHeight

**File da modificare**
- `frontend/src/components/blocks/CtaBlock.vue`
- `frontend/src/components/BlockEditor.vue` — sezione CTA
- `standalone-renderer/BlockRenderer.php` — sezione `case 'cta'`
- `joomla-component/.../blockrenderer.php` — sezione cta

**Criteri di accettazione**
- [ ] Selettore lineHeight visibile in BlockEditor sezione CTA (stesso UI del blocco Text)
- [ ] Default: `1.625` (nessun cambiamento visivo per pagine esistenti)
- [ ] Funziona in anteprima Vue e renderer PHP
- [ ] Opzioni: Compatta (1) / Stretta (1.25) / Normale Stretta (1.375) / Normale (1.5) / Comoda (1.625) / Spaziosa (1.75) / Molto Spaziosa (2)

---

### F-003 · Blocco Text — Controllo interlinea (line-height)
**Priorità**: Alta | **Complessità**: S (già implementato lato Vue) | **Stato**: [ ] Verifica renderer

**Nota**
Il blocco Text ha già `lineHeight` implementato in Vue (TextBlock.vue + BlockEditor.vue). Verificare che sia correttamente passato anche nei renderer PHP.

**File da verificare**
- `standalone-renderer/BlockRenderer.php` — sezione `case 'text'` — cerca `lineHeight`
- `joomla-component/.../blockrenderer.php` — sezione text

**Criteri di accettazione**
- [ ] Il lineHeight impostato in editor si riflette nel renderer standalone
- [ ] Il lineHeight impostato in editor si riflette nel renderer Joomla

---

### F-004 · Profilo Utente — Email modificabile dall'utente loggato
**Priorità**: Alta | **Complessità**: M (2-3h) | **Stato**: [ ] Da fare

**Problema**
L'email del proprietario è visibile solo in sola lettura (PageSettings mostra `currentUserEmail` read-only). Non esiste una pagina profilo dove l'utente può aggiornare la propria email o password. La modifica email è attualmente possibile solo dagli admin tramite `PUT /api/admin/users/{id}`.

**Contesto tecnico**
- Backend: esiste `AuthController::updateUser()` ma è protetto da ruolo admin
- Frontend: `authStore` ha `user.email`, ma non c'è endpoint per auto-aggiornamento
- Non esiste una view/componente "Profilo Utente"

**Soluzione proposta**

**Backend (30 min)**
Aggiungere endpoint dedicato per self-update:
```
PUT /api/auth/me
```
Permette all'utente autenticato di aggiornare: `name`, `email`, `password`
- Verifica unicità email
- Richiede conferma password attuale per cambio email/password
- Aggiorna il token JWT restituendo il nuovo token (o richiede re-login)

**Frontend (1.5-2h)**
Creare componente `UserProfile.vue` o integrarlo nella dashboard esistente:
- Sezione "Il mio profilo" con form: Nome, Email, Nuova password (opzionale), Conferma password attuale
- Route `/profile` o modale accessibile dall'header/avatar
- Aggiornare `authStore` dopo salvataggio con i nuovi dati utente
- Feedback visivo (successo/errore)

**File da creare/modificare**
- `backend/src/Controllers/AuthController.php` — aggiungere `updateMe()`
- `backend/public/index.php` — aggiungere route `PUT /api/auth/me`
- `frontend/src/views/UserProfile.vue` — nuovo componente (oppure modale in App.vue)
- `frontend/src/stores/authStore.js` — aggiungere action `updateProfile()`
- `frontend/src/router/index.js` — aggiungere route `/profile`

**Criteri di accettazione**
- [ ] Utente può modificare il proprio nome
- [ ] Utente può modificare la propria email (con verifica unicità)
- [ ] Utente può cambiare password (richiede conferma password attuale)
- [ ] Dopo salvataggio, i dati aggiornati sono visibili immediatamente in UI
- [ ] Solo l'utente stesso può modificare il proprio profilo (non altri utenti)
- [ ] Admin mantiene la capacità di modificare qualsiasi utente via AdminPanel

---

---

### F-005 · Blocco Text — Allineamento paragrafi + Titolo / Blocco Rich Text Avanzato
**Priorità**: Media | **Complessità**: M (1.5-3h a seconda dell'opzione scelta) | **Stato**: [ ] Da fare — decisione aperta

**Analisi situazione attuale**
L'allineamento del **corpo del testo** è **già completamente implementato**:
- `RichTextEditor.vue` usa VueQuill con toolbar che include `{ align: ['', 'center', 'right', 'justify'] }`
- `TextBlock.vue` ha già i CSS per `.ql-align-center`, `.ql-align-right`, `.ql-align-justify`
- `page.php` (standalone renderer) ha già i CSS equivalenti
- **L'utente può già allineare il testo usando i pulsanti di allineamento nella toolbar dell'editor**

Ciò che **manca**:
- Allineamento del **titolo** (campo `h2` editato via `contenteditable`, senza toolbar di formattazione)

---

**Opzione A — Soluzione minimale: aggiungere allineamento al titolo** *(S, ~30 min)*

Aggiungere un selettore `textAlign` per il titolo h2 del blocco Text in `BlockEditor.vue`, con le stesse 4 opzioni (sinistra/centro/destra/giustificato). Applicare come inline style.

File da modificare:
- `frontend/src/components/blocks/TextBlock.vue` — aggiungere `titleAlign` a `blockStyles` o `titleStyle`
- `frontend/src/components/BlockEditor.vue` — selettore allineamento titolo nella sezione Text
- `standalone-renderer/BlockRenderer.php` — `renderText()` applica `text-align` al tag `<h2>`
- `joomla-component/.../blockrenderer.php` — idem

Criteri di accettazione:
- [ ] Selettore allineamento titolo (sinistra/centro/destra) in BlockEditor sezione Text
- [ ] Default: sinistra (nessun cambiamento per pagine esistenti)
- [ ] Funziona in anteprima Vue e renderer PHP

---

**Opzione B — Soluzione consigliata: nuovo blocco "Rich Text" puro WYSIWYG** *(M, 2-3h)*

Creare un nuovo tipo di blocco `rich-text` che espone **solo ed esclusivamente** il VueQuill editor, senza campi separati (titolo / corpo). L'utente gestisce tutto il contenuto con la toolbar WYSIWYG: titoli H1-H3, allineamenti, grassetto, elenchi, link, colori — nessun campo extra.

**Motivazione:** il blocco Text attuale è un ibrido con titolo `contenteditable` + corpo VueQuill. Un blocco puramente WYSIWYG è più intuitivo, più potente e più coerente dal punto di vista UX.

**Architettura proposta:**
- Nuovo componente `RichTextBlock.vue` — renderizza semplicemente `v-html` del contenuto VueQuill
- In `BlockEditor.vue` la sezione `rich-text` mostra solo `<RichTextEditor v-model="localBlock.content.html" />`
- I CSS `.ql-align-*` e gli stili prose già esistenti in `TextBlock.vue` e `page.php` si riusano
- Il campo dati è `content.html` (stringa HTML)

File da creare/modificare:
- `frontend/src/components/blocks/RichTextBlock.vue` — nuovo componente (semplice, ~30 righe)
- `frontend/src/components/BlockEditor.vue` — aggiungere sezione `rich-text` + import
- `frontend/src/components/PageEditor.vue` — aggiungere `rich-text` alla lista blocchi disponibili
- `standalone-renderer/BlockRenderer.php` — aggiungere `case 'rich-text'` (triviale: render html diretto)
- `joomla-component/.../blockrenderer.php` — idem

Criteri di accettazione:
- [ ] Nuovo blocco "Rich Text" disponibile nell'editor (lista blocchi)
- [ ] Editor mostra solo la toolbar VueQuill, nessun campo separato
- [ ] Supporta: H1-H3, allineamenti, grassetto/corsivo, elenchi, link, colori, blockquote
- [ ] Rendering identico in Vue e renderer PHP
- [ ] Il blocco Text esistente rimane invariato (retrocompatibilità)

---

**Decisione raccomandata**: implementare **Opzione B** come blocco aggiuntivo e **Opzione A** come fix rapido in parallelo per il titolo del blocco Text esistente. Totale stimato: 2.5-3.5h.

---

## BACKLOG — Idee future (non prioritizzate)

> Raccogliere qui le richieste del committente da valutare in sprint futuri.

| ID | Descrizione | Note |
|----|-------------|-------|
| B-001 | — | — |

---

## Note di Implementazione

### Pattern Renderer PHP
Quando si aggiunge una nuova proprietà di stile a un blocco, aggiornare **sempre entrambi** i renderer:
1. `standalone-renderer/BlockRenderer.php`
2. `joomla-component/com_landingpages/site/helpers/blockrenderer.php`

### Pattern lineHeight (riferimento)
Il blocco Text è il riferimento per lineHeight. Vedere:
- Vue: `TextBlock.vue` computed `textStyles`
- Editor: `BlockEditor.vue` sezione "text" con `<select v-model="localBlock.content.lineHeight">`
- PHP: cercare `lineHeight` in `BlockRenderer.php` sezione text

### Retrocompatibilità
Tutte le nuove proprietà devono avere valori di default che non cambiano il comportamento delle pagine esistenti (usare `?? defaultValue` in PHP, `|| 'defaultValue'` in JS).
