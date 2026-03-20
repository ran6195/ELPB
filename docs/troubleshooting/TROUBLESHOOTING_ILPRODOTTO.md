# Troubleshooting "Pagina non trovata" su ilprodotto.it

## Problema
Tutte le pagine su ilprodotto.it/standalone-renderer/page.php?slug=XXX danno "Pagina non trovata".

## Diagnosi e Soluzioni

### STEP 1: Abilita Debug Mode

Sul server **ilprodotto.it**, modifica `standalone-renderer/.env`:

```env
DEBUG=true
```

Poi apri di nuovo la pagina e leggi l'errore completo che appare.

---

### STEP 2: Verifica Pagine Pubblicate

Sul server **edysma.net**, esegui questo script per vedere quali pagine sono pubblicate:

```bash
cd /percorso/a/ELPB/backend
php check_published_pages.php
```

**Problema comune:** Le pagine hanno `is_published = 0`

**Soluzione:**
1. Apri l'editor su https://edysma.net/ELPB
2. Vai sulla pagina che vuoi pubblicare
3. Clicca su "Impostazioni Pagina" (pannello destra)
4. Abilita il toggle "Pubblica questa pagina"
5. Salva

---

### STEP 3: Test Connessione API

**Carica il file di test su ilprodotto.it:**

1. Copia `test-api-connection.php` nella root di ilprodotto.it
2. Apri https://ilprodotto.it/test-api-connection.php
3. Leggi i risultati del test

**Errori comuni:**

#### A) "cURL non è disponibile"
```
✗ ERRORE: cURL non è disponibile
```
**Soluzione:** Contatta l'hosting e chiedi di abilitare l'estensione PHP cURL.

#### B) "Errore cURL: Could not resolve host"
```
✗ ERRORE cURL: Could not resolve host: edysma.net
```
**Soluzione:** Problema DNS o firewall. Il server ilprodotto.it non riesce a raggiungere edysma.net.
- Verifica che edysma.net sia online
- Contatta l'hosting per verificare se c'è un firewall che blocca le connessioni esterne

#### C) "ERRORE 404: Pagina non trovata"
```
HTTP Code: 404
```
**Soluzione:** La pagina esiste ma non è pubblicata. Torna allo STEP 2.

#### D) "ERRORE HTTP 500"
```
HTTP Code: 500
```
**Soluzione:** Errore lato backend. Controlla i log PHP su edysma.net.

---

### STEP 4: Verifica File .env Caricato

Sul server **ilprodotto.it**, verifica che il file `.env` esista e sia leggibile:

```bash
cd standalone-renderer
ls -la .env
cat .env
```

**Contenuto corretto del .env:**
```env
API_BASE_URL=https://edysma.net/ELPB/backend/public/api
DEBUG=true
```

**Permessi corretti:**
```bash
chmod 600 .env
```

---

### STEP 5: Test Manuale cURL

Dal server **ilprodotto.it**, prova a chiamare l'API manualmente:

```bash
curl -v "https://edysma.net/ELPB/backend/public/api/page/homepage"
```

**Risposta corretta (HTTP 200):**
```json
{
  "id": 1,
  "title": "Homepage",
  "slug": "homepage",
  "is_published": true,
  "blocks": [...]
}
```

**Risposta non trovata (HTTP 404):**
```json
{
  "error": "Page not found or not published"
}
```

---

### STEP 6: Verifica Slug Corretto

Assicurati di usare lo slug esatto come salvato nel database.

**Esempi:**
- ✓ `page.php?slug=homepage` (minuscolo)
- ✗ `page.php?slug=Homepage` (maiuscolo - non funziona)
- ✗ `page.php?slug=home-page` (trattino sbagliato)

---

## Checklist Rapida

- [ ] DEBUG=true nel .env di ilprodotto.it
- [ ] Pagina pubblicata (is_published = 1) su edysma.net
- [ ] cURL abilitato su ilprodotto.it
- [ ] API raggiungibile da ilprodotto.it (test-api-connection.php)
- [ ] File .env presente e leggibile (chmod 600)
- [ ] Slug corretto (tutto minuscolo, solo lettere/numeri/trattini)

---

## Comandi Utili

### Su edysma.net (backend)

```bash
# Vai nella directory backend
cd /percorso/a/ELPB/backend

# Verifica pagine pubblicate
php check_published_pages.php

# Verifica database
mysql -u utente -p landing_page_builder -e "SELECT id, title, slug, is_published FROM pages WHERE deleted_at IS NULL;"
```

### Su ilprodotto.it (renderer)

```bash
# Vai nella directory renderer
cd /percorso/a/standalone-renderer

# Verifica permessi
ls -la

# Test API
curl "https://edysma.net/ELPB/backend/public/api/page/homepage"

# Verifica .env
cat .env
```

---

## Script di Test Completo

Esegui questo sul server **ilprodotto.it** per un test completo:

```bash
#!/bin/bash
echo "=== TEST RENDERER STANDALONE ==="
echo ""

echo "1. File .env esiste?"
if [ -f standalone-renderer/.env ]; then
    echo "✓ Sì"
    cat standalone-renderer/.env
else
    echo "✗ NO - File .env mancante!"
fi
echo ""

echo "2. cURL disponibile?"
if command -v curl &> /dev/null; then
    echo "✓ Sì"
else
    echo "✗ NO - cURL non installato!"
fi
echo ""

echo "3. Test API edysma.net"
curl -s "https://edysma.net/ELPB/backend/public/api/page/homepage" | head -20
echo ""

echo "4. Test renderer locale"
curl -s "http://localhost/standalone-renderer/page.php?slug=homepage" | head -20
echo ""
```

---

## Soluzione Rapida (Più Probabile)

Il problema più comune è che **le pagine non sono pubblicate**.

**Fix veloce:**
1. Vai su https://edysma.net/ELPB
2. Apri l'editor della pagina
3. Nel pannello "Impostazioni Pagina" a destra
4. Abilita "Pubblica questa pagina"
5. Clicca "Salva Modifiche"
6. Riprova su ilprodotto.it

---

## Contatti per Supporto

Se il problema persiste dopo aver seguito tutti gli step:

1. Invia il risultato di `test-api-connection.php`
2. Invia il contenuto del file `.env` (oscurando eventuali password)
3. Invia lo screenshot dell'errore con DEBUG=true
