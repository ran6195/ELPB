# Storico della pubblicazione

Base della fatturazione alle agenzie **per pagina pubblicata** (decisione del management,
novembre 2026). Registra per ogni pagina i periodi in cui è stata online e per quale azienda,
così a fine mese si sa quali pagine erano pubblicate e per quanti giorni.

Lo storico è solo il primo pezzo: listino, riepilogo mensile e chiusura del mese si costruiscono
dopo, quando il management avrà deciso le regole (vedi "Decisioni aperte").

## Che cosa si registra

Tabella `page_publications`: un periodo per ogni tratto online di una pagina per una stessa azienda.

| Colonna | Significato |
|---|---|
| `page_id`, `company_id` | Pagina e azienda a cui va addebitata in quel periodo |
| `started_at`, `ended_at` | Inizio e fine (ora italiana); `ended_at` NULL = ancora online |
| `start_source` | Da dove viene l'inizio: `live` (registrato al momento), `audit_log`, `first_view`, `first_hit`, `backfill` |
| `end_reason` | `unpublished` (ritirata), `archived`, `deleted`, `reassigned` (passata a un'altra azienda), `reconciled` (chiusa dal riallineamento) |

**Online** = pubblicata e non archiviata: sono le sole pagine che `/api/page/{slug}` dà ai renderer.
Nessuna FK verso `pages`: lo storico resta anche dopo l'eliminazione definitiva.

## Come si aggiorna

Agganci nel modello `Page` (`created`, `updated` su `is_published`/`company_id`, `deleted`,
`restored`) → `PublicationHistory::sync()`, nella stessa transazione del salvataggio. Valgono per
qualunque rotta o script che usi Eloquent: pubblicazione e ritiro, archiviazione, ripristino,
eliminazione definitiva, "Assegna a…" verso un'altra azienda. Lo stesso giorno possono esserci
più periodi (pubblica, ritira, ripubblica): la regola di fatturazione deciderà come contarli.

Un errore dello storico non blocca il salvataggio della pagina: viene scritto nel log PHP e il
riallineamento lo recupera. Gli aggiornamenti di massa (`Page::where()->update()`) non passano dal
modello: oggi non ce ne sono sulle colonne che contano.

## Scheda Pubblicazioni (pannello admin)

`GET /api/admin/publications` (solo admin, `PublicationController`) restituisce tutte le pagine con
uno storico, comprese le archiviate e le eliminate definitivamente, con i periodi e i nomi delle
aziende. La scheda "Pubblicazioni" (`components/publications/PublicationHistory.vue`) mostra pagina
per pagina i periodi (azienda, dal, al o "in corso", giorni, inizio ricostruito, motivo della fine),
con filtri per mese, azienda, stato e ricerca, e l'export CSV (una riga per periodo). Con un mese
scelto restano solo i periodi che lo toccano e i giorni sono quelli nel mese: un giorno conta se la
pagina è stata online anche solo in parte, i periodi sovrapposti della stessa pagina non si sommano.

## Pagine pubblicate prima dello storico

`scripts/backfill-publication-history.php` (prova senza argomenti, poi `--execute`; rieseguibile)
apre il periodo delle pagine online che non lo hanno, con l'inizio stimato da
`PublicationHistory::inferStart()`:

1. l'ultimo evento del registro attività dopo il quale la pagina è online per l'azienda attuale
   (`page.published`, `page.restored`, `page.reassigned`): dato esatto, disponibile dal
   rilascio del registro (fine ottobre 2026);
2. altrimenti il **primo segno che un sito l'ha mostrata**: il primo giorno con visite
   (`page_views_daily`) o la prima richiesta di un renderer (`renderer_hits`, bot esclusi), il più
   vecchio dei due. Entrambi si registrano solo per pagine pubblicate, ma esistono solo da fine
   ottobre 2026: per le pagine più vecchie è la data del rilascio del tracciamento, non quella
   reale di pubblicazione;
3. altrimenti il momento del recupero.

Lo stesso script chiude i periodi rimasti aperti di pagine non più online o passate a un'altra
azienda. Il dettaglio va in `storage/logs/backfill-publication-history.txt`.

## Deploy (Aruba/Plesk)

1. Backup del database, poi `backend/database/sql/storico_pubblicazione.sql` in phpMyAdmin.
2. Caricare i file del backend (`scripts/deploy-ftp.sh`).
3. Attività pianificata Plesk: `scripts/backfill-publication-history.php` senza argomenti,
   controllare l'output, poi con `--execute`.

Rollback: `DROP TABLE page_publications;` (gli agganci non bloccano i salvataggi se la tabella manca).

## Decisioni aperte (prima della fatturazione)

1. Quando una pagina conta nel mese: almeno un giorno online (consigliato), online a fine mese,
   oppure in proporzione ai giorni.
2. Prezzo: unico o a scaglioni, con o senza canone base, uguale per tutte le agenzie o per agenzia.
3. Pagina passata a un'altra azienda nel mese: la paga chi la aveva in quei giorni (lo storico lo
   permette già) o chi la ha a fine mese.
4. Pagine senza azienda (es. dell'admin): escluse?
5. Primo mese fatturabile: il primo mese intero dopo il rilascio dello storico.

## File

`backend/src/Services/PublicationHistory.php`, `backend/src/Models/Page.php` (agganci),
`backend/database/migrations/create_page_publications_table.php`,
`backend/database/sql/storico_pubblicazione.sql`, `backend/scripts/backfill-publication-history.php`,
test `backend/tests/Feature/PublicationHistoryTest.php`.
