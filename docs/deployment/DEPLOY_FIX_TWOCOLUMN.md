# Deploy Fix Blocchi Two-Column

## Problema Risolto
I blocchi `two-column-text-image` e `two-column-image-text` non visualizzavano le immagini nel renderer standalone e Joomla.

**Causa**: I renderer cercavano `content['imageUrl']` invece di `content['image']` (usato dai componenti Vue).

---

## 📋 Riepilogo File Deploy

### Renderer Standalone
- **File**: `standalone-renderer-fix-twocolumn.zip`
- **Dimensione**: 13K
- **Destinazione**: ilprodotto.it, primehome.it, infortunisticaveneta.it
- **Percorso**: `/public_html/lp/BlockRenderer.php`

### Componente Joomla
- **File**: `joomla-landingpages-v2.0.1-fix-twocolumn.zip`
- **Dimensione**: 14M
- **Versione**: 2.0.1 (upgrade da 2.0.0)
- **Destinazione**: edysma.net
- **Metodo**: Extensions > Manage > Install > Upload Package

### File Modificati
1. `standalone-renderer/BlockRenderer.php`
2. `backend/standalone-renderer/BlockRenderer.php`
3. `joomla-component/com_landingpages/site/helpers/blockrenderer.php`
4. `joomla-update-slider-font/blockrenderer.php`

---

## 📦 Deploy Standalone Renderer (ilprodotto.it, primehome.it)

### File da Aggiornare
```
BlockRenderer.php
```

### Istruzioni Deploy

1. **Scarica il file aggiornato**:
   - File: `standalone-renderer-fix-twocolumn.zip`
   - Contiene: `standalone-renderer/BlockRenderer.php`

2. **Carica via FTP/SFTP**:

   **Per ilprodotto.it**:
   ```
   Percorso remoto: /public_html/lp/BlockRenderer.php
   ```

   **Per primehome.it**:
   ```
   Percorso remoto: /public_html/lp/BlockRenderer.php
   ```

   **Per infortunisticaveneta.it**:
   ```
   Percorso remoto: /www.infortunisticaveneta.it/lp/BlockRenderer.php
   ```

3. **Verifica**:
   - Apri una landing page con blocchi two-column
   - Verifica che le immagini siano visualizzate correttamente

### Modifica Manuale (alternativa)

Se preferisci modificare manualmente il file sul server:

**Cerca** (riga ~761):
```php
$imageUrl = htmlspecialchars($content['imageUrl'] ?? '');
```

**Sostituisci con**:
```php
$image = htmlspecialchars($content['image'] ?? '');
```

**E nella riga dell'img** (~775):
```php
<img src="{$imageUrl}" alt="{$title}" class="w-full h-auto {$roundedClass}">
```

**Sostituisci con**:
```php
<img src="{$image}" alt="{$title}" class="w-full h-auto {$roundedClass}">
```

**Ripeti per entrambi i metodi**:
- `renderTwocolumntextimage()` (riga ~757)
- `renderTwocolumnimagetext()` (riga ~786)

---

## 📦 Deploy Componente Joomla (edysma.net)

### Opzione 1: Aggiorna componente completo (RACCOMANDATO)

**File**: `joomla-landingpages-v2.0.1-fix-twocolumn.zip` (versione 2.0.1)

1. **Accedi al pannello amministrativo Joomla** su edysma.net

2. **Vai a**: Extensions > Manage > Install

3. **Upload del pacchetto**:
   - Clicca su "Upload Package File"
   - Seleziona `com_landingpages.zip`
   - Clicca "Upload & Install"

4. **Joomla rileverà l'upgrade** dalla versione 2.0.0 → 2.0.1

5. **Verifica**:
   - Apri una landing page Joomla con blocchi two-column
   - Verifica che le immagini siano visualizzate

### Opzione 2: Aggiorna solo il file blockrenderer.php

Se preferisci non reinstallare il componente completo:

1. **Accedi via FTP/SFTP a edysma.net**

2. **Carica il file**:
   ```
   File locale: joomla-component/com_landingpages/site/helpers/blockrenderer.php
   Percorso remoto: /components/com_landingpages/helpers/blockrenderer.php
   ```

3. **Verifica** che il file sia stato sovrascritto correttamente

---

## 🧪 Test Post-Deploy

Dopo il deploy, verifica che:

1. **Blocchi esistenti** - Le landing pages con blocchi two-column esistenti mostrano le immagini
2. **Nuovi blocchi** - Creando nuovi blocchi two-column, le immagini vengono visualizzate correttamente
3. **Editor** - L'editor continua a funzionare normalmente
4. **Preview** - La preview mostra le immagini correttamente

---

## 🔄 Rollback

In caso di problemi, ripristina il file originale sostituendo `$image` con `$imageUrl`.

---

**Data fix**: 2025-12-29
**Blocchi interessati**: `two-column-text-image`, `two-column-image-text`
**Renderer aggiornati**: Standalone, Joomla, Backend
