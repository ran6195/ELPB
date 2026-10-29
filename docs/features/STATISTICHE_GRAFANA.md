# Statistiche (scheda del pannello e Grafana)

Numeri e andamenti di lead, visite e conversione per la scheda **Statistiche** del pannello
admin e per le dashboard Grafana (uso interno).
Solo dati aggregati: nessun nome, email o telefono dei lead esce dall'applicazione.

Grafana non conserva i dati: a ogni apertura o aggiornamento della dashboard li chiede
all'API, quindi lo storico è quello del database (lead per sempre, visite 16 mesi).
Basta il piano gratuito di Grafana Cloud (fino a 3 utenti).

## Rotte

Tutte `GET`, base `https://elpb.edysma.com/backend/public/api/stats`.

| Rotta | Restituisce |
|---|---|
| `/summary` | Un oggetto: `leads`, `leads_previous` e `leads_change_pct` (periodo precedente di uguale durata), `views`, `visitors`, `leads_tracked`, `conversion_pct`, `published_pages`, `active_sites`, `outdated_renderers` |
| `/leads` | Serie temporale `[{time, series, series_id, leads}]` |
| `/views` | Serie temporale `[{time, series, series_id, views, visitors}]` (niente `interval=hour`: le visite sono per giorno) |
| `/conversion` | Tabella `[{series, series_id, leads, leads_tracked, views, visitors, conversion_pct}]`, dalla riga con più lead |

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
- `visitors` è la somma dei visitatori unici di ogni giorno;
- conversione = lead tracciati / visitatori. Le visite le contano solo i renderer 2.1 o
  successivi, quindi un lead è **tracciato** solo se la sua pagina, sul suo sito, contava già
  le visite il giorno in cui è arrivato (lead dei renderer vecchi e dei giorni prima
  dell'aggiornamento esclusi). `leads_tracked` dice su quanti lead si basa la percentuale;
  senza visitatori `conversion_pct` è `null`;
- date in ora italiana, come nel database.

Esempio: `/leads?from=1790000000000&to=1792600000000&interval=week&group=site&limit=5`

## Accesso

`StatsAccessMiddleware` accetta due credenziali:

- **chiave di Grafana** in sola lettura, `STATS_API_KEY` nel `.env` del backend, almeno
  32 caratteri, come `Authorization: Bearer <chiave>` o nell'header `X-Stats-Key`: vede tutto;
- **token di un utente del pannello** (login normale) con un ruolo tra `STATS_USER_ROLES`
  (`.env`, separati da virgola, predefinito `admin`; altri ruoli → 403).

Visibilità come per i lead (`StatsController::visiblePages()`): Grafana e admin tutto, gli
altri utenti solo le pagine della propria azienda (anche archiviate), senza azienda nulla.
Il filtro è già attivo e verificato dai test: per aprire le statistiche alle aziende basta
`STATS_USER_ROLES=admin,company,user` e mostrare la scheda nel pannello aziendale.

Senza chiave configurata e senza utente valido le rotte rispondono 503, con chiave errata 401.

Generarla con `php -r 'echo bin2hex(random_bytes(32));'`. Il repository è pubblico:
la chiave va solo nel `.env` del server e in Grafana, mai in git. Per revocarla basta
cambiarla nel `.env` (e aggiornarla in Grafana).

Le richieste di Grafana non finiscono nel registro attività (GET senza utente): Grafana
interroga ogni pochi secondi. Dalla scheda del pannello si registra solo
`GET /api/stats/summary` (una voce per apertura o cambio di periodo); i grafici
(`leads`, `views`, `conversion`) sono in `SKIP_ROUTES`.

## Scheda Statistiche

`frontend/src/components/stats/` (`StatsDashboard.vue`, `StatCard.vue`, `charts.js`), store
`statsStore.js`, scheda "Statistiche" del pannello admin caricata solo all'apertura insieme
ad ECharts (`echarts` + `vue-echarts`, chunk separato in `vite.config.js`). Contiene: periodo
(7/30/90 giorni, 12 mesi) e raggruppamento (pagina, sito, azienda) in una riga; numeri del
periodo (lead con variazione sul periodo precedente, visitatori, conversione sui lead
tracciati, pagine pubblicate) con andamento; lead nel tempo; lead per gruppo (barre impilate,
prime 7 + "Altre", colore legato alla serie); calendario dei lead degli ultimi 12 mesi;
tabella della conversione per gruppo. Palette categoriale a ordine fisso, validata per il
daltonismo; i colori con poco contrasto sono sempre accompagnati da legenda e tabella.

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
| Conversione per pagina (Table) | `/conversion?from=${__from}&to=${__to}&group=page` | colonne `series`, `leads`, `leads_tracked`, `visitors`, `conversion_pct` (unità *Percent*) |

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

`Controllers/StatsController.php`, `Middleware/StatsAccessMiddleware.php`, rotte in
`config/routes.php` (gruppo `/api/stats`), test `tests/Feature/StatsTest.php`.
