# Quick Start - Standalone Renderer

Guida rapida per installare e usare lo standalone renderer in 5 minuti.

## 1. Copia i file

Carica tutti i file sul tuo server:

```
tuo-sito.com/
└── landing/              # o qualsiasi directory
    ├── page.php
    ├── config.php
    ├── BlockRenderer.php
    ├── .env.example
    └── .htaccess
```

## 2. Configura l'API

Crea il file `.env` dalla copia di esempio:

```bash
cp .env.example .env
```

Modifica `.env` con l'URL della tua API:

```env
API_BASE_URL=https://tuo-dominio.com/backend/public/api
DEBUG=false
```

## 3. Imposta i Permessi

**IMPORTANTE per la sicurezza!**

```bash
chmod +x set-permissions.sh
./set-permissions.sh
```

O manualmente:
```bash
chmod 644 *.php
chmod 600 .env    # ← Critico!
chmod 644 .htaccess
```

**Verifica**: Il file `.env` deve essere `600` (solo tu puoi leggerlo)

## 4. Testa

Apri nel browser:

```
https://tuo-sito.com/landing/page.php?slug=homepage
```

Se hai configurato `.htaccess`:

```
https://tuo-sito.com/landing/homepage
```

## 5. Pubblica una pagina

1. Vai nell'editor dell'applicazione principale
2. Crea o modifica una pagina
3. Clicca su **"Pubblica"**
4. Accedi alla pagina tramite lo slug

## Esempio Completo

### Backend API
```
https://api.tuodominio.com/api
```

### Standalone Renderer
```
https://sito.tuodominio.com/
```

### Configurazione .env
```env
API_BASE_URL=https://api.tuodominio.com/api
```

### Accesso pagina
```
https://sito.tuodominio.com/homepage
https://sito.tuodominio.com/chi-siamo
https://sito.tuodominio.com/contatti
```

## Troubleshooting Rapido

### Errore 500
- Controlla i permessi dei file (644)
- Verifica che cURL sia abilitato
- Attiva `DEBUG=true` nel .env

### Pagina bianca
- Controlla che l'API sia raggiungibile
- Verifica che la pagina sia pubblicata
- Controlla i log PHP del server

### Form non funziona
- Verifica `API_BASE_URL` nel .env
- Controlla che l'endpoint `/api/leads` risponda
- Verifica CORS nel backend

## Prossimi Passi

Leggi il [README.md](README.md) completo per:
- Configurazione avanzata
- URL rewriting
- Cache
- Sicurezza
- Personalizzazioni

---

Hai domande? Consulta la documentazione completa nel README.md
