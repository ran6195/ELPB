# Troubleshooting Email non arrivano su Gmail

## Problema
Le email di notifica vengono inviate correttamente dal server SMTP Aruba (`250 2.0.0 Ok: queued`), ma non arrivano agli indirizzi Gmail.

---

## Cause comuni

### 1. **Email finisce in SPAM**
Gmail ha filtri anti-spam molto aggressivi. Le email potrebbero essere state recapitate ma finite in spam.

**Soluzione:**
1. Controlla la cartella **Spam** in Gmail
2. Se trovi l'email, clicca su **"Non è spam"**
3. Aggiungi il mittente (`elpbuilder@vu000816.arubabiz.net`) ai contatti

---

### 2. **Dominio mittente non verificato (SPF/DKIM)**
Gmail verifica l'autenticità del mittente tramite record SPF e DKIM. Se il dominio `vu000816.arubabiz.net` non ha questi record configurati, Gmail può rifiutare l'email.

**Verifica SPF/DKIM:**
```bash
# Controlla record SPF
dig TXT vu000816.arubabiz.net

# Controlla DKIM
dig TXT default._domainkey.vu000816.arubabiz.net
```

**Soluzione:**
- Contatta Aruba e chiedi di configurare SPF e DKIM per il dominio
- Oppure usa un dominio personalizzato con SPF/DKIM configurati

---

### 3. **IP server in blacklist**
L'IP del server SMTP Aruba potrebbe essere in blacklist temporanea.

**Verifica blacklist:**
1. Trova IP del server: `nslookup vu000816.arubabiz.net`
2. Controlla blacklist: https://mxtoolbox.com/blacklists.aspx
3. Inserisci l'IP e verifica

**Soluzione:**
- Se in blacklist, contatta Aruba per rimozione
- Usa un servizio SMTP transazionale (SendGrid, Mailgun, Amazon SES)

---

### 4. **Rate limiting Gmail**
Gmail limita le email da mittenti sconosciuti. Se invii molte email in poco tempo, Gmail può bloccarle temporaneamente.

**Soluzione:**
- Invia poche email di test inizialmente
- Costruisci "reputazione" del mittente gradualmente
- Usa un servizio transazionale per volumi alti

---

### 5. **Email rifiutata senza notifica (soft bounce)**
Gmail può rifiutare silenziosamente email da mittenti non fidati senza inviare bounce.

**Verifica:**
```bash
# Controlla log server SMTP Aruba (se disponibili)
# Oppure usa test-email.php con debug attivo
```

---

## Come verificare

### Test 1: Email di test base
```bash
cd backend
php test-email.php
# Inserisci indirizzo Gmail
```

Controlla:
- ✅ "250 2.0.0 Ok: queued" → Email accettata da server
- 📧 Controlla casella Gmail (anche spam)

---

### Test 2: Notifica lead completa
```bash
cd backend
php test-lead-email.php
# Seleziona pagina con notifiche abilitate
```

Controlla log:
```bash
tail -f backend/logs/error.log
# oppure
tail -f /var/log/php_errors.log
```

---

### Test 3: Header email (se arriva in spam)
Se l'email arriva in spam, apri l'email e:
1. Clicca su **"Mostra originale"** (o "Show original")
2. Cerca questi header:

```
SPF: PASS / FAIL / SOFTFAIL
DKIM: PASS / FAIL
DMARC: PASS / FAIL
```

Se vedi **FAIL** o **SOFTFAIL**, il problema è SPF/DKIM non configurato.

---

## Soluzioni definitive

### Soluzione 1: Configurare SPF su Aruba (Consigliato)
Contatta supporto Aruba e chiedi di aggiungere record SPF:

```
Tipo: TXT
Nome: @
Valore: v=spf1 include:_spf.arubabiz.net ~all
```

---

### Soluzione 2: Usare servizio transazionale
Per deliverability al 100%, usa un servizio SMTP transazionale:

**SendGrid** (12k email/mese gratis):
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=<sendgrid_api_key>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tuodominio.com
```

**Mailgun** (5k email/mese gratis):
```env
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@tuodominio.mailgun.org
MAIL_PASSWORD=<mailgun_password>
MAIL_ENCRYPTION=tls
```

---

### Soluzione 3: Dominio personalizzato
Se hai un dominio tuo (es. `tuodominio.com`):

1. Configura SPF su Cloudflare/GoDaddy/registro dominio:
   ```
   TXT @ "v=spf1 include:_spf.arubabiz.net ~all"
   ```

2. Usa dominio personalizzato come mittente:
   ```env
   MAIL_FROM_ADDRESS=noreply@tuodominio.com
   ```

---

## Modifiche già applicate

### ✅ Mittente autentico
```env
# Prima (PROBLEMATICO)
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Ora (CORRETTO)
MAIL_FROM_ADDRESS=elpbuilder@vu000816.arubabiz.net
```

### ✅ Header anti-spam
- Message-ID con dominio reale
- X-Priority, Importance header
- Encoding base64
- XMailer rimosso
- Plain text alternativo (AltBody)

### ✅ SMTP ottimizzato
- SMTPKeepAlive attivo
- SMTPAutoTLS attivo
- Timeout 10s

---

## Checklist verifica Gmail

- [ ] Email presente in **Spam**?
- [ ] Email presente in **Posta in arrivo**?
- [ ] Email presente in **Tutti i messaggi**?
- [ ] Controlla filtri Gmail (Impostazioni → Filtri)
- [ ] Controlla inoltri automatici (Impostazioni → Inoltro)
- [ ] Verifica spazio disponibile Gmail (non piena)
- [ ] Aspetta 5-10 minuti (ritardo normale)

---

## Log utili

### Backend PHP
```bash
tail -f backend/logs/error.log
```

### Log SMTP (se disponibili)
Contatta Aruba per accesso ai log SMTP del server `vu000816.arubabiz.net`.

---

## Script diagnostici

**test-email.php** - Test SMTP base
```bash
php backend/test-email.php
```

**test-lead-email.php** - Test notifica completa
```bash
php backend/test-lead-email.php
```

---

## FAQ

**Q: L'email dice "250 Ok: queued" ma non arriva. Perché?**
A: "Queued" significa che il server SMTP Aruba ha accettato l'email. Il problema è nel tragitto Aruba → Gmail (SPF, blacklist, spam filter).

**Q: Le email arrivano su altri provider (Outlook, Yahoo) ma non Gmail?**
A: Gmail ha filtri più stretti. Configura SPF/DKIM o usa servizio transazionale.

**Q: Quanto tempo ci vuole per arrivare?**
A: Normalmente 1-30 secondi. Se dopo 10 minuti non arriva, controlla spam o è stata rifiutata.

**Q: Posso forzare Gmail ad accettare le email?**
A: No, ma puoi migliorare deliverability con SPF/DKIM e costruendo reputazione del mittente.

---

## Prossimi passi

1. **Breve termine**: Controlla spam Gmail
2. **Medio termine**: Richiedi configurazione SPF ad Aruba
3. **Lungo termine**: Valuta servizio transazionale per deliverability garantita
