# Import/Export Pagine

Sistema per trasferire pagine tra diversi server o creare backup.

## Funzionalità

### Export
- Esporta una pagina in formato JSON
- Include tutti i blocchi con contenuti e stili
- Include impostazioni pagina (meta tag, tracking, reCAPTCHA, quick contacts)
- NON include relazioni utente (user_id, company_id)
- NON include stato pubblicazione (sempre importata come bozza)

### Import
- Importa una pagina da file JSON
- La pagina diventa proprietà dell'utente che importa
- Verifica unicità slug automaticamente
- Se lo slug esiste già, aggiunge suffisso random (es: `homepage-a3f2b1`)
- Sempre importata come non pubblicata (is_published = false)

## Come Usare

### Esportare una Pagina

**Dashboard:**
1. Vai su "Le Mie Landing Pages"
2. Trova la pagina da esportare
3. Clicca sul pulsante verde **"Esporta"**
4. Il file JSON viene scaricato automaticamente

**API:**
```bash
GET /api/pages/{id}/export
Authorization: Bearer {token}
```

**Formato File Esportato:**
```json
{
  "version": "1.0",
  "exported_at": "2026-01-24 10:30:00",
  "page": {
    "title": "Homepage",
    "slug": "homepage",
    "meta_title": "Benvenuto",
    "meta_description": "La nostra homepage",
    "styles": {
      "backgroundColor": "#FFFFFF",
      "blockGap": 0,
      "fontFamily": "Inter",
      "roundedCorners": true,
      "containerWidth": "max-w-7xl"
    },
    "tracking_settings": {
      "gtm_enabled": true,
      "gtm_id": "GTM-XXXXXX"
    },
    "recaptcha_settings": {
      "enabled": true,
      "site_key": "6Le..."
    },
    "quick_contacts": {
      "enabled": true,
      "phone": "+39 123 456 7890",
      "email": "info@example.com"
    },
    "blocks": [
      {
        "type": "hero",
        "content": {
          "title": "Benvenuto",
          "subtitle": "Sottotitolo",
          "buttonText": "Scopri di più",
          "buttonLink": "#"
        },
        "styles": {
          "backgroundColor": "#3B82F6",
          "textColor": "#FFFFFF"
        },
        "position": {},
        "order": 0
      }
    ]
  }
}
```

### Importare una Pagina

**Dashboard:**
1. Vai su "Le Mie Landing Pages"
2. Clicca sul pulsante blu **"Importa"** in alto a destra
3. Seleziona il file JSON esportato
4. Clicca "Importa"
5. Se lo slug esiste già, verrà modificato automaticamente

**API:**
```bash
POST /api/pages/import
Authorization: Bearer {token}
Content-Type: application/json

{
  "version": "1.0",
  "exported_at": "2026-01-24 10:30:00",
  "page": { ... }
}
```

**Risposta Successo:**
```json
{
  "success": true,
  "message": "Page imported successfully",
  "page": {
    "id": 42,
    "title": "Homepage",
    "slug": "homepage-a3f2b1",
    "is_published": false,
    "user_id": 5,
    "company_id": 2,
    ...
  },
  "slug_changed": true,
  "original_slug": "homepage",
  "new_slug": "homepage-a3f2b1"
}
```

## Casi d'Uso

### 1. Trasferimento tra Server

**Scenario:** Hai un server di staging e uno di produzione.

**Procedura:**
1. **Su staging:** Crea e testa la pagina
2. **Su staging:** Esporta la pagina (download JSON)
3. **Su produzione:** Importa il file JSON
4. **Su produzione:** La pagina è importata come bozza
5. **Su produzione:** Verifica e pubblica

### 2. Backup Pagine

**Scenario:** Vuoi creare un backup prima di modifiche importanti.

**Procedura:**
1. Esporta la pagina corrente
2. Salva il file JSON in una cartella backup
3. Fai le modifiche
4. Se serve ripristinare: importa il file JSON salvato

### 3. Duplicazione Cross-Tenant

**Scenario:** Hai più aziende clienti e vuoi riusare template.

**Procedura:**
1. **Account Admin:** Crea template perfetto
2. **Account Admin:** Esporta template
3. **Account Cliente:** Importa template
4. **Account Cliente:** La pagina diventa proprietà del cliente
5. **Account Cliente:** Personalizza e pubblica

### 4. Migrazione Dati

**Scenario:** Devi migrare pagine da un database vecchio.

**Procedura:**
1. Crea script che genera JSON nel formato corretto
2. Per ogni pagina del vecchio DB:
   - Genera file JSON
   - POST /api/pages/import
3. Tutte le pagine vengono ricreate sul nuovo server

## Limitazioni

### NON Incluso nell'Export
- `id` (viene generato al momento dell'import)
- `user_id` (viene impostato all'utente che importa)
- `company_id` (viene impostato dall'utente che importa)
- `is_published` (sempre false per sicurezza)
- `created_at`, `updated_at` (generati automaticamente)
- `deleted_at` (non applicabile)
- Relazioni con lead esistenti

### File Upload
- **Max size immagini:** Limitate dal backend (default ~10MB)
- **Path immagini:** Se la pagina usa immagini caricate, le URL devono essere accessibili dal nuovo server
- **Video:** Come per le immagini, devono essere accessibili

### Slug Collision
- Se esiste già una pagina con lo stesso slug (anche archiviata)
- Viene aggiunto suffisso random: `slug-a3f2b1`
- Puoi poi modificare manualmente lo slug dall'editor

## API Reference

### Export Endpoint

```http
GET /api/pages/{id}/export
```

**Headers:**
- `Authorization: Bearer {token}` (required)

**Response:**
- `Content-Type: application/json`
- `Content-Disposition: attachment; filename="page-{slug}-{date}.json"`
- Body: JSON con struttura completa pagina

**Errori:**
- `404` - Page not found
- `403` - Forbidden (no permission to view page)
- `401` - Unauthorized (no auth token)

### Import Endpoint

```http
POST /api/pages/import
```

**Headers:**
- `Authorization: Bearer {token}` (required)
- `Content-Type: application/json`

**Body:**
```json
{
  "version": "1.0",
  "page": { ... }
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Page imported successfully",
  "page": { ... },
  "slug_changed": boolean,
  "original_slug": "string",
  "new_slug": "string"
}
```

**Errori:**
- `400` - Invalid import data / Title and slug required
- `401` - Unauthorized
- `500` - Import failed (server error)

## Sicurezza

### Permessi
- **Export:** Richiede permesso `canViewPage` (proprietario, company manager, admin)
- **Import:** Richiede autenticazione, nessun permesso speciale

### Validazione
- Verifica formato JSON valido
- Richiede campi obbligatori (title, slug)
- Normalizza slug (lowercase, no special chars)
- Verifica unicità slug (include soft-deleted)

### Ownership
- La pagina importata appartiene SEMPRE all'utente che la importa
- Non è possibile importare pagine per altri utenti
- `company_id` viene impostato dalla company dell'utente

## Best Practices

1. **Nomina File:** Usa nomi descrittivi per i file export
   ```
   homepage-staging-20260124.json
   template-hero-v2.json
   cliente-xyz-landing.json
   ```

2. **Versionamento:** Includi versione/data nel nome file per tracking

3. **Backup Regolari:** Esporta pagine importanti prima di modifiche

4. **Test Import:** Testa sempre l'import su staging prima di produzione

5. **Verifica Post-Import:**
   - Controlla che tutti i blocchi siano presenti
   - Verifica immagini e video funzionino
   - Testa form e tracking
   - Controlla responsive su mobile

6. **Slug Management:** Dopo import, personalizza lo slug se necessario

7. **Pubblicazione:** Verifica sempre prima di pubblicare pagina importata

## Troubleshooting

### Import fallisce con "Invalid import data"
- Verifica che il file JSON sia valido (usa jsonlint.com)
- Assicurati che ci siano i campi `title` e `slug`

### Slug modificato all'import
- Normale se esiste già una pagina con stesso slug
- Puoi modificare lo slug dall'editor dopo l'import

### Immagini non visibili dopo import
- Le URL immagini devono essere assolute o accessibili dal nuovo server
- Soluzione: ri-carica immagini sul nuovo server

### Video non funziona
- Come per le immagini, verifica accessibilità
- Ri-carica video se necessario

### Tracking GTM non funziona
- `tracking_settings` viene importato correttamente
- Verifica che il GTM ID sia valido sul nuovo server/dominio

### reCAPTCHA non funziona
- Le chiavi reCAPTCHA sono domain-specific
- Dopo import, aggiorna con chiavi corrette per il nuovo dominio

## Script Utility

### Export Massivo (Bash)
```bash
#!/bin/bash
# export-all-pages.sh

API_URL="https://api.example.com"
TOKEN="your-jwt-token"

# Get all page IDs
PAGE_IDS=$(curl -s "${API_URL}/api/pages" \
  -H "Authorization: Bearer ${TOKEN}" | jq -r '.[].id')

# Export each page
for PAGE_ID in $PAGE_IDS; do
  echo "Exporting page ${PAGE_ID}..."
  curl -s "${API_URL}/api/pages/${PAGE_ID}/export" \
    -H "Authorization: Bearer ${TOKEN}" \
    -o "page-${PAGE_ID}.json"
done

echo "Done! Exported $(echo $PAGE_IDS | wc -w) pages"
```

### Import Massivo (Bash)
```bash
#!/bin/bash
# import-all-pages.sh

API_URL="https://api-new.example.com"
TOKEN="your-jwt-token"

for FILE in page-*.json; do
  echo "Importing ${FILE}..."
  curl -s "${API_URL}/api/pages/import" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d @"${FILE}"
  echo ""
done

echo "Done!"
```

## Changelog

### v1.0 (2026-01-24)
- Implementazione iniziale export/import
- Formato JSON versione 1.0
- Gestione slug duplicati con suffisso random
- Ownership automatica all'utente importatore
- Validazione e sicurezza
