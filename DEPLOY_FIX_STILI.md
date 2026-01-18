# Deploy Fix Stili Blocchi - Landing Page Builder

## 📝 Descrizione Modifiche

**Data**: 2026-01-14
**Problema risolto**: Il renderer standalone non manteneva gli stili personalizzati dei blocchi (fontFamily, button styles)

### Modifiche Applicate

1. **Supporto fontFamily negli stili dei blocchi**
   - Aggiunto supporto per `$styles['fontFamily']` nel metodo `getBlockStyle()`
   - Il fontFamily del blocco viene applicato a tutti gli elementi del blocco
   - Nel Joomla: il fontFamily del blocco ha priorità sul fontFamily globale della pagina

2. **Supporto stili personalizzati button Hero**
   - Il blocco Hero ora supporta stili personalizzati per il button dal campo `content['buttonStyle']`
   - Stili supportati: backgroundColor, textColor, fontSize, padding, borderRadius, borderWidth, borderColor, borderStyle, shadow
   - Sincronizzazione completa tra componenti Vue e renderer PHP

### File Modificati

#### Renderer Standalone
- `standalone-renderer/BlockRenderer.php`
  - Metodo `getBlockStyle()` (righe 56-77): aggiunto supporto fontFamily
  - Metodo `renderHero()` (righe 687-761): aggiunti stili personalizzati button

#### Componente Joomla
- `joomla-component/com_landingpages/site/helpers/blockrenderer.php`
  - Metodo `getBlockStyle()` (righe 74-103): aggiunto supporto fontFamily con fallback a fontFamily globale
  - Metodo `renderHero()` (righe 719-778): aggiunti stili personalizzati button

---

## 🚀 Procedura di Deploy

### 1. Renderer Standalone (ilprodotto.it, primehome.it, infortunisticaveneta.it)

```bash
# 1. Crea archivio aggiornato
cd /Users/francesco/Code/landing-page-builder
zip -r standalone-renderer-fix-stili-$(date +%Y%m%d).zip standalone-renderer/

# 2. Carica su server via FTP/SFTP
# Carica standalone-renderer-fix-stili-YYYYMMDD.zip nella root del sito

# 3. Connettiti via SSH al server
ssh user@ilprodotto.it

# 4. Estrai e sovrascrivi
cd /home/ilprodotto.it/public_html
unzip -o standalone-renderer-fix-stili-YYYYMMDD.zip

# 5. IMPORTANTE: Correggi permessi file (OBBLIGATORIO per evitare errore 403)
cd standalone-renderer
chmod +x fix-permissions.sh
./fix-permissions.sh

# OPPURE manualmente:
# find . -type f -name "*.php" -exec chmod 644 {} \;
# find . -type d -exec chmod 755 {} \;

# Verifica permessi corretti:
# ls -la
# Output atteso: -rw-r--r-- (644) per file PHP

# 6. Test
# Visita: https://ilprodotto.it/landing-pages/[slug-test]
# Verifica che gli stili (fontFamily, colori button) siano corretti
# Se ottieni errore 403, vedi SOLUZIONE_RAPIDA_403.txt
```

**Ripeti per primehome.it e infortunisticaveneta.it**

---

### 2. Componente Joomla (edysma.net)

```bash
# 1. Aggiorna versione in landingpages.xml
cd /Users/francesco/Code/landing-page-builder/joomla-component/com_landingpages
# Modifica manualmente landingpages.xml: <version>2.0.3</version>
# Aggiungi in changelog: "Fix stili blocchi - supporto fontFamily e button styles personalizzati"

# 2. Crea pacchetto componente
cd /Users/francesco/Code/landing-page-builder
zip -r joomla-landingpages-v2.0.3-fix-stili.zip joomla-component/com_landingpages/

# 3. Upload e installazione via Joomla Admin
# - Login su https://edysma.net/administrator
# - Extensions > Manage > Install
# - Upload joomla-landingpages-v2.0.3-fix-stili.zip
# - L'aggiornamento sovrascriverà automaticamente i file modificati

# 4. Verifica installazione
# - Controlla che la versione sia 2.0.3 in Extensions > Manage > Manage
# - Test su landing page esistente per verificare stili
```

---

## ✅ Checklist Post-Deploy

- [ ] **Standalone Renderer**: Testato fontFamily personalizzato su blocco
- [ ] **Standalone Renderer**: Testato stili button personalizzati in Hero
- [ ] **Joomla**: Versione aggiornata a 2.0.3
- [ ] **Joomla**: Testato fontFamily blocco con override del globale
- [ ] **Joomla**: Testato stili button personalizzati in Hero
- [ ] **Tutti**: Nessun errore PHP nei log
- [ ] **Tutti**: Aspetto visivo identico tra editor Vue e render pubblico

---

## 🧪 Test Consigliati

### Test 1: FontFamily Personalizzato
1. Nell'editor, crea una pagina con blocco Hero
2. Nelle proprietà del blocco Hero, imposta "Font Family" (es. "Roboto")
3. Salva e pubblica
4. Verifica che il font sia applicato correttamente nel renderer pubblico

### Test 2: Button Styles Personalizzati
1. Nell'editor, seleziona blocco Hero
2. Modifica stili button (colore, dimensione, bordo, ombra)
3. Salva e pubblica
4. Verifica che tutti gli stili del button siano applicati nel renderer pubblico

### Test 3: Compatibilità Joomla
1. Verifica che il fontFamily globale della pagina funzioni ancora
2. Verifica che il fontFamily del blocco sovrascriva quello globale
3. Testa con landing page esistenti per regressioni

---

## 📋 Rollback

Se riscontri problemi:

### Rollback Standalone
```bash
# Ripristina versione precedente
cd /home/SITENAME/public_html
cp standalone-renderer/BlockRenderer.php standalone-renderer/BlockRenderer.php.new
# Ripristina da backup precedente
```

### Rollback Joomla
```bash
# Reinstalla versione precedente (2.0.2) via Joomla Admin
# Extensions > Manage > Install > Upload joomla-landingpages-v2.0.2.zip
```

---

## 📊 Impatto

- **Compatibilità**: ✅ Backwards compatible (nuovi campi opzionali)
- **Performance**: ✅ Nessun impatto (stessi calcoli, solo più completi)
- **Database**: ✅ Nessuna migrazione necessaria (solo rendering)

---

## 📞 Supporto

### Problemi Comuni

**🔴 ERRORE 403 FORBIDDEN**
- **Causa**: Permessi file non corretti dopo upload
- **Soluzione rapida**: Leggi `SOLUZIONE_RAPIDA_403.txt`
- **Documentazione completa**: Leggi `FIX_403_STANDALONE.md`
- **Script automatico**: Esegui `fix-permissions.sh` nella directory standalone-renderer

**🔴 Stili non applicati**
- Verifica che gli stili siano salvati nel database (campo `styles` JSON)
- Svuota cache del browser
- Controlla che il file BlockRenderer.php sia stato aggiornato correttamente

**🔴 Errori PHP**
- Verifica log PHP del server
- Controlla versione PHP (minima: 7.4)
- Verifica che tutte le estensioni necessarie siano installate

### File di Supporto

- `SOLUZIONE_RAPIDA_403.txt` - Soluzione immediata per errore 403
- `FIX_403_STANDALONE.md` - Guida completa troubleshooting errore 403
- `fix-permissions.sh` - Script automatico correzione permessi

### Ulteriori Problemi

Per altri problemi:
- Verifica log PHP del server
- Testa prima in ambiente di sviluppo
- Controlla che gli stili siano salvati correttamente nel database (campo `styles` JSON)
