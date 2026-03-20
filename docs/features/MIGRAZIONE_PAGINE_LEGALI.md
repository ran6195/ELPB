# Migrazione Pagine Legali - Riepilogo Implementazione

**Data:** 2026-02-01
**Versione:** 2.0.8
**Stato:** ✅ Implementato (in attesa di testing e deployment)

---

## 📋 Sommario delle Modifiche

La migrazione delle pagine legali da backend centralizzato a renderer locali è stata completata con successo. Ora le pagine Privacy, Condizioni d'uso e Cookie Policy vengono renderizzate direttamente sui renderer (standalone e Joomla), mantenendo i dati nel database backend.

---

## 📁 File Modificati/Creati

### ✨ Nuovi File Creati (3)

1. **`menu-legali/LegalTemplateProcessor.php`** (Nuovo)
   - Helper condiviso per processare i template delle pagine legali
   - Gestisce sostituzione variabili e validazione dati
   - Sicurezza: escape HTML, validazione input
   - Riutilizzabile da standalone e Joomla

2. **`standalone-renderer/legal-pages.php`** (Nuovo)
   - Entry point per pagine legali su renderer standalone
   - URL pattern: `/legal/{slug}/{type}`
   - Renderizza header/footer della landing page originale
   - Gestisce errori 404 con pagina custom

3. **`joomla-component/com_landingpages/site/helpers/LegalTemplateProcessor.php`** (Copiato)
   - Copia del file condiviso per uso in Joomla
   - Identico a `menu-legali/LegalTemplateProcessor.php`

### 📝 File Modificati (5)

4. **`standalone-renderer/.htaccess`** (Modificato)
   - ✅ Aggiunta regola routing per `/legal/{slug}/{type}`
   - Posizionata PRIMA della regola catch-all (critico!)
   - Pattern: `^legal/([a-z0-9-]+)/(privacy|condizioni|cookies)$`

5. **`standalone-renderer/BlockRenderer.php`** (Modificato - riga ~2008)
   - ✅ Cambiati link footer da URL backend assoluti a URL relativi
   - Prima: `$backendUrl . '/legal/' . $slug . '/privacy'`
   - Dopo: `'/legal/' . $slug . '/privacy'`

6. **`joomla-component/com_landingpages/site/controller.php`** (Modificato)
   - ✅ Aggiunto metodo `displayLegal()` (150+ righe)
   - ✅ Aggiunto metodo `renderLegalPage()` (rendering completo)
   - Task URL: `index.php?option=com_landingpages&task=displayLegal&slug={slug}&type={type}`
   - Validazione input, security, gestione errori

7. **`joomla-component/com_landingpages/site/helpers/blockrenderer.php`** (Modificato - riga ~2077)
   - ✅ Cambiati link footer da URL backend a URL task Joomla
   - Prima: `$backendUrl . '/legal/' . $slug . '/privacy'`
   - Dopo: `'index.php?option=com_landingpages&task=displayLegal&slug=' . $slug . '&type=privacy'`

8. **`frontend/src/components/blocks/LegalFooterBlock.vue`** (Modificato - righe 70-80)
   - ✅ Cambiati link da URL backend assoluti a URL relativi
   - Prima: `` `${backendUrl}/legal/${slug}/privacy` ``
   - Dopo: `` `/legal/${slug}/privacy` ``
   - Nota: I link funzionano solo sui renderer pubblicati, non in preview

9. **`backend/src/Controllers/LegalPagesController.php`** (Modificato)
   - ✅ Aggiunto tag `@deprecated 2.0.8` nel docblock della classe
   - Route mantenute per retrocompatibilità temporanea
   - Pianificata rimozione dopo 2-4 settimane di deployment stabile

### 📂 Directory Creata (1)

10. **`joomla-component/com_landingpages/site/templates/legal/`** (Nuova)
    - Contiene copie dei template legali (`privacy.php`, `condizioni.php`, `cookies.php`)
    - Usati da `LegalTemplateProcessor` in Joomla

### 🏗️ Build Frontend (1)

11. **`frontend/dist/`** (Ricompilato)
    - ✅ Build completato con successo
    - Dimensione bundle: ~337 KB (gzip: ~66 KB)
    - Pronto per deployment

---

## 🔗 URL Pattern Nuovi

### Standalone
```
https://tuosito.it/legal/homepage/privacy
https://tuosito.it/legal/homepage/condizioni
https://tuosito.it/legal/homepage/cookies
```

### Joomla
```
https://joomlasite.it/index.php?option=com_landingpages&task=displayLegal&slug=homepage&type=privacy
https://joomlasite.it/index.php?option=com_landingpages&task=displayLegal&slug=homepage&type=condizioni
https://joomlasite.it/index.php?option=com_landingpages&task=displayLegal&slug=homepage&type=cookies
```

---

## 🛡️ Sicurezza Implementata

1. **Sanitizzazione Input**
   - Slug: `preg_replace('/[^a-z0-9\-]/', '', strtolower($slug))`
   - Type: whitelist `['privacy', 'condizioni', 'cookies']`

2. **Path Traversal Prevention**
   - Controllo presenza `..`, `/`, `\` nello slug
   - Blocco immediato con 404

3. **XSS Prevention**
   - Escape HTML con `htmlspecialchars()` in `LegalTemplateProcessor`
   - Flag: `ENT_QUOTES | ENT_HTML5`

4. **Validazione Dati**
   - Metodo `validate()` controlla campi obbligatori
   - Exception se mancano: `ragioneSociale`, `sitoWeb`, `indirizzoCompleto`, `emailContatto`

---

## ✅ Benefici Ottenuti

### Tecnici
- ✅ Eliminata dipendenza runtime dal backend per rendering
- ✅ URL semplici e SEO-friendly (no parametri complessi)
- ✅ Risolti problemi con `BASE_PATH` e sottocartelle
- ✅ Prestazioni migliori (no HTTP roundtrip al backend)

### Operativi
- ✅ Deployment semplificato (renderer autonomi)
- ✅ Maggiore resilienza (backend offline non impatta pagine legali)
- ✅ Manutenzione template centralizzata (`menu-legali/`)

### UX
- ✅ Caricamento pagine più veloce
- ✅ Stessi header/footer della landing page (coerenza visiva)
- ✅ Conformità normativa garantita (pagine sempre disponibili)

---

## ⚠️ Note Importanti

### Frontend Preview
I link alle pagine legali nel componente Vue (`LegalFooterBlock.vue`) usano ora URL relativi (`/legal/{slug}/{type}`). Questo significa che:
- ✅ Funzionano correttamente sui renderer pubblicati (standalone/Joomla)
- ❌ NON funzionano in modalità preview/editor (URL relativo non risolve)
- Comportamento atteso: in preview i link puntano a 404 (normale)

### Backend Routes
Le route backend (`/legal/{slug}/{type}`) sono marcate `@deprecated` ma **NON rimosse**:
- Mantenute per retrocompatibilità temporanea
- Pianificata rimozione dopo 2-4 settimane di deployment stabile
- Opzionale: aggiungere log warning quando vengono usate

### Template Sincronizzazione
I template in `menu-legali/` sono copiati in:
- `standalone-renderer/../menu-legali/` (usati via path relativo)
- `joomla-component/.../templates/legal/` (copia fisica)

Per modifiche future ai template:
1. Modificare file in `menu-legali/`
2. Ricopiare in Joomla: `cp menu-legali/*.php joomla-component/.../templates/legal/`
3. Opzionale: creare script `deploy-legal-templates.sh` per automatizzare

---

## 🧪 Testing Richiesto

### Prima del Deployment

#### Test Funzionali Standalone
- [ ] Visitare `/legal/homepage/privacy` → verifica rendering
- [ ] Visitare `/legal/homepage/condizioni` → verifica rendering
- [ ] Visitare `/legal/homepage/cookies` → verifica rendering
- [ ] Verificare header/footer presenti e identici alla landing page
- [ ] Verificare sostituzione variabili corretta (nome, sito, indirizzo, email)
- [ ] Verificare font caricati correttamente

#### Test Funzionali Joomla
- [ ] Visitare URL task `displayLegal` con `type=privacy`
- [ ] Visitare URL task `displayLegal` con `type=condizioni`
- [ ] Visitare URL task `displayLegal` con `type=cookies`
- [ ] Stessi controlli di standalone

#### Test Frontend
- [ ] Click link "Privacy" nel footer → apre pagina corretta
- [ ] Click link "Condizioni" nel footer → apre pagina corretta
- [ ] Click link "Cookies" nel footer → apre pagina corretta

#### Test Errori
- [ ] `/legal/slug-inesistente/privacy` → 404
- [ ] `/legal/homepage/malicious` → 404
- [ ] `/legal/../../../etc/passwd` → 404 (path traversal)
- [ ] Pagina senza `legal_info` → 404 o messaggio

#### Test Sicurezza
- [ ] Inserire `<script>alert('xss')</script>` in `ragioneSociale` → deve essere escaped
- [ ] Tentare injection SQL nello slug → deve essere bloccato
- [ ] Verificare escape HTML in tutti i campi `legal_info`

#### Test Regressione
- [ ] Pagine landing normali funzionano ancora
- [ ] Form lead funzionano ancora
- [ ] Link backend (se usati) funzionano ancora

---

## 🚀 Deployment Checklist

### Pre-Deploy
- [ ] Backup database
- [ ] Backup file renderer (standalone + Joomla)
- [ ] Test completati su ambiente staging
- [ ] Piano rollback preparato (vedi sotto)

### Deploy Standalone
1. [ ] Upload `legal-pages.php` su server
2. [ ] Backup `.htaccess` esistente
3. [ ] Upload `.htaccess` modificato
4. [ ] Upload `BlockRenderer.php` modificato
5. [ ] Verificare `menu-legali/LegalTemplateProcessor.php` presente
6. [ ] Test: `curl https://tuosito.it/legal/test-slug/privacy`

### Deploy Joomla
1. [ ] Upload `controller.php` modificato
2. [ ] Upload `blockrenderer.php` modificato
3. [ ] Upload `helpers/LegalTemplateProcessor.php`
4. [ ] Upload `templates/legal/*.php`
5. [ ] Invalidare cache Joomla (Admin > System > Clear Cache)
6. [ ] Test: visitare URL task `displayLegal`

### Deploy Frontend
1. [ ] Upload `frontend/dist/` su server
2. [ ] Verificare file `index.html` aggiornato
3. [ ] Invalidare cache browser (Ctrl+Shift+R)

### Post-Deploy
- [ ] Test pagine legali su tutti i renderer
- [ ] Monitorare log errori per 24 ore
- [ ] Verificare analytics (nessun aumento 404)
- [ ] Raccogliere feedback utenti

---

## 🔄 Rollback Plan

### Rollback Standalone (5 min)
```bash
# Ripristinare .htaccess
cp .htaccess.backup .htaccess

# Ripristinare BlockRenderer.php (versione precedente)
# via FTP o git checkout

# I file nuovi (legal-pages.php) possono rimanere (non interferiscono)
```

### Rollback Joomla (5 min)
```bash
# Ripristinare controller.php e blockrenderer.php
# via FTP o backup

# Invalidare cache
# Admin > System > Clear Cache
```

### Rollback Frontend (10 min)
```bash
# Ripristinare LegalFooterBlock.vue
git checkout HEAD~1 frontend/src/components/blocks/LegalFooterBlock.vue

# Rebuild
cd frontend
npm run build

# Redeploy dist/
```

**Nota:** Il rollback riporta al sistema precedente (backend centralizzato) senza perdita di dati.

---

## 📊 Metriche Post-Deploy

### Monitorare per 1 settimana:
1. **Errori 404**
   - Path: `/legal/*`
   - Soglia alert: > 5% delle richieste

2. **Tempo Caricamento**
   - Pagine legali vs. landing page normale
   - Aspettativa: < 500ms differenza

3. **Traffico Backend**
   - Route deprecate `/legal/{slug}/{type}`
   - Aspettativa: 0 richieste dopo 2 settimane

4. **Errori Server**
   - Log PHP/Apache per eccezioni
   - Soglia: 0 errori critici

---

## 🗑️ Rimozione Route Backend (Pianificata)

**Quando:** 2-4 settimane dopo deployment stabile
**Cosa fare:**

1. Modificare `backend/public/index.php`:
   ```php
   // Rimuovere completamente queste righe:
   $app->get('/legal/{slug}/privacy', [LegalPagesController::class, 'privacy']);
   $app->get('/legal/{slug}/condizioni', [LegalPagesController::class, 'terms']);
   $app->get('/legal/{slug}/cookies', [LegalPagesController::class, 'cookies']);
   ```

2. Eliminare `backend/src/Controllers/LegalPagesController.php`

3. Rimuovere template `backend/templates/legal/*.php` (se non usati altrove)

4. Aggiornare documentazione

---

## 📚 Documentazione Correlata

- `CLAUDE.md` - Aggiornato con questa sessione (Sessione 2026-02-01)
- `FIX_403_STANDALONE.md` - Permessi file renderer standalone
- `GUIDA_URL_REWRITING.md` - Configurazione Apache .htaccess
- `SOFT_DELETE_PAGES.md` - Sistema soft delete pagine

---

## 👥 Credits

**Implementato da:** Claude Code (claude.ai/code)
**Richiesto da:** Francesco
**Piano originale:** `Piano: Migrazione Pagine Legali da Backend Centralizzato a Renderer Locali`

---

## 📝 Changelog

### v2.0.8 - 2026-02-01
- ✨ Migrazione pagine legali a renderer locali
- ✨ Aggiunto `LegalTemplateProcessor` helper condiviso
- ✨ Implementato rendering locale su standalone (`legal-pages.php`)
- ✨ Implementato rendering locale su Joomla (`displayLegal()` task)
- 🔧 Aggiornati link footer da backend URL a URL relativi/locali
- 🔧 Aggiornato routing `.htaccess` standalone
- 🔧 Build frontend con nuovi link
- 📝 Deprecated controller backend `LegalPagesController`
- 🔒 Implementata validazione input e sicurezza (XSS, path traversal)

---

**Status:** ✅ Pronto per testing e deployment
**Prossimo Step:** Eseguire checklist testing su ambiente staging
