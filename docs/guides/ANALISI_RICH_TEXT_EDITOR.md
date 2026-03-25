# Analisi Editor Rich Text per Vue 3
**Data**: Marzo 2026
**Contesto**: Valutazione migrazione da `@vueup/vue-quill` verso alternative più moderne

---

## Situazione attuale

Il progetto usa `@vueup/vue-quill` (wrapper Vue 3 di Quill.js) in `RichTextEditor.vue`.

### Problema critico
Il wrapper `@vueup/vue-quill` è **abbandonato dal maggio 2023** (v1.2.0, ultima release). Non ha mai aggiornato a Quill 2.0 (uscito aprile 2024). Ha migliaia di issue aperte senza risposta. È di fatto debito tecnico.

### Nota storica importante
Il CLAUDE.md documenta che a **gennaio 2026** il progetto aveva già Tiptap ed è stato deliberatamente migrato a VueQuill (`"Migrazione TipTap → VueQuill"`). Prima di rimigare a Tiptap, è utile capire il motivo di quella scelta — se c'era un problema specifico, potrebbe ripresentarsi.

---

## Editor analizzati

### 1. Tiptap v3
**GitHub**: github.com/ueberdosis/tiptap — **35.8k stelle**
**Ultima versione**: v3.20.3 (marzo 2026) — aggiornato settimanalmente
**Licenza**: MIT (core gratuito, feature avanzate a pagamento su Tiptap Cloud)

**Cos'è**: Editor headless costruito su ProseMirror. Non ha una UI predefinita: sei tu a costruire toolbar e controlli. Oltre 100 estensioni disponibili.

**Supporto Vue 3**: Nativo, first-class. Pacchetto `@tiptap/vue-3` con composable `useEditor()` e Composition API.

**Feature principali (tutte gratuite)**:
- Testo: bold, italic, underline, strike, code, colori, evidenziazione
- Struttura: H1–H6, liste ordinate/non ordinate, blockquote, separatori
- Allineamento: left, center, right, justify
- Tabelle con merge celle
- Link, immagini, video
- Menzioni (@), slash commands
- Collaborazione real-time (via Yjs, gratuito self-hosted)
- TypeScript nativo

**Feature a pagamento (Tiptap Cloud)**:
- AI writing assistant
- Commenti e annotazioni
- Versioning/cronologia modifiche
- Presenza utenti avanzata

**Bundle size**: ~45KB gzip (tree-shakable, paghi solo le estensioni che usi)

**Integrazione**:
```bash
npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit
```

**Pro**:
- Il più popolare del settore (4x più stelle di tutti gli altri)
- Aggiornamento quasi settimanale
- Output HTML — i renderer PHP esistenti non cambiano
- Bundle 6x più leggero di VueQuill
- Estremamente personalizzabile
- Ottima documentazione

**Contro**:
- Setup iniziale più verboso (devi costruire la tua toolbar)
- Per personalizzazioni profonde richiede conoscenza di ProseMirror
- Feature cloud interessanti ma a pagamento

---

### 2. @vueup/vue-quill (ATTUALE)
**GitHub**: github.com/vueup/vue-quill — **1.3k stelle**
**Ultima versione**: v1.2.0 (maggio 2023) — **non mantenuto**
**Licenza**: MIT

**Cos'è**: Wrapper Vue 3 di Quill.js, il celebre editor usato da Slack, LinkedIn, Figma.

**Problema**: Il wrapper non ha mai supportato Quill 2.0 (uscito aprile 2024). La libreria core Quill ha ~38k stelle ed è attiva, ma il wrapper Vue 3 è fermo.

**Feature attualmente usate nel progetto**:
- Bold, italic, strike
- H1, H2, H3
- Colori testo
- Allineamento (con workaround CSS)
- Blockquote, code block
- Liste

**Bundle size**: ~300KB minified

**Pro**:
- Già integrato e funzionante
- Configurazione semplice
- API intuitiva

**Contro**:
- Wrapper abbandonato (3 anni senza aggiornamenti)
- Bloccato su Quill 1.x
- Bundle pesante
- Personalizzazioni avanzate difficili
- Nessuna collaborazione real-time
- Potenziali problemi di compatibilità futura con Vue 3 aggiornato

---

### 3. Milkdown v7
**GitHub**: github.com/Milkdown/milkdown — **11.2k stelle**
**Ultima versione**: v7.19.0 (marzo 2026) — attivo
**Licenza**: MIT

**Cos'è**: Editor WYSIWYG orientato al Markdown, costruito su ProseMirror + Remark. Ispirato a Typora. Plugin-driven.

**Supporto Vue 3**: Nativo con `@milkdown/vue` e `useEditor()`.

**Feature principali**: Markdown parsing/rendering, tabelle, immagini, LaTeX, code blocks, slash commands (plugin Crepe).

**Bundle size**: ~40KB gzip

**Pro**:
- Ottimo se si vuole salvare il contenuto in Markdown
- Plugin system flessibile
- MIT license
- Bundle leggero

**Contro**:
- **Output Markdown, non HTML** → incompatibile con i renderer PHP attuali (richiederebbe riadattamento significativo)
- Curva di apprendimento del sistema di plugin
- Community più piccola di Tiptap

**Verdetto per questo progetto**: Non adatto senza riscrivere i renderer PHP.

---

### 4. CKEditor 5 v44+
**GitHub**: github.com/ckeditor/ckeditor5 — **9k stelle**
**Ultima versione**: v44+ (2026) — attivo
**Licenza**: ⚠️ GPL-2 / Commerciale

**Cos'è**: Editor WYSIWYG "classico" con toolbar visibile, molto simile a Word. Feature-complete out-of-the-box.

**Supporto Vue 3**: Nativo con `@ckeditor/ckeditor5-vue`.

**Feature principali**: Tutto quello di Tiptap, più export Word/PDF, collaborazione premium, revisioni con track changes.

**Bundle size**: ~400KB+ per setup completo

**Pro**:
- Editor più completo out-of-the-box
- Toolbar classica familiare agli utenti
- Nessun setup di estensioni

**Contro (CRITICO)**:
- Da v44.0.0 (fine 2024) la **`licenseKey` è obbligatoria** anche per uso free
- Senza licenza commerciale appare il badge "Powered by CKEditor"
- Piano gratuito cloud: limite 1.000 editor loads/mese
- Uso self-hosted GPL: il progetto deve essere open-source GPL
- Bundle pesantissimo
- Vendor lock-in progressivo

**Verdetto**: Licenza problematica per un progetto commerciale closed-source.

---

### 5. Editor.js
**GitHub**: ~28k stelle — attivo
**Licenza**: Apache-2

**Cos'è**: Editor block-based. Ogni blocco (paragrafo, header, immagine) è un plugin indipendente. Output JSON strutturato.

**Supporto Vue 3**: Solo wrapper community non ufficiali e non aggiornati.

**Contro**:
- Output JSON, non HTML → incompatibile con i renderer PHP
- Wrapper Vue 3 non ufficiali e non mantenuti

**Verdetto**: Non adatto per questo progetto.

---

### 6. BlockNote
**GitHub**: github.com/TypeCellOS/BlockNote — **9.3k stelle**
**Licenza**: MPL-2 / GPL-3

**Cos'è**: Editor block-based stile Notion con slash commands e drag-and-drop. Molto moderno.

**PROBLEMA**: **React-only**. Non esiste supporto Vue 3.

**Verdetto**: Non applicabile.

---

### 7. Slate.js
**GitHub**: ~30k stelle
**Licenza**: MIT

**Cos'è**: Framework ultra-flessibile per costruire editor custom. Usato da Discord, Grafana, Sanity.

**Supporto Vue 3**: Solo wrapper community non ufficiali.

**Verdetto**: React-first by design, Vue 3 non supportato ufficialmente.

---

## Tabella di confronto finale

| Criterio | VueQuill (attuale) | **Tiptap** | Milkdown | CKEditor 5 |
|---|:---:|:---:|:---:|:---:|
| Manutenzione | ❌ Abbandonato | ✅ Eccellente | ✅ Buona | ✅ Buona |
| Vue 3 nativo | ⚠️ Wrapper vecchio | ✅ First-class | ✅ Nativo | ✅ Nativo |
| GitHub stars | 1.3k | **35.8k** | 11.2k | 9k |
| Bundle size | ~300KB | **~45KB** | ~40KB | ~400KB |
| Output compatibile PHP | ✅ HTML | ✅ HTML | ❌ Markdown | ✅ HTML |
| Tabelle | ❌ | ✅ | ✅ | ✅ |
| Colori / allineamento | ⚠️ Workaround | ✅ | ✅ | ✅ |
| Headings H1-H3 | ✅ | ✅ | ✅ | ✅ |
| Personalizzazione UI | Limitata | ✅ Headless | Media | Media |
| Collaborazione real-time | ❌ | ✅ Yjs gratuito | Limitata | 💰 Paywall |
| Licenza | MIT | **MIT** | MIT | ⚠️ GPL/Comm. |
| Costo | Gratuito | **Gratuito** (core) | Gratuito | 💰 Logo |

---

## Scenari di decisione

### Scenario A — Rimani su VueQuill
**Quando ha senso**: Il progetto è stabile, le feature attuali bastano, e non vuoi spendere tempo in refactoring.
**Rischio**: Il wrapper potrebbe smettere di funzionare con versioni future di Vue 3. Nessuna nuova feature disponibile.
**Costo**: Zero.

### Scenario B — Migri a Tiptap
**Quando ha senso**: Vuoi feature come tabelle, personalizzazioni avanzate, o vuoi eliminare il debito tecnico.
**Lavoro stimato**: Riscrittura di `RichTextEditor.vue` (componente isolato) + aggiornamento CSS dei renderer PHP per la formattazione del testo. Le sessioni blocco non cambiano.
**Costo**: Solo tempo di sviluppo. Licenza MIT, nessuna spesa.
**Nota**: Verificare perché a gennaio 2026 si era scelto di abbandonare Tiptap per VueQuill.

### Scenario C — Aspetti Quill 2.0 con wrapper aggiornato
**Quando ha senso**: Se la community di `vue-quill` si risveglia e aggiorna a Quill 2.0.
**Rischio**: Nessun segnale in questo senso. Non raccomandato come strategia attiva.

---

## Conclusione

Se si decide di migrare, **Tiptap** è la scelta più sicura:
- Unico editor con supporto Vue 3 nativo di prima classe, MIT, output HTML, bundle leggero, e sviluppo attivo
- La migrazione riguarda un solo componente (`RichTextEditor.vue`) e non tocca la struttura del progetto
- I renderer PHP non richiedono modifiche (entrambi producono HTML)

La domanda chiave rimane: **perché si era abbandonato Tiptap a gennaio 2026?** Se c'era un bug specifico o una limitazione che dava problemi, va valutato se è stato risolto nella v3.
