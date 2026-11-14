# Come funziona l'app: pagine, contatti, visite e statistiche

Questo documento spiega come funziona l'applicazione, quali informazioni raccoglie quando un
visitatore apre una landing page e quali statistiche se ne ricavano. Le sezioni dalla 1 alla 7
si leggono senza competenze tecniche. Alla fine c'è un'appendice tecnica con tabelle, rotte e
file del codice.

---

## 1. Come funziona l'app

L'applicazione ha tre parti:

| Parte | Dove si trova | A cosa serve |
|---|---|---|
| **Editor** | `elpb.edysma.com` (con login) | Creare le landing page a blocchi, impostarle, pubblicarle, leggere i contatti e le statistiche |
| **Backend** | `elpb.edysma.com/backend` | Conserva pagine, contatti, domini, visite e statistiche, e li fornisce ai siti tramite API |
| **Renderer** | Sul sito del cliente (es. `cliente.it/lp/`) | Piccolo programma PHP (o componente Joomla) che scarica la pagina dal backend e la mostra ai visitatori |

La pagina vive in un solo posto, il backend. Il renderer sul sito del cliente la scarica a ogni
apertura, quindi una modifica fatta nell'editor è visibile subito su tutti i siti che mostrano
quella pagina, senza ricaricare file.

```
 Visitatore ──► cliente.it/lp/offerta ──► renderer (PHP sul sito del cliente)
                                              │
                                              │ chiede la pagina "offerta"
                                              ▼
                                       Backend ELPB  ──► database
```

### Utenti e visibilità

- **Admin**: vede e gestisce tutto (pagine, aziende, utenti, statistiche, registro attività).
- **Responsabile aziendale**: vede le pagine, i contatti e i domini della propria azienda.
- **Utente**: vede le proprie pagine.

### Domini

Ogni pagina si associa ai siti (domini) su cui deve comparire. Per pubblicare una pagina serve
almeno un dominio associato. Il dominio serve a capire **da dove** arrivano visite e contatti e
a segnalare quando una pagina compare su un sito non previsto.

---

## 2. Quali informazioni raccogliamo

Quando un visitatore apre una landing page succedono tre cose distinte:

| Evento | Chi lo invia | Cosa registra | A cosa serve |
|---|---|---|---|
| **Richiesta della pagina** | Il server del sito (renderer) | Sito, renderer e versione, IP **del server** del sito, numero di richieste | Sapere dove è installata ogni pagina e quali siti sono da aggiornare (scheda Renderer) |
| **Visita** | Il browser del visitatore (segnale anonimo) | Pagina, sito, giorno, +1 visita, +1 visitatore se è nuovo nella giornata | Visite, visitatori e conversione |
| **Contatto (lead)** | Il browser, quando il visitatore invia il form | I dati del form e il sito da cui è stato inviato | Elenco dei contatti, notifiche email, statistiche dei lead |

Non usiamo **cookie** e non salviamo nulla nel browser del visitatore. Non usiamo servizi
esterni di analisi: tutti i dati restano nel nostro database. L'eventuale Google Tag Manager
configurato su una pagina è separato e risponde alle regole del cliente.

---

## 3. Visite e visitatori

### Come si conta una visita

Quando la pagina si apre, il browser invia al backend un piccolo segnale ("beacon")
con due sole informazioni: **quale pagina** e **su quale sito**. Ogni segnale vale una **visita**.

### Come si conta un visitatore senza cookie

Per sapere se due visite vengono dalla stessa persona, il backend calcola un'impronta
(hash) combinando:

- un codice casuale **del giorno** (il "sale"), creato al primo accesso della giornata;
- l'indirizzo IP e il browser (user agent) del visitatore;
- la pagina e il sito.

Se l'impronta è nuova nella giornata, il visitatore viene contato. IP e user agent non vengono
salvati: si salva solo l'impronta, da cui non si può risalire a chi l'ha prodotta.

**Il giorno dopo** il codice del giorno precedente e tutte le impronte vengono cancellati.
Da quel momento non resta nulla che permetta di riconoscere un visitatore: restano solo i
totali (visite e visitatori per pagina, sito e giorno).

Conseguenze pratiche:

- la stessa persona che torna il giorno dopo conta come **nuovo visitatore**;
- i "visitatori" di un periodo sono la **somma dei visitatori di ogni giorno** (non le persone
  diverse in tutto il periodo);
- due persone dietro la stessa rete con lo stesso browser identico possono contare come una;
- la stessa persona da telefono e computer conta come due.

### Che cosa non viene contato

- Bot, crawler e strumenti automatici (riconosciuti dallo user agent);
- browser comandati da programmi (`navigator.webdriver`);
- l'anteprima dell'editor;
- le pagine non pubblicate;
- la pagina di ringraziamento (thank-you) dopo l'invio del form;
- le pagine con un renderer **precedente alla versione 2.1**, che non invia il segnale.

### Conservazione

I totali giornalieri si conservano **16 mesi** (come Google Analytics 4), poi vengono
cancellati automaticamente.

---

## 4. Contatti (lead)

Quando il visitatore invia il form, il backend salva:

- nome, email, telefono, messaggio e gli eventuali campi aggiuntivi del form avanzato;
- la pagina di provenienza;
- il **sito da cui è stato inviato**, ricavato dagli header `Origin`/`Referer` che il browser
  invia sempre. Funziona anche con i renderer vecchi, perché non dipende dal renderer.

Inoltre:

- se è attivo reCAPTCHA, il contatto viene salvato solo se la verifica riesce;
- se lo stesso modulo viene reinviato identico entro 10 minuti (succede su Android tornando
  indietro dalla thank-you), non si crea un secondo contatto e non parte una seconda email;
- parte la notifica email al proprietario della pagina e agli indirizzi configurati.

### Sito di provenienza del contatto

Nell'elenco dei lead ogni contatto ha uno di questi stati:

| Stato | Significato |
|---|---|
| *Dominio associato* | Arrivato da un sito associato alla pagina |
| *Non previsto* | Arrivato da un sito che **non** è tra quelli associati alla pagina |
| *Pagina senza domini associati* | La pagina non ha domini, quindi non si può confrontare |
| *Anteprima* | Inviato dall'anteprima dell'editor (escluso dalle statistiche) |
| *Stimato* | Contatto vecchio: il sito è stato stimato dopo (la pagina aveva un solo dominio), dato non certo |
| *Origine non registrata* | Contatto arrivato prima che il sito venisse registrato |

I dati personali dei contatti restano solo nella tabella dei lead: non finiscono nel registro
attività e non escono nelle statistiche, che sono sempre numeri aggregati.

---

## 5. Registro dei renderer (scheda "Renderer")

Ogni volta che un sito chiede una pagina al backend, il backend annota **chi** l'ha chiesta.
I renderer dalla versione 2.1 dichiarano:

- il **sito** (`X-ELPB-Host`) e l'indirizzo della pagina (`X-ELPB-Url`);
- il **tipo e la versione** del renderer (`X-ELPB-Renderer`, es. `standalone/2.1.1`).

I renderer precedenti non dichiarano nulla: si riconoscono solo dall'IP del server e dal tipo
di richiesta.

La scheda Renderer del pannello admin mostra una riga per pagina e sito, con:

| Colonna | Significato |
|---|---|
| Domini associati | I siti previsti per la pagina |
| Sito che la mostra | Il sito che ha davvero chiesto la pagina (vuoto per i renderer vecchi) |
| Sito associato | Se quel sito è tra quelli previsti |
| **Contatti da** | Solo per i renderer vecchi senza sito: i siti da cui sono arrivati i contatti di quella pagina, come indizio di dove è installata. Con i renderer aggiornati è sempre vuota, perché il sito è già noto |
| Renderer / Da aggiornare | Tipo e versione; "da aggiornare" se è precedente all'ultima distribuita (oggi Standalone 2.1.1, Joomla 2.1.0) |
| Server | IP del server del sito |
| Richieste / Ultimo accesso | Quante volte il sito ha chiesto la pagina e quando l'ultima |

Note:

- "Richieste" **non sono visite**: sono le chiamate del sito al backend. Contano anche i bot
  che aprono la pagina sul sito del cliente, perché il renderer chiede comunque la pagina al
  backend. Per il pubblico reale valgono visite e visitatori (sezione 3).
- Le richieste di bot e assistenti IA che chiamano direttamente il backend (Google, Bing, Meta,
  Anthropic…) sono riconosciute e si possono nascondere.
- Dopo l'aggiornamento di un renderer la riga vecchia e quella nuova vengono unite, se si
  riesce a capire con certezza che si tratta della stessa installazione (stesso IP e un solo
  sito aggiornato).
- La casella "Diretta" aggiorna la tabella ogni 4 secondi; "Esporta CSV" scarica le righe mostrate.

---

## 6. Statistiche

Le statistiche si vedono nella scheda **Statistiche** del pannello admin e, per uso interno,
in **Grafana**. Sono sempre dati aggregati, mai dati personali.

### Indicatori

| Indicatore | Come si calcola |
|---|---|
| **Lead** | Contatti ricevuti nel periodo, con la variazione rispetto al periodo precedente di pari durata. Esclusi quelli dall'anteprima, inclusi quelli delle pagine archiviate |
| **Visite** | Somma dei segnali di visita del periodo |
| **Visitatori** | Somma dei visitatori unici di ogni giorno (sezione 3) |
| **Conversione** | Lead **tracciati** ÷ visitatori |
| **Pagine pubblicate** | Pagine attualmente pubblicate |
| **Siti attivi** | Siti che hanno chiesto almeno una pagina nel periodo (esclusi bot e anteprima) |
| **Renderer da aggiornare** | Installazioni con una versione precedente all'ultima |

### Perché la conversione usa solo i lead "tracciati"

Le visite si contano solo con i renderer 2.1 o successivi, mentre i contatti arrivano da tutti
i siti. Se si dividessero tutti i lead per i visitatori, i siti con renderer vecchio darebbero
lead senza visite e la percentuale salirebbe a valori senza senso (in produzione era arrivata a
2856%).

Per questo un lead conta nella conversione solo se **quella pagina, su quel sito, contava già
le visite il giorno in cui è arrivato**. Il numero di lead tracciati è sempre mostrato accanto
alla percentuale, per sapere su quanti contatti si basa. Senza visitatori la conversione non
si calcola.

### Grafici della scheda Statistiche

- Periodo: 7, 30, 90 giorni o 12 mesi.
- Raggruppamento: per pagina, per sito o per azienda.
- Riquadri con gli indicatori e il loro andamento.
- Lead nel tempo.
- Lead per gruppo (barre impilate, prime 7 serie + "Altre").
- Calendario dei lead degli ultimi 12 mesi.
- Tabella della conversione per gruppo, ordinata dalla conversione più alta.

Oggi la scheda è visibile solo agli admin. Il filtro per azienda è già pronto: si può aprire ai
responsabili aziendali, che vedrebbero solo le proprie pagine.

### Grafana

Grafana Cloud legge gli stessi numeri tramite API con una chiave in sola lettura e non conserva
dati propri: lo storico è quello del database (lead per sempre, visite 16 mesi). Dettagli in
`docs/features/STATISTICHE_GRAFANA.md`.

---

## 7. Limiti da conoscere

- **Siti con renderer precedente alla 2.1**: niente visite e niente sito nella scheda
  Renderer. I contatti arrivano comunque e indicano il sito. Aggiornando il renderer le visite
  si contano da quel giorno in poi, non per il passato.
- **Visitatori**: sono una stima per giorno, senza cookie (vedi sezione 3). Possono differire
  da Google Analytics, che usa cookie e altri criteri.
- **Ad-blocker e browser molto restrittivi** possono bloccare il segnale di visita: in quel
  caso la visita non si conta, mentre il contatto sì. La conversione può quindi risultare un
  po' più alta del reale.
- **Richieste della scheda Renderer**: misurano l'attività del sito, non il pubblico.

---

## Appendice tecnica

### Tabelle

| Tabella | Contenuto | Conservazione |
|---|---|---|
| `leads` | Dati del form, `metadata` (campi extra), `origin_host`, `domain_id`, `origin_inferred` | Senza scadenza |
| `renderer_hits` | Una riga per pagina + sito + renderer + IP: `version`, `user_agent`, `last_url`, `hits`, `first_seen_at`, `last_seen_at` | Senza scadenza |
| `page_views_daily` | Una riga per pagina + sito + giorno: `views`, `visitors` | 16 mesi |
| `page_view_visitors` | Impronte (hash) dei visitatori del giorno | Solo il giorno corrente |
| `tracking_salts` | Sale casuale del giorno | Solo il giorno corrente |
| `domains`, `domain_page` | Anagrafica domini per azienda e associazione alle pagine | Senza scadenza |

La pulizia (sali, impronte e aggregati oltre 16 mesi) avviene alla prima visita di ogni nuovo
giorno, senza attività pianificate.

### Flusso

1. Il renderer chiama `GET /api/page/{slug}` con gli header `X-ELPB-Host`, `X-ELPB-Url`,
   `X-ELPB-Renderer` → `TrackingService::recordRendererHit()` scrive in `renderer_hits`.
   Le richieste dal browser (anteprima, apertura a mano) vengono ignorate.
2. La pagina mostrata contiene lo script del beacon (`elpbViewBeacon()` in
   `standalone-renderer/config.php`, equivalente nel componente Joomla), che invia
   `POST /api/track/view { page_id, host }` come `text/plain` con `navigator.sendBeacon`
   → `TrackingService::recordView()`. Risposta sempre 204.
3. Il form invia `POST /api/leads` → `LeadController::store()`: reCAPTCHA, controllo dei
   doppi (`recentDuplicate`), sito da `Origin`/`Referer` (`originHost`), dominio
   (`matchDomain`), salvataggio, notifica email.

Impronta del visitatore: `sha256(sale_del_giorno | IP | user agent | page_id | host)`.

Bot: user agent (`BOT_PATTERN`, `AUTOMATED_AGENT`) e reti note (`BOT_NETWORKS`) in
`TrackingService`. Per le righe dei renderer la valutazione avviene alla lettura, quindi
vale anche per i dati già registrati.

### Rotte

| Rotta | Accesso | Uso |
|---|---|---|
| `POST /api/track/view` | Pubblica | Beacon delle visite |
| `GET /api/pages/{id}/renderers` | Utente che vede la pagina | "Dove viene mostrata" e visite degli ultimi 30 giorni in Impostazioni pagina |
| `GET /api/renderers`, `/api/renderers/new` | Utenti autenticati (visibilità delle pagine) | Scheda Renderer e diretta |
| `GET /api/stats/summary\|leads\|views\|conversion` | Chiave di Grafana o utente con ruolo in `STATS_USER_ROLES` | Scheda Statistiche e Grafana |

### File

- Backend: `src/Services/TrackingService.php`, `src/Controllers/TrackController.php`,
  `src/Controllers/StatsController.php`, `src/Controllers/LeadController.php`,
  `src/Models/Lead.php` (`originStatus()`), `src/Utils/Hostname.php`.
- Renderer: `standalone-renderer/config.php` (header e beacon), `page.php`,
  `joomla-component/com_landingpages/site/helpers/landingpages.php`.
- Frontend: `components/domains/RendererOverview.vue`, `components/stats/*`,
  `utils/leadOrigin.js`.
- Documenti collegati: `docs/features/PIANO_DOMINI_LP.md`,
  `docs/features/STATISTICHE_GRAFANA.md`, `docs/features/REGISTRO_ATTIVITA.md`.
