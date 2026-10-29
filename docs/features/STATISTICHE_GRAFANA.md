# Statistiche per Grafana

Numeri e andamenti di lead, visite e conversione per le dashboard Grafana (uso interno).
Solo dati aggregati: nessun nome, email o telefono dei lead esce dall'applicazione.

Grafana non conserva i dati: a ogni apertura o aggiornamento della dashboard li chiede
all'API, quindi lo storico è quello del database (lead per sempre, visite 16 mesi).
Basta il piano gratuito di Grafana Cloud (fino a 3 utenti).

## Rotte

Tutte `GET`, base `https://elpb.edysma.com/backend/public/api/stats`.

| Rotta | Restituisce |
|---|---|
| `/summary` | Un oggetto: `leads`, `leads_previous` e `leads_change_pct` (periodo precedente di uguale durata), `views`, `visitors`, `conversion_pct`, `published_pages`, `active_sites`, `outdated_renderers` |
| `/leads` | Serie temporale `[{time, series, series_id, leads}]` |
| `/views` | Serie temporale `[{time, series, series_id, views, visitors}]` (niente `interval=hour`: le visite sono per giorno) |
| `/conversion` | Tabella `[{series, series_id, leads, views, visitors, conversion_pct}]`, dalla riga con più lead |

Parametri:

| Parametro | Valori | Predefinito |
|---|---|---|
| `from`, `to` | epoch in millisecondi (`${__from}`, `${__to}` di Grafana) o `AAAA-MM-GG`; `to` escluso | ultimi 30 giorni |
| `interval` | `hour`, `day`, `week` (da lunedì), `month` | `day` |
| `group` | `none`, `page`, `site`, `company` (`/conversion`: senza `none`) | `none` (`/conversion`: `page`) |
| `limit` | 1-50 serie, le altre sommate in "Altre" | 10 |

Note sui numeri:
- le serie temporali hanno gli zeri per i periodi senza dati e sono in formato lungo
  (una riga per periodo e serie);
- non si contano i lead arrivati dall'anteprima dell'applicazione (sito = `APP_URL`);
  si contano quelli delle pagine archiviate ("– archiviata") ed eliminate ("Pagina eliminata");
- le pagine hanno l'id nel nome ("Offerta (#105)"), così due pagine con lo stesso titolo
  restano serie separate;
- `visitors` è la somma dei visitatori unici di ogni giorno, conversione = lead / visitatori;
- per sito: i renderer precedenti alla 2.1 non comunicano il sito delle visite, che finiscono
  in "Sito sconosciuto"; i loro lead hanno comunque il sito (dal browser), quindi la
  conversione per sito è affidabile solo sui siti con il renderer aggiornato;
- date in ora italiana, come nel database.

Esempio: `/leads?from=1790000000000&to=1792600000000&interval=week&group=site&limit=5`

## Chiave di accesso

Le rotte non usano gli utenti dell'applicazione ma una chiave in sola lettura
(`StatsKeyMiddleware`), `STATS_API_KEY` nel `.env` del backend, almeno 32 caratteri.
Si invia come `Authorization: Bearer <chiave>` o nell'header `X-Stats-Key`.
Senza chiave configurata le rotte rispondono 503, con chiave errata 401.

Generarla con `php -r 'echo bin2hex(random_bytes(32));'`. Il repository è pubblico:
la chiave va solo nel `.env` del server e in Grafana, mai in git. Per revocarla basta
cambiarla nel `.env` (e aggiornarla in Grafana).

Le richieste non finiscono nel registro attività (sono GET senza utente, come le pagine
servite ai renderer): Grafana interroga ogni pochi secondi.

## Configurazione di Grafana Cloud

1. Creare l'account su grafana.com scegliendo una **regione europea** per lo stack.
2. *Connections → Add new connection → Infinity* → installare il plugin.
3. Nuova fonte dati Infinity:
   - *Authentication*: **Bearer Token**, incollare la chiave;
   - *Allowed hosts*: `https://elpb.edysma.com`;
   - salvare e provare.
4. Nella dashboard, variabile `intervallo` (*Custom*): `hour,day,week,month`.

Pannelli suggeriti (*Type*: JSON, *Parser*: Backend, *Source*: URL):

| Pannello | URL (dopo la base) | Impostazioni |
|---|---|---|
| Lead, visitatori, conversione (Stat) | `/summary?from=${__from}&to=${__to}` | un pannello Stat per campo, `leads_change_pct` come variazione |
| Lead nel tempo (Time series o Bar chart) | `/leads?from=${__from}&to=${__to}&interval=${intervallo}` | colonne: `time` *Timestamp*, `leads` *Number* |
| Lead per pagina nel tempo | `/leads?from=${__from}&to=${__to}&interval=${intervallo}&group=page` | colonne `time`, `series`, `leads`; trasformazione **Partition by values** su `series` |
| Visite per sito | `/views?from=${__from}&to=${__to}&interval=day&group=site` | come sopra, campi `views` o `visitors` |
| Conversione per pagina (Table) | `/conversion?from=${__from}&to=${__to}&group=page` | colonne `series`, `leads`, `visitors`, `conversion_pct` (unità *Percent*) |

Aggiornamento automatico della dashboard: 30 s o 1 m sono più che sufficienti (l'intervallo
si sceglie in alto a destra).

## Deploy

1. Generare la chiave e aggiungerla al `.env` del backend sul server via FTP
   (`STATS_API_KEY=...`). `deploy-ftp.sh` non carica mai il `.env`.
2. `./scripts/deploy-ftp.sh` (prova) poi `--execute`.
3. Verifica: `curl -H "Authorization: Bearer <chiave>" https://elpb.edysma.com/backend/public/api/stats/summary`
   risponde con il JSON; senza chiave 401.

Se Aruba non inoltrasse l'header `Authorization` a PHP, in Infinity usare invece
*Authentication: API Key*, chiave `X-Stats-Key`, posizione *Header*.

## File

`Controllers/StatsController.php`, `Middleware/StatsKeyMiddleware.php`, rotte in
`config/routes.php` (gruppo `/api/stats`), test `tests/Feature/StatsTest.php`.
