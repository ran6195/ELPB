# Piano Implementazione Email Alerts

## 📊 Analisi Sistema Attuale

### Stato Corrente
- ✅ Form nei blocchi landing pages
- ✅ Salvataggio leads nel database (tabella `leads`)
- ✅ Validazione email e privacy
- ✅ Supporto reCAPTCHA v2
- ✅ Relazioni: Lead → Page → User → Company
- ❌ Nessuna notifica email quando arriva un lead

### Struttura Dati Esistente
```
Lead {
  - page_id (FK)
  - name, email, phone, message
  - metadata (JSON)
  - privacy_accepted
  - appointment_requested, appointment_datetime
  - created_at, updated_at
}

Page {
  - user_id (proprietario pagina)
  - company_id
  - title, slug
  - is_published
  - recaptcha_settings (JSON)
  - tracking_settings (JSON)
}

User {
  - name, email
  - role (admin/company/user)
  - company_id
}
```

---

## 🎯 Obiettivo

**Inviare email automatiche quando arriva un nuovo lead da una landing page.**

### Destinatari Email
1. **Proprietario pagina** (User che ha creato la landing)
2. **Company manager** (opzionale - se abilitato)
3. **Email personalizzate** (configurabili per pagina)

---

## 📋 Inventario Requisiti

### 1️⃣ **Backend - Servizio Email**

#### A. Libreria/Servizio Email
**Opzioni disponibili**:

| Opzione | Pro | Contro | Costo |
|---------|-----|--------|-------|
| **PHPMailer** | Semplice, standalone, supporta SMTP | Necessita configurazione SMTP | Gratis |
| **Symfony Mailer** | Moderno, supporta molti provider | Dipendenza aggiuntiva | Gratis |
| **SendGrid API** | Affidabile, deliverability alta, analytics | Richiede API key, 3rd party | 100 email/giorno gratis |
| **Mailgun API** | Buona deliverability, logs | Richiede API key, 3rd party | 100 email/giorno gratis |
| **Amazon SES** | Economico, scalabile | Configurazione complessa | $0.10/1000 email |
| **PHP mail()** | Zero configurazione | Inaffidabile, spesso bloccato come spam | Gratis |

**Raccomandazione**: **PHPMailer con SMTP** (semplice, affidabile, no dipendenze esterne)

#### B. Configurazione Necessaria

**File `.env` (backend)**:
```bash
# Email Configuration
MAIL_DRIVER=smtp              # smtp, sendmail, mailgun, ses
MAIL_HOST=smtp.gmail.com      # Server SMTP
MAIL_PORT=587                 # 587 (TLS) o 465 (SSL)
MAIL_USERNAME=your@email.com  # Username SMTP
MAIL_PASSWORD=your_password   # Password/App Password
MAIL_ENCRYPTION=tls           # tls o ssl
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Landing Page Builder"

# Opzionale - API Services
SENDGRID_API_KEY=
MAILGUN_API_KEY=
MAILGUN_DOMAIN=
```

#### C. Nuove Classi Backend

**File da creare**:
```
backend/src/Services/EmailService.php
  - sendLeadNotification($lead, $page, $user)
  - sendTestEmail($to)
  - getEmailTemplate($templateName, $data)

backend/src/Templates/Email/
  - lead-notification.html   (Template email HTML)
  - lead-notification.txt    (Template email testo semplice)

backend/src/Utils/MailerFactory.php
  - createMailer() (Factory per istanziare PHPMailer)
```

**Metodi EmailService**:
```php
class EmailService {
    public function sendLeadNotification(Lead $lead, Page $page, User $user): bool
    public function sendBulkNotification(Lead $lead, array $recipients): bool
    public function testConnection(): bool
    private function getLeadEmailTemplate(Lead $lead, Page $page): string
}
```

---

### 2️⃣ **Database - Configurazione Email**

#### A. Modifiche Tabella `pages`

**Aggiungere campo JSON `email_settings`**:
```sql
ALTER TABLE pages ADD COLUMN email_settings JSON NULL AFTER tracking_settings;
```

**Struttura `email_settings`**:
```json
{
  "enabled": true,
  "notify_owner": true,
  "notify_company": false,
  "additional_emails": [
    "manager@company.com",
    "sales@company.com"
  ],
  "subject": "Nuovo contatto da {page_title}",
  "reply_to": "info@company.com"
}
```

#### B. Opzionale - Tabella Log Email

**Per tracking e debug**:
```sql
CREATE TABLE email_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NULL,
  page_id BIGINT UNSIGNED NULL,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent', 'failed', 'queued') DEFAULT 'queued',
  error_message TEXT NULL,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_sent_at (sent_at)
);
```

---

### 3️⃣ **Backend - Modifiche Controller**

#### LeadController.php
```php
public function store(Request $request, Response $response)
{
    // ... validazione esistente ...

    // Create lead
    $lead = Lead::create([...]);

    // 🆕 NUOVO: Invia notifiche email
    if ($page && $page->email_settings['enabled'] ?? false) {
        $emailService = new EmailService();
        $emailService->sendLeadNotification($lead, $page, $page->user);
    }

    return $response->withJson(['message' => 'Lead saved', 'lead' => $lead]);
}
```

---

### 4️⃣ **Frontend - UI Configurazione**

#### A. Nuova Sezione in PageSettings.vue

**Sezione "Notifiche Email"** dopo "Integrazioni":
- Toggle "Abilita notifiche email"
- Checkbox "Notifica proprietario pagina"
- Checkbox "Notifica company manager"
- Campo multi-email "Email aggiuntive" (array)
- Campo "Subject personalizzato" (con placeholder variabili)
- Campo "Reply-to email"
- Pulsante "Invia email di test"

#### B. Componenti Opzionali

**AdminPanel.vue**:
- Sezione "Configurazione Email SMTP"
- Test connessione SMTP
- Visualizza log email recenti

---

### 5️⃣ **Template Email**

#### Contenuto Email Lead Notification

**Oggetto**: `Nuovo contatto da {page_title}`

**Corpo HTML**:
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; }
        .content { background: #f9fafb; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #374151; }
        .value { color: #1f2937; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🎉 Nuovo contatto ricevuto!</h2>
        </div>
        <div class="content">
            <p>Hai ricevuto un nuovo contatto dalla landing page <strong>{page_title}</strong></p>

            <div class="field">
                <div class="label">Nome:</div>
                <div class="value">{lead_name}</div>
            </div>

            <div class="field">
                <div class="label">Email:</div>
                <div class="value">{lead_email}</div>
            </div>

            <div class="field">
                <div class="label">Telefono:</div>
                <div class="value">{lead_phone}</div>
            </div>

            <div class="field">
                <div class="label">Messaggio:</div>
                <div class="value">{lead_message}</div>
            </div>

            <div class="field">
                <div class="label">Data ricezione:</div>
                <div class="value">{received_at}</div>
            </div>

            <p style="margin-top: 30px;">
                <a href="{dashboard_url}" style="background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
                    Visualizza nel Dashboard
                </a>
            </p>
        </div>
    </div>
</body>
</html>
```

**Corpo Testo Semplice** (fallback):
```
Nuovo contatto ricevuto!

Hai ricevuto un nuovo contatto dalla landing page "{page_title}"

Nome: {lead_name}
Email: {lead_email}
Telefono: {lead_phone}
Messaggio: {lead_message}

Data ricezione: {received_at}

Visualizza nel dashboard: {dashboard_url}
```

---

## 🔧 Dipendenze/Librerie

### Composer (Backend)
```bash
composer require phpmailer/phpmailer    # ^6.8
```

### NPM (Frontend)
Nessuna dipendenza aggiuntiva necessaria.

---

## 📝 File da Creare/Modificare

### Backend (PHP)

**Nuovi File**:
- `backend/src/Services/EmailService.php`
- `backend/src/Utils/MailerFactory.php`
- `backend/src/Templates/Email/lead-notification.html`
- `backend/src/Templates/Email/lead-notification.txt`
- `backend/database/migrations/add_email_settings.php`
- `backend/database/migrations/create_email_logs_table.php` (opzionale)

**File da Modificare**:
- `backend/src/Controllers/LeadController.php` - Aggiungere invio email in `store()`
- `backend/src/Models/Page.php` - Aggiungere `email_settings` a `$fillable` e `$casts`
- `backend/.env.example` - Aggiungere variabili MAIL_*
- `backend/.env` - Configurare credenziali SMTP

### Frontend (Vue.js)

**File da Modificare**:
- `frontend/src/components/PageSettings.vue` - Aggiungere sezione "Notifiche Email"
- `frontend/src/stores/pageStore.js` - Aggiungere azione `sendTestEmail()`

**Opzionale**:
- `frontend/src/views/AdminPanel.vue` - Sezione test SMTP e log email

---

## 🎨 Mockup UI

### PageSettings.vue - Sezione Notifiche Email

```
┌─────────────────────────────────────────────────────┐
│  📧 Notifiche Email                                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│  [✓] Abilita notifiche email                       │
│                                                     │
│  Chi deve ricevere le notifiche?                   │
│  [✓] Proprietario pagina (tuo@email.com)          │
│  [ ] Company manager                               │
│                                                     │
│  Email aggiuntive (una per riga):                  │
│  ┌───────────────────────────────────────────────┐ │
│  │ sales@company.com                             │ │
│  │ info@company.com                              │ │
│  └───────────────────────────────────────────────┘ │
│                                                     │
│  Oggetto email:                                    │
│  ┌───────────────────────────────────────────────┐ │
│  │ Nuovo contatto da {page_title}                │ │
│  └───────────────────────────────────────────────┘ │
│  Variabili: {page_title}, {lead_name}, {lead_email}│
│                                                     │
│  Reply-to email:                                   │
│  ┌───────────────────────────────────────────────┐ │
│  │ info@company.com                              │ │
│  └───────────────────────────────────────────────┘ │
│                                                     │
│  [Invia Email di Test]                            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## ⚙️ Configurazione SMTP Consigliata

### Opzioni Provider SMTP

#### 1. Gmail (per test/sviluppo)
```
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=app_specific_password

Note: Richiede "App Password" (non password normale)
```

#### 2. Office 365
```
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your@company.com
MAIL_PASSWORD=your_password
```

#### 3. Server SMTP Dedicato
```
MAIL_HOST=mail.yourserver.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=info@yoursite.com
MAIL_PASSWORD=your_smtp_password
```

#### 4. SendGrid (API - opzionale)
```
MAIL_DRIVER=sendgrid
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxx

Pro: Deliverability alta, analytics
Contro: 100 email/giorno limit free
```

---

## 🚀 Fasi di Implementazione

### Fase 1: Setup Base (2-3h)
1. Installare PHPMailer via Composer
2. Creare EmailService e MailerFactory
3. Aggiungere variabili .env
4. Test connessione SMTP base

### Fase 2: Database & Models (1h)
1. Migration per email_settings in pages
2. Aggiornare Page model
3. Opzionale: Migration per email_logs

### Fase 3: Template Email (1h)
1. Creare template HTML e testo
2. Implementare sostituzione variabili
3. Test rendering template

### Fase 4: Integrazione Backend (2h)
1. Modificare LeadController.store()
2. Implementare logica invio email
3. Gestione errori e logging
4. Test con lead reali

### Fase 5: Frontend UI (3h)
1. Aggiungere sezione in PageSettings.vue
2. Form per configurazione email
3. Validazione frontend
4. Pulsante "Invia test email"

### Fase 6: Testing & Refinement (2h)
1. Test end-to-end
2. Test fallback (email bounce)
3. Test performance
4. Documentazione

**Totale stimato**: 11-14 ore

---

## ⚠️ Considerazioni Importanti

### Sicurezza
- ✅ Mai committare credenziali SMTP nel repository
- ✅ Usare .env per configurazione sensibile
- ✅ Rate limiting su invio email (max X email/minuto)
- ✅ Validare indirizzi email prima di inviare
- ✅ Sanitizzare contenuto lead per prevenire injection

### Performance
- ⚡ Invio email sincrono: ~1-3 secondi per email
- ⚡ Opzione asincrona: Usare job queue (future enhancement)
- ⚡ Non bloccare response al form per invio email
- ⚡ Timeout SMTP: max 10 secondi

### Reliability
- 🔄 Retry automatico su fallimento (max 3 tentativi)
- 📊 Logging errori per debug
- 💾 Salvare lead anche se email fallisce (priorità: non perdere lead!)
- 📧 Fallback: Notifica admin se troppe email falliscono

### Privacy/GDPR
- 🔒 Email contengono dati personali → usare connessione crittografata (TLS/SSL)
- 🔒 Non loggare password o dati sensibili
- 🔒 Retention policy per email_logs (cancellare dopo X giorni)

---

## 🎯 Features Opzionali (Future)

### V2 - Enhancement
- [ ] Queue system asincrono (Laravel Queue, Redis)
- [ ] Email digest giornaliero (riepilogo leads)
- [ ] Template email personalizzabili per pagina
- [ ] A/B testing oggetto email
- [ ] Auto-responder al lead (email di conferma ricezione)
- [ ] Integrazione CRM (Salesforce, HubSpot)
- [ ] Webhook notifiche (Slack, Discord, Teams)
- [ ] Dashboard analytics email (open rate, click rate)

---

## 📚 Risorse Utili

- PHPMailer Docs: https://github.com/PHPMailer/PHPMailer
- SMTP Settings Guide: https://www.gmass.co/smtp-settings
- Email Template Best Practices: https://www.campaignmonitor.com/resources/
- GDPR Email Guidelines: https://gdpr.eu/email-encryption/

---

## ✅ Checklist Implementazione

### Setup Iniziale
- [ ] Installare PHPMailer
- [ ] Configurare .env con credenziali SMTP
- [ ] Test connessione SMTP

### Backend
- [ ] Creare EmailService.php
- [ ] Creare MailerFactory.php
- [ ] Creare template email HTML e testo
- [ ] Migration email_settings
- [ ] Aggiornare Page model
- [ ] Modificare LeadController
- [ ] Testing invio email

### Frontend
- [ ] Aggiungere sezione in PageSettings
- [ ] Form configurazione email
- [ ] Pulsante test email
- [ ] Validazione form

### Testing
- [ ] Test invio email reale
- [ ] Test con SMTP errato (gestione errori)
- [ ] Test email multipli destinatari
- [ ] Test template con tutti i placeholder
- [ ] Test performance (tempo risposta)

### Deployment
- [ ] Documentare setup in README
- [ ] Aggiornare .env.example
- [ ] Istruzioni configurazione SMTP
- [ ] Backup database prima migration

---

**Documento creato**: 2026-01-16
**Ultimo aggiornamento**: 2026-01-16
**Versione**: 1.0
