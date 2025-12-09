# Troubleshooting - Landing Page Builder

## Problema: Lo slider non viene renderizzato

Se lo slider non appare correttamente nel componente Joomla, segui questi passaggi:

### 1. Verifica gli errori JavaScript nella Console

1. Apri la Developer Console del browser (F12)
2. Vai alla tab "Console"
3. Cerca eventuali errori in rosso
4. Se vedi errori relativi a Swiper o SliderBlock, continua con i passaggi successivi

### 2. Aggiorna i blocchi slider esistenti nel database

I blocchi slider creati prima degli ultimi aggiornamenti potrebbero mancare alcuni campi necessari. Esegui questo script per aggiornarli:

```bash
cd components/com_landingpages/api
php scripts/fix-slider-blocks.php
```

Questo script aggiunge automaticamente i seguenti campi ai blocchi slider esistenti:
- `slideHeight` (altezza dello slider)
- `slideAspectRatio` (proporzioni delle slide)
- `slideGap` (spazio tra le slide)

### 3. Verifica che Swiper sia caricato

Controlla nella console del browser:

```javascript
// Apri la console e digita:
typeof Swiper
```

Se ritorna `undefined`, significa che Swiper non è stato caricato correttamente.

### 4. Svuota la cache del browser

1. Apri DevTools (F12)
2. Tasto destro sul pulsante Refresh
3. Seleziona "Empty Cache and Hard Reload"

### 5. Verifica la struttura dei dati dello slider

Nel database, i blocchi slider dovrebbero avere questa struttura nel campo `content`:

```json
{
  "title": "La Nostra Gallery",
  "slides": [
    {
      "image": "https://example.com/image.jpg",
      "alt": "Descrizione immagine",
      "title": "Titolo slide",
      "description": "Descrizione slide"
    }
  ],
  "autoplay": true,
  "autoplayDelay": 3000,
  "loop": true,
  "slidesPerViewDesktop": 3,
  "slideGap": 20,
  "slideHeight": "",
  "slideAspectRatio": "square",
  "showNavigation": true,
  "showPagination": true
}
```

### 6. Ricrea lo slider

Se il problema persiste:

1. Elimina il blocco slider dalla pagina
2. Aggiungi un nuovo blocco slider
3. Configura le opzioni nello slider
4. Salva la pagina

### 7. Verifica i permessi dei file

Assicurati che i file JavaScript e CSS abbiano i permessi corretti:

```bash
chmod 644 components/com_landingpages/assets/app/assets/*.js
chmod 644 components/com_landingpages/assets/app/assets/*.css
```

### 8. Controlla il percorso API

Verifica che l'API backend sia accessibile:

```bash
# Testa l'endpoint API
curl http://tuosito.com/components/com_landingpages/api/public/index.php/api/pages
```

Dovresti ricevere una risposta JSON con la lista delle pagine.

## Altri problemi comuni

### Le immagini caricate non vengono visualizzate

**Causa**: Permessi insufficienti sulla cartella uploads

**Soluzione**:
```bash
chmod 755 components/com_landingpages/api/public/uploads/images
chown www-data:www-data components/com_landingpages/api/public/uploads/images
```

### Errore 500 quando si salva una pagina

**Causa**: Problemi con il database o configurazione PHP

**Soluzione**:
1. Verifica il file `.env` in `components/com_landingpages/api/.env`
2. Controlla i log PHP in `/var/log/apache2/error.log` o `/var/log/nginx/error.log`
3. Verifica che le tabelle del database esistano:
   ```bash
   mysql -u username -p database_name
   SHOW TABLES LIKE 'pages%';
   SHOW TABLES LIKE 'blocks%';
   ```

### L'autenticazione non funziona

**Causa**: Chiave JWT mancante o scaduta

**Soluzione**:
1. Verifica che `JWT_SECRET` sia impostato nel file `.env`
2. Rigenera una nuova chiave segreta se necessario:
   ```
   JWT_SECRET=your-very-long-and-random-secret-key-here
   ```

## Supporto

Se il problema persiste dopo aver seguito questi passaggi:

1. Raccogli le seguenti informazioni:
   - Versione PHP: `php -v`
   - Versione Joomla
   - Errori dalla console del browser (F12)
   - Errori dai log PHP

2. Controlla il file `CLAUDE.md` per informazioni sull'architettura

3. Verifica che tutti i file siano stati caricati correttamente sul server
