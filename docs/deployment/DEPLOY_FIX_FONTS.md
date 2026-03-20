# Deploy Fix Google Fonts - Landing Page Builder

## 📝 Descrizione Modifiche

**Data**: 2026-01-14
**Problema risolto**: I font selezionati per i singoli blocchi non venivano caricati nei renderer standalone e Joomla

### Causa del Problema

- **Frontend Vue**: Carica dinamicamente ogni font specificato in `block.styles.fontFamily` via JavaScript
- **Renderer PHP**: Caricava SOLO il font globale della pagina (`page.styles.fontFamily`)
- **Risultato**: Se un blocco aveva un font diverso dal globale, il font non veniva caricato e si usava il fallback

### Soluzione Implementata

I renderer standalone e Joomla ora:
1. Raccolgono tutti i `fontFamily` unici da TUTTI i blocchi della pagina
2. Caricano tutti i font unici nel `<head>` della pagina HTML
3. Ogni blocco può ora usare il proprio font specificato in `block.styles.fontFamily`

### File Modificati

#### Renderer Standalone
- **`standalone-renderer/page.php`** (righe 146-164, 188-193)
  - Raccolta font unici da tutti i blocchi
  - Caricamento multiplo Google Fonts nel HEAD

#### Componente Joomla
- **`joomla-component/com_landingpages/site/views/page/tmpl/default.php`** (righe 24-43, 65-97)
  - Raccolta font unici da tutti i blocchi
  - Caricamento multiplo Google Fonts in modalità standalone e template
- **`joomla-component/com_landingpages/landingpages.xml`**
  - Versione aggiornata a 2.0.4

---

## 🚀 Procedura di Deploy

### 1. Renderer Standalone (ilprodotto.it, primehome.it, infortunisticaveneta.it)

```bash
# 1. Carica archivio su server via FTP/SFTP
# Carica standalone-renderer-fix-fonts-20260114.zip nella root del sito

# 2. Connettiti via SSH al server
ssh user@ilprodotto.it

# 3. Backup file esistente (raccomandato)
cd /home/ilprodotto.it/public_html
cp standalone-renderer/page.php standalone-renderer/page.php.backup-20260114

# 4. Estrai e sovrascrivi
unzip -o standalone-renderer-fix-fonts-20260114.zip

# 5. IMPORTANTE: Correggi permessi file (OBBLIGATORIO per evitare errore 403)
cd standalone-renderer
chmod +x fix-permissions.sh
./fix-permissions.sh

# 6. Test
# Visita una landing page con blocchi che usano font diversi
# Verifica nel browser (DevTools > Network) che tutti i font vengono caricati
# Verifica visivamente che i font siano applicati correttamente
```

**Ripeti per primehome.it e infortunisticaveneta.it**

---

### 2. Componente Joomla (edysma.net)

```bash
# 1. Upload e installazione via Joomla Admin
# - Login su https://edysma.net/administrator
# - Extensions > Manage > Install
# - Upload joomla-landingpages-v2.0.4-fix-fonts.zip
# - Conferma aggiornamento (sovrascrive file esistenti)

# 2. Verifica installazione
# - Controlla che la versione sia 2.0.4 in Extensions > Manage > Manage
# - Filtra per "Landing Pages"
# - Verifica versione: 2.0.4

# 3. Test
# - Visita una landing page esistente con blocchi che usano font diversi
# - Verifica nel browser che i font siano caricati e applicati correttamente
# - Prova sia in modalità standalone che in modalità template
```

---

## ✅ Checklist Post-Deploy

- [ ] **Standalone**: Font globale pagina caricato correttamente
- [ ] **Standalone**: Font specifici blocchi caricati nel HEAD
- [ ] **Standalone**: Tutti i font visibili correttamente nelle landing pages
- [ ] **Joomla**: Versione aggiornata a 2.0.4
- [ ] **Joomla**: Font caricati in modalità standalone
- [ ] **Joomla**: Font caricati in modalità template
- [ ] **Joomla**: Font applicati correttamente in entrambe le modalità
- [ ] **Tutti**: Nessun errore JavaScript/CSS in console browser
- [ ] **Tutti**: Nessun errore PHP nei log del server

---

## 🧪 Come Testare

### Test 1: Verifica Caricamento Font nel HEAD

1. Apri una landing page nel browser
2. Apri DevTools (F12) → Network
3. Filtra per "fonts.googleapis.com"
4. Ricarica la pagina (Ctrl+R)
5. Verifica che vengano caricati TUTTI i font usati nei blocchi, non solo il globale

**Risultato atteso:**
```html
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

### Test 2: Verifica Applicazione Font

1. Apri una landing page con blocchi che usano font diversi
2. Apri DevTools → Elements
3. Seleziona un elemento di testo dentro un blocco
4. Verifica nel pannello "Computed" che `font-family` sia quello specificato

**Risultato atteso:**
- Se il blocco Hero ha `fontFamily: "Montserrat"` → `font-family: "Montserrat", sans-serif`
- Se il blocco Text ha `fontFamily: "Roboto"` → `font-family: "Roboto", sans-serif`

### Test 3: Confronto Vue vs PHP

1. Apri una landing page nell'editor Vue (anteprima)
2. Fai screenshot dei blocchi con font personalizzati
3. Pubblica la pagina e aprila nel renderer standalone/Joomla
4. Confronta visivamente: i font devono essere identici

---

## 🔄 Rollback

Se riscontri problemi:

### Rollback Standalone
```bash
cd /home/SITENAME/public_html/standalone-renderer
cp page.php.backup-20260114 page.php
chmod 644 page.php
```

### Rollback Joomla
```bash
# Reinstalla versione precedente (2.0.3) via Joomla Admin
# Extensions > Manage > Install > Upload joomla-landingpages-v2.0.3-fix-stili.zip
```

---

## 📊 Impatto

- **Compatibilità**: ✅ Backwards compatible (se un blocco non ha fontFamily, usa quello globale o default)
- **Performance**: ⚠️ Carica più font → più richieste HTTP
  - Mitigazione: I font sono caricati in parallelo e cachati dal browser
  - Impatto: ~100-200ms per font aggiuntivo solo al primo caricamento
- **Database**: ✅ Nessuna migrazione necessaria

---

## 💡 Ottimizzazione Font Loading (Opzionale)

Per migliorare le performance, considera:

### Opzione 1: Combinare richieste Google Fonts

Invece di:
```html
<link href="...?family=Roboto:wght@...">
<link href="...?family=Montserrat:wght@...">
```

Usa (modifica manuale dopo deploy):
```html
<link href="...?family=Roboto:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap">
```

### Opzione 2: Preconnect ai server Google Fonts

Aggiunto automaticamente in Joomla, ma non in standalone. Per standalone, aggiungi manualmente:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

---

## 📞 Supporto

### Problemi Comuni

**🔴 ERRORE 403 FORBIDDEN**
- Vedi `SOLUZIONE_RAPIDA_403.txt` o `FIX_403_STANDALONE.md`

**🔴 Font non caricati**
- Verifica DevTools > Network che i font siano richiesti
- Controlla errori console per blocco CORS o CSP
- Verifica connessione internet del server

**🔴 Font caricati ma non applicati**
- Verifica che `block.styles.fontFamily` sia salvato nel database
- Controlla DevTools > Elements > Computed > font-family
- Verifica che non ci siano override CSS che sovrascrivono il font

---

## 📚 File di Riferimento

- `standalone-renderer-fix-fonts-20260114.zip` - Renderer aggiornato
- `joomla-landingpages-v2.0.4-fix-fonts.zip` - Componente Joomla v2.0.4
- `FIX_403_STANDALONE.md` - Guida risoluzione errore 403
- `SOLUZIONE_RAPIDA_403.txt` - Fix rapido permessi file

---

## 🔗 Changelog Completo

**v2.0.4** (2026-01-14)
- ✅ Fix caricamento Google Fonts per blocchi individuali
- ✅ Raccolta automatica font unici da tutti i blocchi
- ✅ Caricamento multiplo font nel HEAD (standalone e Joomla)
- ✅ Supporto font diversi per ogni blocco

**v2.0.3** (2026-01-14)
- ✅ Supporto fontFamily negli stili blocco (getBlockStyle)
- ✅ Stili personalizzati button Hero

**v2.0.2** (2026-01-10)
- ✅ Supporto completo block styles (padding, backgroundColor, textColor)
