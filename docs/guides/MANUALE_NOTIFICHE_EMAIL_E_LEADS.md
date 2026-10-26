# Manuale: Notifiche Email e Pagina Leads

> Guida tecnica completa per sviluppatori e amministratori di sistema.

---

## PARTE 1 — Notifiche Email

### Dove si configurano

Le notifiche email si configurano nelle **Impostazioni Pagina** (tab "Notifiche Email"), accessibili dall'editor cliccando l'icona ingranaggio in alto.

### Opzioni disponibili

**Toggle principale: "Abilita Notifiche Email"**
- Se attivo, ogni nuovo lead invia un'email automatica.
- Se disattivo, i lead vengono salvati ma non arriva nessuna notifica.

**Email proprietario**
- Mostrata in sola lettura: è l'email dell'account che ha creato la pagina.
- Riceve sempre la notifica (non si può togliere).

**Email aggiuntive** *(opzionale)*
- Aggiungi altri destinatari separati da virgola.
- Esempio: `commerciale@azienda.it, supporto@azienda.it`
- Ogni email viene validata prima del salvataggio.

---

### Email di cortesia al cliente *(opzionale)*

Se vuoi inviare una conferma automatica al cliente che ha compilato il form:

| Campo | Descrizione |
|---|---|
| **Abilita email di cortesia** | Toggle per attivare |
| **Oggetto** | Oggetto dell'email ricevuta dal cliente |
| **Testo** | Corpo del messaggio. Usa `{name}` per inserire il nome del cliente |
| **Nome mittente** | Nome visualizzato nel client email (opzionale) |
| **Indirizzo mittente** | Email mittente (opzionale, default: indirizzo sistema) |
| **Colori header** | Due colori per il gradient dell'intestazione email |

**Struttura dati salvata (JSON nel database):**
```json
{
  "enabled": true,
  "additional_emails": "info@azienda.it",
  "confirmation_email": {
    "enabled": true,
    "subject": "Abbiamo ricevuto la tua richiesta",
    "body": "Ciao {name}, grazie per averci contattato.",
    "from_name": "Azienda SRL",
    "from_address": "info@azienda.it",
    "header_color": "#667eea",
    "header_color_end": "#764ba2"
  }
}
```

---

### Cosa succede quando arriva un lead

1. Il visitatore compila e invia il form sulla landing page.
2. Il backend salva il lead nel database.
3. Se le notifiche sono abilitate:
   - Invia email di notifica a proprietario + eventuali email aggiuntive.
   - Se configurata, invia email di cortesia al cliente.
4. Il processo email è **non-bloccante**: anche se l'email fallisce, il lead viene comunque salvato.

**Template email notifica** include:
- Nome, Email, Telefono del lead
- Data e ora di ricezione
- Messaggio scritto dal visitatore
- Link "Visualizza tutti i lead" al pannello

---

### Configurazione SMTP (file `.env` backend)

```
MAIL_HOST=vu000816.arubabiz.net
MAIL_PORT=587
MAIL_USERNAME=elpbuilder@vu000816.arubabiz.net
MAIL_ENCRYPTION=tls
```

> Questi parametri sono fissi e si modificano solo sul server.

---

### File coinvolti

| File | Responsabilità |
|---|---|
| `backend/src/Services/EmailService.php` | Invio email con PHPMailer, template HTML |
| `backend/src/Controllers/LeadController.php` | Creazione lead, trigger invio email |
| `backend/src/Controllers/PageController.php` | Endpoint `updateNotificationSettings` |
| `backend/src/Models/Page.php` | Cast JSON `notification_settings` |
| `backend/src/Models/Lead.php` | Cast JSON `metadata` |
| `frontend/src/components/PageSettings.vue` | UI configurazione notifiche |
| `frontend/src/stores/pageStore.js` | Action `updateNotificationSettings` |

---

## PARTE 2 — Pagina Leads

### Chi vede cosa

| Ruolo | Leads visibili |
|---|---|
| **Admin** | Tutti i leads di tutte le pagine |
| **Company** | Solo i leads delle pagine della propria azienda |
| **User** | Solo i leads delle proprie pagine |

### Come accedere

Dalla dashboard, clicca il tab **"Leads"** nel pannello admin/company.

---

### Lista Leads

**Colonne visualizzate:**
- Data di ricezione
- Nome (con badge appuntamento se presente)
- Email (cliccabile → apre client email)
- Telefono (cliccabile → avvia chiamata)
- Pagina sorgente
- Stato pagina (Pubblicata / Non pubblicata)
- Azioni: Dettagli (occhio) | Elimina (cestino)

**Filtri disponibili:**
- **Ricerca testo**: cerca in nome, email, telefono
- **Filtra per pagina**: dropdown con tutte le pagine disponibili
- **Stato**: Pubblicata / Non pubblicata / Tutti
- **Appuntamento**: Con appuntamento / Senza
- **Righe per pagina**: 10, 20, 50, 100

**Export CSV**: bottone verde in alto a destra, esporta i lead filtrati correntemente.

---

### Dettagli Lead (modal)

Cliccando l'occhio si apre il pannello dettagli con:

**Dati principali** — Nome, Email, Telefono, Pagina, Data, Stato

**Messaggio** — testo libero scritto dal visitatore (se presente)

**Dati extra** — eventuali campi custom del form (es. "Città", "Servizio richiesto")

**Appuntamento** — sezione dedicata:
- Se non impostato: form con Data, Ora, Note → "Salva appuntamento"
- Se già impostato: riquadro blu con data/ora/note/autore + pulsante "Modifica"

**Note interne** — timeline cronologica:
- Ogni nota ha testo, timestamp, nome autore
- Textarea + bottone "Aggiungi nota"
- Solo visibili internamente, non al cliente

**Azioni in fondo al modal:**
- "Invia Email" → apre client email
- "Telefona" → avvia chiamata (se telefono presente)

---

### Vista Calendario

Sopra la tabella c'è il toggle **Lista / Calendario**.
- La vista calendario mostra solo i lead che hanno un appuntamento impostato.
- Cliccando su un evento si apre il modal dettagli.

---

### Struttura dati lead nel database

```
leads.metadata (JSON):
{
  "campo_custom": "valore",
  "_appointment": {
    "date": "2026-03-25",
    "time": "14:30",
    "notes": "Riunione presso ufficio",
    "set_by": "Mario Rossi"
  },
  "_notes": [
    { "text": "Interessato al piano premium", "author": "Anna", "timestamp": "..." }
  ]
}
```

---

### Endpoint API di riferimento

| Metodo | URL | Descrizione |
|---|---|---|
| `GET` | `/api/leads` | Carica lista leads |
| `POST` | `/api/leads/{id}/notes` | Aggiunge nota interna |
| `PUT` | `/api/leads/{id}/appointment` | Salva/aggiorna appuntamento |
| `DELETE` | `/api/leads/{id}` | Elimina lead |
| `PUT` | `/api/pages/{id}/notification-settings` | Salva config notifiche |

### File coinvolti

| File | Responsabilità |
|---|---|
| `frontend/src/views/AdminPanel.vue` | Tabella, filtri, modal dettagli (admin) |
| `frontend/src/views/CompanyPanel.vue` | Stessa UI per company manager |
| `frontend/src/components/LeadsCalendar.vue` | Vista calendario appuntamenti |
| `backend/src/Controllers/LeadController.php` | CRUD leads, note, appuntamenti |
