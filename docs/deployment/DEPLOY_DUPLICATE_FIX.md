# Fix Funzione Duplicate - Deploy Manuale

Guida per caricare i file necessari per abilitare la duplicazione pagine su edysma.net

## File da Caricare

Carica questi file sul server edysma.net mantenendo la struttura delle directory:

### 1. File Obbligatori

```
backend/public/index.php
  → Carica in: /ELPB/backend/public/index.php
  Contiene la definizione della rotta POST /api/pages/{id}/duplicate

backend/src/Controllers/PageController.php
  → Carica in: /ELPB/backend/src/Controllers/PageController.php
  Contiene il metodo duplicate() che gestisce la duplicazione
```

### 2. File Opzionali (già aggiornati in precedenza)

Questi file dovrebbero già essere sul server, ma se hai problemi puoi ricaricarli:

```
backend/src/Models/Page.php
  → Carica in: /ELPB/backend/src/Models/Page.php
  (Modello della pagina con relazioni)
```

## Procedura di Upload

### Via FTP/SFTP (FileZilla, Cyberduck, ecc.)

1. **Connettiti a edysma.net**
   - Host: edysma.net
   - Porta: 21 (FTP) o 22 (SFTP)
   - Usa le tue credenziali

2. **Naviga nella directory corretta**
   ```
   /percorso/al/sito/ELPB/backend/
   ```

3. **Carica i file**
   - `public/index.php` → sostituisci quello esistente
   - `src/Controllers/PageController.php` → sostituisci quello esistente

4. **Verifica i permessi**
   ```
   index.php: 644 (rw-r--r--)
   PageController.php: 644 (rw-r--r--)
   ```

### Via pannello di controllo hosting

1. Accedi al pannello di controllo (cPanel, Plesk, ecc.)
2. Vai nel File Manager
3. Naviga in `/ELPB/backend/`
4. Carica i file sostituendo quelli esistenti

## Verifica

Dopo l'upload, testa la funzione duplicate:

### Test 1: Via Browser Console

Apri https://edysma.net/ELPB e nella console:

```javascript
// Sostituisci TOKEN e ID_PAGINA con valori reali
fetch('https://edysma.net/ELPB/backend/public/api/pages/15/duplicate', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer TUO_TOKEN',
    'Content-Type': 'application/json'
  }
})
.then(r => r.json())
.then(d => console.log('✅ Duplicate OK:', d))
.catch(e => console.error('❌ Errore:', e));
```

### Test 2: Via curl (se hai SSH)

```bash
curl -X POST "https://edysma.net/ELPB/backend/public/api/pages/15/duplicate" \
  -H "Authorization: Bearer TUO_TOKEN" \
  -H "Content-Type: application/json"
```

### Risultato Atteso

✅ Status 200 con JSON:
```json
{
  "id": 16,
  "title": "Copia di Titolo Originale",
  "slug": "copia-di-titolo-originale",
  "is_published": false,
  ...
}
```

❌ Se ottieni ancora 405 o 403:
- Verifica che i file siano stati caricati correttamente
- Controlla i log di errore del server
- Verifica che mod_rewrite sia abilitato su Apache
- Controlla che non ci siano regole ModSecurity che bloccano la richiesta

## Troubleshooting

### Errore: "405 Method Not Allowed"
- Il file `index.php` non è stato caricato o sovrascritto correttamente
- Verifica che la cache di Apache/Nginx non stia servendo la vecchia versione
- Riavvia Apache/Nginx se hai accesso (non sempre necessario)

### Errore: "500 Internal Server Error"
- Controlla i log PHP del server
- Verifica che PageController.php sia stato caricato correttamente
- Assicurati che i permessi dei file siano corretti (644)

### Errore: "403 Forbidden"
- Potrebbe essere ModSecurity o un firewall che blocca
- Controlla i log del server
- Contatta il supporto hosting se persiste

### Funzione duplicate non appare nell'app
- Svuota la cache del browser (Ctrl+F5)
- Verifica che il frontend sia stato ribuildata e ricaricato

## Note

- Backup: Prima di sovrascrivere, fai backup dei file esistenti
- Cache: Potrebbe essere necessario attendere qualche minuto per la propagazione della cache
- Permessi: Se cambi i permessi, ricorda di impostarli a 644

## File Completi

I file da caricare si trovano in:
- `backend/public/index.php` (locale)
- `backend/src/Controllers/PageController.php` (locale)

Basta copiarli sul server mantenendo la stessa struttura di directory.
