# Legal Footer Block - Documentazione Implementazione

## Riepilogo
Nuovo blocco "Footer Legale" (legal-footer) per landing pages con link legali e informazioni aziendali, seguendo la struttura HTML fornita dall'utente.

## Caratteristiche
- **Layout Compatto**: Design su 3 righe (link, indirizzo/contatti, info legali)
- **Link Legali**: Lista personalizzabile inline con supporto per classe `cc-show` (preferenze cookies)
- **Testo Legale**: Campo rich text HTML compatto per informazioni aziendali, copyright e credits
- **Stili**: Supporto completo per backgroundColor, textColor, linkColor, fontFamily, padding
- **fullWidth**: Toggle per larghezza completa (default true)
- **Posizione Fissa**: Sempre all'ultimo posto nella pagina
- **⚠️ ESCLUSIONE RECIPROCA**: Footer e Legal-Footer si escludono a vicenda - non possono coesistere nella stessa pagina

## Files Modificati

### Frontend Vue

1. **frontend/src/components/blocks/LegalFooterBlock.vue** (NUOVO)
   - Componente Vue per rendering blocco
   - Default content con 4 link legali (Privacy, Condizioni d'uso, Cookies, Preferenze cookies)
   - Default text da ARAN CUCINE PALERMO
   - Supporto fullWidth e stili personalizzati

2. **frontend/src/components/BlockEditor.vue**
   - Aggiunta sezione editor per legal-footer (righe ~1783-1873)
   - Funzioni `addLegalLink()` e `removeLegalLink()`
   - Mapping nome blocco: `'legal-footer': 'Footer Legale'`

3. **frontend/src/views/PageEditor.vue**
   - Import `LegalFooterBlock`
   - Aggiunto a `blockTypes` array
   - Aggiunto default content in `getDefaultContent()`
   - Aggiunto mapping in `getBlockComponent()`
   - Logica trascinamento: legal-footer non trascinabile
   - Logica posizione: sempre ultimo (footer prima se presente)

4. **frontend/src/views/PagePreview.vue**
   - Import `LegalFooterBlock`
   - Aggiunto mapping in `getBlockComponent()`

5. **frontend/src/views/PublicPage.vue**
   - Import `LegalFooterBlock`
   - Aggiunto mapping in `getBlockComponent()`

### Renderer PHP

6. **standalone-renderer/BlockRenderer.php**
   - Metodo `renderLegalfooter()` (righe ~1989-2094)
   - Logica fullWidth identica a footer normale
   - Link legali con classe `cc-show` condizionale
   - Stile hover inline per link

7. **joomla-component/com_landingpages/site/helpers/blockrenderer.php**
   - Metodo `renderLegalfooter()` (identico a standalone)
   - Supporto completo per tutti gli stili

### Cookie Consent CSS (task precedente)

8. **standalone-renderer/page.php**
   - Aggiunto `<link href="/cookieconsent/cookieconsent.css" rel="stylesheet" />`

9. **joomla-component/com_landingpages/site/views/page/tmpl/default.php**
   - Aggiunto `<link href="/cookieconsent/cookieconsent.css" rel="stylesheet" />`

10. **frontend/src/views/PagePreview.vue**
    - Caricamento dinamico cookieconsent CSS

11. **frontend/src/views/PublicPage.vue**
    - Caricamento dinamico cookieconsent CSS

## Struttura Dati

### Content
```javascript
{
  fullWidth: true,
  legalLinks: [
    {
      text: 'Privacy',
      url: '/privacy.php',
      isCookiePreference: false
    },
    {
      text: 'Preferenze cookies',
      url: '#',
      isCookiePreference: true  // Aggiunge classe cc-show
    }
  ],
  legalText: 'HTML string con informazioni legali'
}
```

### Styles
```javascript
{
  backgroundColor: '#F3F4F6',  // gray-100 default
  textColor: '#6B7280',        // gray-500 default
  linkColor: '#6B7280',        // Colore link (fallback a textColor)
  padding: '2rem',
  fontFamily: 'Inter'
}
```

## HTML Output Esempio (Layout Compatto 3 Righe)
```html
<footer class="footer" style="background-color: #F3F4F6; color: #6B7280;">
  <div class="px-6 py-4 max-w-7xl mx-auto">
    <div class="text-center">
      <!-- Riga 1: Link legali -->
      <ul class="list-none mb-2" style="display: flex; gap: 0.25rem 1.5rem; ...">
        <li><a href="/privacy.php">Privacy</a></li>
        <li><a href="#" class="cc-show">Preferenze cookies</a></li>
      </ul>
      <!-- Riga 2-3: Testo legale compatto -->
      <div class="text-xs" style="line-height: 1.375;">
        ARAN CUCINE PALERMO - VIALE LAZIO...<br>
        è un'iniziativa INTERLINEA SRL... - Tutti i diritti riservati...
      </div>
    </div>
  </div>
</footer>
```

## Ordine Blocchi Pagina
1. **header** (sempre primo)
2. ... altri blocchi (trascinabili)
3. **footer** OPPURE **legal-footer** (sempre ultimo - SI ESCLUDONO A VICENDA)

## Note Implementazione
- **Layout Compatto**: Design ottimizzato per ridurre l'altezza (py-4 invece di py-8, mb-2 invece di mb-4)
- **Tipografia**: text-xs e line-height 1.375 per massima compattezza
- **3 Righe**: Link legali (riga 1) + Indirizzo/contatti (riga 2) + Info legali complete (riga 3)
- Link con `isCookiePreference: true` ottengono classe `cc-show` per integrazione CookieConsent
- Legal-footer NON trascinabile (come header e footer)
- Supporta tutti gli stili del footer normale (fullWidth, colori, padding, font)
- Rich text editor per legalText consente HTML con link
- Default content personalizzabile nell'editor
- **⚠️ IMPORTANTE**: Footer e Legal-Footer si escludono a vicenda:
  - Se tenti di aggiungere un Footer quando è presente un Legal-Footer, appare un alert: "Non puoi aggiungere un Footer standard se è già presente un Footer Legale. Elimina prima il Footer Legale per aggiungere un Footer standard."
  - Se tenti di aggiungere un Legal-Footer quando è presente un Footer, appare un alert: "Non puoi aggiungere un Footer Legale se è già presente un Footer standard. Elimina prima il Footer standard per aggiungere un Footer Legale."

## Testing
- ✅ Rendering Vue (PageEditor, PagePreview, PublicPage)
- ✅ Rendering PHP standalone
- ✅ Rendering PHP Joomla
- ✅ Posizionamento automatico (sempre ultimo)
- ✅ fullWidth toggle
- ✅ Personalizzazione stili
- ✅ Gestione link legali (add/remove)
- ✅ Classe cc-show per preferenze cookies

## Prossimi Passi (Opzionali)
- [ ] Aggiungere stili predefiniti per link (underline, hover color)
- [ ] Supportare icone nei link legali
- [ ] Aggiungere template predefiniti (GDPR, eCommerce, etc.)
- [ ] Supportare multilinguaggio per link default
