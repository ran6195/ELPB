# Piano: animazioni di comparsa allo scroll

Stato: **piano**, non implementato (2026-11-12).

## Obiettivo

Dare movimento alle landing page facendo comparire i blocchi mentre il visitatore scorre la pagina, e le card dei blocchi a griglia una dopo l'altra. Deve:

- funzionare uguale in anteprima Vue, renderer standalone e componente Joomla;
- non penalizzare il caricamento (LCP) né far saltare il layout (CLS);
- lasciare il contenuto **sempre visibile** se il JavaScript non parte, se il browser è vecchio o se il visitatore ha chiesto di ridurre il movimento;
- non richiedere modifiche al database né alle API.

Fuori da questo piano (idee successive): contatori animati, pulso/CTA fissa su mobile, parallasse dell'Hero.

## Cosa vede l'utente

### Impostazioni pagina (PageSettings, sezione Stile)

Nuovo gruppo **"Animazioni allo scroll"**:

| Campo | Valori | Predefinito |
|---|---|---|
| Attive | sì / no | **no** (le pagine esistenti non cambiano) |
| Effetto predefinito | Dissolvenza, Dal basso, Da sinistra, Da destra, Zoom | Dal basso |
| Durata | Veloce (400 ms), Normale (600 ms), Lenta (900 ms) | Normale |
| Elementi in sequenza | sì / no (card di Vantaggi e Griglia servizi una dopo l'altra) | sì |

Con l'interruttore spento i controlli sono disattivati e le impostazioni dei blocchi sono ignorate.

### Impostazioni blocco (BlockEditor, sezione comune vicino ad "Ancora")

Nuovo gruppo **"Animazione"**, mostrato per tutti i blocchi:

| Campo | Valori | Predefinito |
|---|---|---|
| Effetto | Come la pagina, Nessuna, Dissolvenza, Dal basso, Da sinistra, Da destra, Zoom | Come la pagina |
| Ritardo | 0, 100, 200, 300, 500 ms | 0 |

Se le animazioni della pagina sono spente, il gruppo mostra una nota "Attiva le animazioni nelle impostazioni della pagina" con il link al pannello pagina.

**"Come la pagina" diventa "Nessuna"** per i blocchi in cima o fissi, dove l'animazione fa più danni che altro: `header`, `hero`, `hero-wide`, `hero-video`. Si possono comunque animare scegliendo l'effetto a mano (con un avviso sull'Hero: "rallenta la comparsa del contenuto principale"). I Contatti rapidi (pulsanti fluttuanti) non sono un blocco e non si animano.

### Editor

Nel canvas dell'editor i blocchi restano **statici** (animarli disturberebbe la modifica). Nel gruppo "Animazione" del blocco c'è un pulsante **"Prova"** che riproduce l'animazione di quel blocco nel canvas una volta. Anteprima (`/preview/:id`) e pagina pubblica Vue (`/p/:slug`) mostrano le animazioni come i renderer.

## Dati

Tutto nei JSON esistenti, nessuna migration; il backend salva già `pages.styles` e `blocks.content` così come arrivano.

```jsonc
// pages.styles
"animations": {
  "enabled": false,
  "effect": "fade-up",      // fade | fade-up | fade-left | fade-right | zoom
  "duration": 600,          // 400 | 600 | 900
  "stagger": true
}

// blocks.content
"animation": {
  "effect": "inherit",      // inherit | none | fade | fade-up | fade-left | fade-right | zoom
  "delay": 0                // 0 | 100 | 200 | 300 | 500
}
```

Valori sconosciuti o assenti → predefiniti. I renderer accettano solo i valori in elenco (whitelist), mai stringhe libere negli attributi.

Import/export e duplicazione delle pagine portano già `styles` e `content`, quindi anche le animazioni.

## Come funziona

### Risoluzione dell'effetto (uguale in JS e PHP)

```
se animations.enabled è falso         → nessuna animazione
effect = block.content.animation.effect ?? 'inherit'
se effect == 'inherit':
    effect = tipo in [header, hero, hero-wide, hero-video] ? 'none' : animations.effect
se effect == 'none'                   → nessuna animazione
```

Una funzione per lato: `resolveBlockAnimation(page, block)` in `frontend/src/utils/blockAnimations.js` e `BlockRenderer::animationAttributes($block, $pageAnimations)` nei due renderer PHP.

### Markup

Il wrapper che esiste già attorno a ogni blocco riceve gli attributi:

```html
<div class="block-container" id="ancora"
     data-anim="fade-up" data-anim-delay="100" data-anim-duration="600" data-anim-stagger>
```

- standalone: `page.php`, ciclo dei blocchi (`<div class="block-container">`);
- Joomla: `default.php`, ciclo dei blocchi (`<div>` con ancora);
- Vue: `PagePreview.vue` e `PublicPage.vue`, `div` del `v-for` con l'ancora.

Per la sequenza, le card hanno `data-anim-item` e un indice `style="--anim-i: N"`:

- Vantaggi (`renderFeatures` / `FeaturesBlock.vue`): ogni card;
- Griglia servizi (`renderServicesgrid` / `ServicesGridBlock.vue`): ogni servizio.

Aggiungere `data-anim-item` ad altri blocchi in futuro non richiede altro codice.

### CSS (identico nei tre punti)

```css
/* Nasconde solo se lo script ha confermato che può animare */
html.elpb-anim [data-anim] {
  opacity: 0;
  transition-property: opacity, transform;
  transition-duration: var(--anim-duration, 600ms);
  transition-timing-function: cubic-bezier(.22,.61,.36,1);
  transition-delay: var(--anim-delay, 0ms);
}
html.elpb-anim [data-anim="fade-up"]    { transform: translateY(32px); }
html.elpb-anim [data-anim="fade-left"]  { transform: translateX(-40px); }
html.elpb-anim [data-anim="fade-right"] { transform: translateX(40px); }
html.elpb-anim [data-anim="zoom"]       { transform: scale(.94); }
html.elpb-anim [data-anim].anim-in      { opacity: 1; transform: none; }

/* Sequenza: le card partono dopo il blocco, una ogni 90 ms */
html.elpb-anim [data-anim-stagger] [data-anim-item] {
  opacity: 0; transform: translateY(16px);
  transition: opacity .5s, transform .5s;
  transition-delay: calc(var(--anim-delay, 0ms) + 150ms + var(--anim-i, 0) * 90ms);
}
html.elpb-anim [data-anim-stagger].anim-in [data-anim-item] { opacity: 1; transform: none; }

@media print { html.elpb-anim [data-anim], html.elpb-anim [data-anim-item] { opacity: 1 !important; transform: none !important; } }
```

Note:

- `transform: none` dopo la comparsa (non `translate(0)`), così il wrapper non resta un contenitore di riferimento per eventuali elementi `fixed`/`sticky` all'interno.
- Solo `opacity` e `transform`: niente layout shift, animazione sulla GPU.
- Niente `will-change` permanente (consuma memoria su pagine lunghe).

### JavaScript (identico nei tre punti, ~1 KB, nessuna libreria)

Due pezzi:

1. **In `<head>`, sincrono** (evita il lampo di contenuto visibile e poi nascosto): aggiunge `elpb-anim` a `<html>` solo se `IntersectionObserver` esiste e `prefers-reduced-motion` non è `reduce`.
2. **A fine pagina**: un solo `IntersectionObserver` (`threshold: 0`, `rootMargin: 0px 0px -12% 0px`: il blocco compare quando il suo bordo superiore supera di poco il fondo dello schermo, anche se è più alto dello schermo) su tutti i `[data-anim]`; all'ingresso aggiunge `anim-in` e smette di osservare l'elemento (animazione **una sola volta**). Imposta `--anim-delay` e `--anim-duration` dagli attributi.

Reti di sicurezza:

- se dopo 3 secondi dal `load` l'observer non è partito (errore JS altrove, script bloccato), rimuove `elpb-anim` e mostra tutto;
- link ad ancora (`#sezione`) e apertura della pagina già scrollata: i blocchi sopra la posizione corrente vengono mostrati subito senza animazione;
- `pageshow` da bfcache (il "torna indietro" della thank-you): i blocchi già comparsi restano visibili.

In Vue: composable `useBlockAnimations(containerRef)` che fa la stessa cosa con `onMounted`/`onBeforeUnmount` e riosserva quando cambiano i blocchi; CSS in `frontend/src/assets/block-animations.css` importato da PagePreview e PublicPage. Il pezzo 1 diventa la stessa verifica fatta prima di montare i blocchi.

## Casi da controllare

| Caso | Rischio | Soluzione |
|---|---|---|
| Slider (Swiper) dentro un blocco nascosto | Swiper calcola le misure con `opacity: 0`: funziona (il layout c'è); con `display:none` no, ma non lo usiamo | Verifica in Chromium |
| Mappa (iframe) e video | Nessuno, solo opacità | — |
| Hero video autoplay | Escluso di default | — |
| Form con reCAPTCHA | Il widget si carica anche se invisibile | Verifica invio lead |
| Blocco altissimo (più alto dello schermo) | Con una soglia in percentuale (es. 15% visibile) un blocco più alto dello schermo potrebbe non comparire mai | `threshold: 0` + `rootMargin` negativo |
| Thank-you page, pagine legali | Non devono animare | Gli attributi si aggiungono solo nel ciclo dei blocchi della landing |
| Bot e anteprima di Google | Vedono `opacity: 0` solo se eseguono JS e non scorrono | Le pagine hanno `noindex`; il contenuto è comunque nell'HTML |
| CSS personalizzato della pagina | Può usare `transform` sul wrapper | Il nostro CSS punta a `[data-anim]`, nessun conflitto con le classi esistenti |

## Renderer installati e versioni

- Standalone **2.2.0** e Joomla **2.2.0** (nuova funzione, compatibile all'indietro).
- I renderer vecchi ignorano `styles.animations` e `content.animation`: le pagine con animazioni attive si vedono **statiche**, senza errori.
- **Decisione da prendere:** aggiornare `TrackingService::CURRENT_RENDERER_VERSIONS` a 2.2.0. Se sì, tutti i siti standalone 2.1.1 risultano "da aggiornare" nella scheda Renderer; se no, l'aggiornamento resta facoltativo e invisibile. Proposta: aggiornare la costante, perché la scheda serve proprio a sapere quali siti non mostrano le funzioni nuove.
- Dopo la modifica: `scripts/build-renderer-packages.sh` (zip della guida), LEGGIMI con le novità della 2.2.0, file da caricare per aggiornare (`page.php`, `BlockRenderer.php`, `config.php`).
- Nell'editor, quando le animazioni sono attive, nota: "Visibili sui siti con renderer 2.2.0 o successivo". Se la pagina ha siti con renderer più vecchio (dati già disponibili da `GET /api/pages/{id}/renderers`), l'avviso li elenca.

## Fasi

1. **Utility condivise** — `blockAnimations.js` (risoluzione effetto, costanti, whitelist), CSS, composable Vue. Test a mano su PagePreview.
2. **Editor** — gruppo in PageSettings, gruppo in BlockEditor con "Prova", nota renderer.
3. **Card in sequenza** — `data-anim-item` in FeaturesBlock e ServicesGridBlock.
4. **Renderer standalone** — `animationAttributes()` in BlockRenderer, attributi in `renderFeatures`/`renderServicesgrid`, CSS e script in `page.php`, versione 2.2.0.
5. **Joomla** — stesse modifiche in `blockrenderer.php` e `default.php`, versione 2.2.0 in `landingpages.xml`.
6. **Versioni e pacchetti** — `CURRENT_RENDERER_VERSIONS` (+ test esistente `RendererBotsTest` da aggiornare), pacchetti, LEGGIMI, guida.
7. **Verifica in Chromium** — desktop e telefono, `prefers-reduced-motion` emulato, JS disattivato, link ad ancora, slider, form con invio lead, thank-you e ritorno indietro, pagine con animazioni spente identiche a prima.

Backend: solo la costante delle versioni (fase 6). Nessuna nuova rotta, quindi nulla in `AuditCatalog`.

## File coinvolti

- Nuovi: `frontend/src/utils/blockAnimations.js`, `frontend/src/composables/useBlockAnimations.js`, `frontend/src/assets/block-animations.css`, `frontend/src/components/AnimationEditor.vue`
- Frontend: `PageSettings.vue`, `BlockEditor.vue`, `PagePreview.vue`, `PublicPage.vue`, `PageEditor.vue` (solo "Prova"), `FeaturesBlock.vue`, `ServicesGridBlock.vue`
- Standalone: `page.php`, `BlockRenderer.php`, `config.php`, `renderer-package/` (LEGGIMI)
- Joomla: `blockrenderer.php`, `default.php`, `landingpages.xml`
- Backend: `TrackingService.php`, `RendererBotsTest.php`

## Domande aperte

1. Aggiornare `CURRENT_RENDERER_VERSIONS` alla 2.2.0 (siti segnati "da aggiornare")? Proposta: sì.
2. Animazioni attive di default sulle **nuove** pagine? Proposta: no, si accendono a mano.
3. Sequenza anche sui campi del Form? Proposta: no per ora (il form deve essere subito usabile).
