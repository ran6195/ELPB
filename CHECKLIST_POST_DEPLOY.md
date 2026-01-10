# ✅ Checklist Post-Deploy

## 🎉 Deploy Completato con Successo!

L'applicazione è ora online su: **https://edysma.net/ELPB/**

## 🔒 Sicurezza - DA FARE SUBITO

### 1. Cambia Password Admin
- [ ] Login con `admin@example.com` / `admin123`
- [ ] Vai nelle impostazioni utente
- [ ] Cambia la password con una sicura

### 2. Cambia Credenziali Utenti Demo
- [ ] `company@example.com` / `company123` → Cambia password
- [ ] `user@example.com` / `user123` → Cambia password
- [ ] Oppure elimina questi utenti se non servono

### 3. Verifica JWT_SECRET
- [ ] Controlla che in `backend/.env` il `JWT_SECRET` sia stato cambiato
- [ ] NON deve essere il valore di default
- [ ] Genera uno nuovo: `openssl rand -base64 64`

### 4. Disabilita Debug in Produzione
- [ ] Verifica che `APP_DEBUG=false` in `backend/.env`
- [ ] Considera di rimuovere/proteggere le route `/api/debug/*`

## ✅ Test Funzionalità

### Test Completo dell'App
- [ ] **Login**: Accedi con le credenziali admin
- [ ] **Dashboard**: Vedi la lista delle pagine
- [ ] **Crea Pagina**: Crea una nuova pagina di test
- [ ] **Editor**:
  - [ ] Aggiungi vari blocchi (header, hero, form, footer)
  - [ ] Modifica i contenuti inline
  - [ ] Testa drag & drop dei blocchi
  - [ ] Prova le visualizzazioni Desktop/Tablet/Mobile
- [ ] **Salvataggio**: Salva la pagina
- [ ] **Upload Immagini**: Carica un'immagine in un blocco
- [ ] **Impostazioni Pagina**:
  - [ ] Cambia slug
  - [ ] Imposta meta title/description
  - [ ] Attiva/disattiva angoli arrotondati
  - [ ] Aggiungi custom CSS
- [ ] **Pubblicazione**: Pubblica la pagina
- [ ] **Pagina Pubblica**: Visita la pagina all'URL pubblico
- [ ] **Form Lead**:
  - [ ] Compila un form e invia
  - [ ] Verifica che la lead appaia nel pannello admin
- [ ] **Duplicazione**: Duplica una pagina esistente
- [ ] **Protezione Eliminazione**: Prova a eliminare una pagina pubblicata (deve bloccare)
- [ ] **Rimuovi Pubblicazione**: Spubblica e poi elimina

### Test Permessi (se hai più utenti)
- [ ] **Admin**: Può vedere/modificare tutte le pagine
- [ ] **Company Manager**: Può gestire utenti e pagine della sua company
- [ ] **User**: Può vedere solo le sue pagine

### Test Responsive
- [ ] Apri una pagina pubblica da smartphone
- [ ] Verifica che sia responsive
- [ ] Testa il menu header su mobile
- [ ] Testa i form su mobile

## 🔧 Configurazione Server

### Backup
- [ ] Fai un backup del database
  ```bash
  mysqldump -u username -p landing_page_builder > backup_$(date +%Y%m%d).sql
  ```
- [ ] Fai un backup dei file caricati
  ```bash
  tar -czf uploads_backup_$(date +%Y%m%d).tar.gz /var/www/html/ELPB/backend/public/uploads/
  ```

### Monitoraggio
- [ ] Verifica i log di Apache per errori
  ```bash
  sudo tail -f /var/log/apache2/error.log
  ```
- [ ] Monitora lo spazio disco per le immagini caricate
  ```bash
  du -sh /var/www/html/ELPB/backend/public/uploads/
  ```

### Performance
- [ ] Verifica che la compressione gzip sia attiva
- [ ] Controlla che il caching dei file statici funzioni
- [ ] Considera di configurare un CDN per le immagini (opzionale)

## 📝 Documentazione per gli Utenti

### Crea Guide per gli Utenti
- [ ] Come creare una landing page
- [ ] Come pubblicare una pagina
- [ ] Come inserire un form
- [ ] Come gestire le lead ricevute
- [ ] Guida ai blocchi disponibili

## 🚀 Prossimi Passi (Opzionale)

### SEO e Marketing
- [ ] Configura Google Analytics (se necessario)
- [ ] Imposta reCAPTCHA per i form
- [ ] Verifica meta tag OpenGraph per social sharing
- [ ] Testa la sitemap

### Funzionalità Future
- [ ] Notifiche email per nuove lead
- [ ] Template di pagine predefiniti
- [ ] Integrazione con CRM
- [ ] A/B testing
- [ ] Analytics integrate

## 📞 Contatti e Supporto

### In Caso di Problemi
- Consulta `TROUBLESHOOTING.md`
- Consulta `RISOLUZIONE_ERRORE_MIME.md`
- Consulta `SOLUZIONE_ALTERNATIVA.md`
- Controlla i log: `/var/log/apache2/error.log`

### File Importanti
- `DEPLOY.md` - Guida completa deploy
- `CHECKLIST_DEPLOY.md` - Checklist deploy
- `CLAUDE.md` - Documentazione progetto

---

## ✨ Congratulazioni!

La tua applicazione Landing Page Builder è ora online e funzionante! 🎉

**URL Applicazione**: https://edysma.net/ELPB/
**URL API**: https://edysma.net/ELPB/backend/public/api
**Credenziali Admin**: admin@example.com / admin123 (DA CAMBIARE!)

---

**Data Deploy**: 2025-12-19
**Versione**: 1.0.0
