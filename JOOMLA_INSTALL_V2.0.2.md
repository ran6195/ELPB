# 📦 Installazione/Aggiornamento Componente Joomla v2.0.2

**Versione:** 2.0.2
**Data:** 10 Gennaio 2026
**Tipo Update:** Aggiornamento BlockRenderer - Supporto Completo Stili

---

## 🎯 Cosa Include Questo Aggiornamento

✅ **Font Family** - Applicato automaticamente a TUTTI i blocchi
✅ **Background Color** - Supporto completo per ogni blocco
✅ **Text Color** - Supporto completo per ogni blocco
✅ **Padding** - Supporto completo per ogni blocco
✅ **Rounded Corners** - Supporto completo per ogni blocco

**Blocchi Corretti:**
- Hero (ora usa stili consistenti)
- VideoInfo (rimossa duplicazione stili)
- Slider (font family automatico)
- ImageSlide (aggiunto supporto stili)

---

## 📦 Opzioni di Installazione

Hai **2 opzioni** per aggiornare il componente Joomla:

### Opzione A: Aggiornamento Completo (Raccomandato) 🔴

**File:** `joomla-landingpages-v2.0.2-full-styles.tar.gz` (11 MB)

Contiene:
- Componente Joomla completo
- Tutte le funzionalità più recenti
- API backend integrata
- Fix e miglioramenti

**Quando usare:**
- Prima installazione del componente
- Vuoi essere sicuro di avere tutte le ultime funzionalità
- Hai problemi con il componente attuale

---

### Opzione B: Aggiornamento Solo BlockRenderer (Veloce) 🟢

**File:** `joomla-blockrenderer-v2.0.2-update.tar.gz` (15 KB)

Contiene:
- Solo `blockrenderer.php` modificato
- File XML con versione aggiornata

**Quando usare:**
- Hai già la versione 2.0.0 o 2.0.1 installata
- Vuoi solo il fix degli stili
- Vuoi un aggiornamento veloce senza reinstallare tutto

---

## 🚀 OPZIONE A: Installazione Completa

### Step 1: Backup

```bash
# Via phpMyAdmin
Database → Esporta

# Via SSH (opzionale)
mysqldump -u USERNAME -p DATABASE_NAME > backup_joomla_$(date +%Y%m%d).sql
```

### Step 2: Disinstalla Versione Precedente (se presente)

1. Vai su **Joomla Admin** → **Extensions** → **Manage**
2. Cerca "Landing Pages"
3. Seleziona e clicca **Uninstall**
4. ⚠️ **ATTENZIONE**: Questo NON elimina i dati del database

### Step 3: Installa Nuova Versione

1. Vai su **Joomla Admin** → **Extensions** → **Install**
2. **Upload Package File**
3. Seleziona `joomla-landingpages-v2.0.2-full-styles.tar.gz`
4. Clicca **Upload & Install**
5. Attendi il completamento (può richiedere qualche minuto)

### Step 4: Configura Componente

1. Vai su **Components** → **Landing Pages**
2. Clicca **Options** (icona ingranaggio in alto a destra)
3. Configura:
   - **API URL**: URL della tua API backend (es: `https://edysma.net/ELPB/backend/public/api`)
   - **reCAPTCHA Keys**: Chiavi reCAPTCHA (opzionale)
4. Salva

### Step 5: Verifica

1. Vai su una landing page esistente nel sito Joomla
2. Verifica che:
   - Il font si applica correttamente
   - Gli stili dei blocchi funzionano
   - Non ci sono errori 500

---

## ⚡ OPZIONE B: Aggiornamento Veloce (Solo File)

### Step 1: Backup

```bash
# Backup solo blockrenderer.php
cp components/com_landingpages/helpers/blockrenderer.php components/com_landingpages/helpers/blockrenderer.php.bak
```

### Step 2: Upload via FTP/SSH

#### Via FTP:
1. Estrai `joomla-blockrenderer-v2.0.2-update.tar.gz` localmente
2. Carica via FTP:
   - `blockrenderer.php` → `/components/com_landingpages/helpers/blockrenderer.php`
   - `landingpages.xml` → `/administrator/components/com_landingpages/landingpages.xml`

#### Via SSH:
```bash
# Carica l'archivio sul server
scp joomla-blockrenderer-v2.0.2-update.tar.gz user@server:/tmp/

# Connetti via SSH
ssh user@server

# Estrai nella posizione corretta
cd /path/to/joomla
tar -xzf /tmp/joomla-blockrenderer-v2.0.2-update.tar.gz

# Verifica permessi
chmod 644 components/com_landingpages/helpers/blockrenderer.php
chmod 644 administrator/components/com_landingpages/landingpages.xml
chown www-data:www-data components/com_landingpages/helpers/blockrenderer.php
chown www-data:www-data administrator/components/com_landingpages/landingpages.xml
```

### Step 3: Verifica

1. Vai su **Joomla Admin** → **Extensions** → **Manage**
2. Cerca "Landing Pages"
3. Verifica che la versione sia **2.0.2**
4. Apri una landing page e verifica gli stili

---

## ✅ Test Post-Installazione

### 1. Test Font Family

1. Vai nell'editor della landing page
2. **Impostazioni Pagina** → **Stili** → **Font Family**: seleziona "Montserrat"
3. Salva
4. Visualizza la pagina su Joomla
5. ✅ Verifica che TUTTI i blocchi usino Montserrat

### 2. Test Background Color

1. Seleziona un blocco (es: Hero)
2. **Stili Blocco** → **Background Color**: scegli un colore (es: #1e40af blu)
3. Salva
4. Visualizza la pagina su Joomla
5. ✅ Verifica che lo sfondo del blocco sia blu

### 3. Test Text Color

1. Seleziona un blocco (es: Text)
2. **Stili Blocco** → **Text Color**: scegli un colore (es: #ffffff bianco)
3. Salva
4. Visualizza la pagina su Joomla
5. ✅ Verifica che il testo sia bianco

### 4. Test Padding

1. Seleziona un blocco
2. **Stili Blocco** → **Padding**: imposta "60px"
3. Salva
4. Visualizza la pagina su Joomla
5. ✅ Verifica che il blocco abbia più spazio interno

### 5. Test Rounded Corners

1. Seleziona un blocco
2. **Stili Blocco** → **Rounded Corners**: disattiva
3. Salva
4. Visualizza la pagina su Joomla
5. ✅ Verifica che gli angoli siano quadrati (non arrotondati)

---

## 🔄 Rollback (in caso di problemi)

### Se hai fatto l'installazione completa (Opzione A):

1. Disinstalla versione 2.0.2
2. Reinstalla la versione precedente dal backup
3. Ripristina il database:
   ```bash
   mysql -u USERNAME -p DATABASE_NAME < backup_joomla_YYYYMMDD.sql
   ```

### Se hai fatto l'aggiornamento veloce (Opzione B):

```bash
# Via SSH
cd /path/to/joomla
cp components/com_landingpages/helpers/blockrenderer.php.bak components/com_landingpages/helpers/blockrenderer.php
```

O via FTP:
- Ripristina `blockrenderer.php.bak` → `blockrenderer.php`

---

## 🆘 Troubleshooting

### Errore: "Component not found"

**Causa:** Joomla non riconosce il componente

**Soluzione:**
1. Vai su **System** → **Clear Cache**
2. Vai su **Extensions** → **Discover**
3. Clicca **Discover**
4. Seleziona "Landing Pages" e clicca **Install**

---

### Errore: "Class 'BlockRenderer' not found"

**Causa:** Il file blockrenderer.php non è stato caricato correttamente

**Soluzione:**
1. Verifica che il file esista:
   ```bash
   ls -la components/com_landingpages/helpers/blockrenderer.php
   ```
2. Verifica i permessi:
   ```bash
   chmod 644 components/com_landingpages/helpers/blockrenderer.php
   ```

---

### Gli stili non si applicano

**Causa:** Cache del browser o Joomla

**Soluzione:**
1. **Joomla Admin** → **System** → **Clear Cache**
2. Svuota la cache del browser (Ctrl+Shift+R)
3. Ricarica la pagina

---

### Il font non cambia

**Causa:** Il font potrebbe non essere caricato

**Soluzione:**
1. Verifica che il font sia disponibile su Google Fonts
2. Apri la console del browser (F12) → Tab "Network"
3. Cerca richieste a `fonts.googleapis.com`
4. Se non ci sono, il font potrebbe non esistere o essere scritto male

**Font supportati:** Qualsiasi font su Google Fonts (es: "Roboto", "Open Sans", "Montserrat", "Lato", "Poppins")

---

## 📋 Checklist Finale

- [ ] Backup database fatto
- [ ] Backup file fatto (se Opzione A)
- [ ] Componente installato/aggiornato
- [ ] Versione 2.0.2 confermata in Extensions Manager
- [ ] Test Font Family → OK
- [ ] Test Background Color → OK
- [ ] Test Text Color → OK
- [ ] Test Padding → OK
- [ ] Test Rounded Corners → OK
- [ ] Nessun errore 500
- [ ] Landing pages esistenti funzionano

---

## 📝 Note Aggiuntive

### Compatibilità
- ✅ Joomla 4.x
- ✅ PHP 7.4+
- ✅ MySQL 5.7+

### Retrocompatibilità
- ✅ Le landing pages esistenti continueranno a funzionare
- ✅ Nessuna migration database richiesta
- ✅ Nessun dato perso

### Performance
- ✅ Nessun impatto sulle performance
- ✅ Stili inline minimali
- ✅ Font caricato da Google Fonts (cached)

---

**Installazione Completata! 🎉**

Ora il tuo componente Joomla supporta completamente gli stili dei blocchi, proprio come nell'editor Vue.js!
