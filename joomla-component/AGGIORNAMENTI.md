# 🎉 Aggiornamenti Componente Joomla - Landing Pages

## Versione Aggiornata - 19 Dicembre 2025

### ✨ Nuove Funzionalità

#### 1. **Blocco Map (Google Maps)**
Il componente ora supporta completamente il blocco mappa di Google Maps con tutte le funzionalità:

**Caratteristiche:**
- Integrazione Google Maps tramite iframe
- Titolo e descrizione opzionali
- Altezza personalizzabile
- Informazioni di contatto sotto la mappa:
  - Indirizzo con icona
  - Telefono con icona
  - Email con icona
- Supporto angoli arrotondati
- Placeholder quando l'URL non è impostato

**Uso:**
Il blocco Map viene automaticamente renderizzato quando presente in una landing page creata dall'applicazione principale.

#### 2. **Social Media Buttons nel Header**
Il blocco Header è stato aggiornato per supportare i pulsanti social media:

**Piattaforme Supportate:**
- Facebook
- Instagram
- X (Twitter)

**Personalizzazione:**
- Background color personalizzabile
- Colore icone personalizzabile
- Padding personalizzabile
- Bordi personalizzabili (larghezza, colore, raggio)
- Effetto hover con transizione

**Design:**
- Icone SVG ottimizzate
- Responsive design
- Posizionamento a destra dell'header
- Link aperti in nuova finestra

### 🐛 Bug Fix

- **Footer Block**: Corretto errore di inizializzazione della variabile `$html` che causava warning PHP

### 📦 Installazione Aggiornamento

#### Metodo 1: Nuova Installazione
1. Accedi al pannello amministratore di Joomla
2. Vai su **Extensions > Manage > Install**
3. Carica il file `com_landingpages.zip` aggiornato
4. Clicca su "Upload & Install"

#### Metodo 2: Aggiornamento da Versione Precedente
1. **Disinstalla** la versione precedente:
   - Extensions > Manage > Manage
   - Cerca "Landing Pages"
   - Seleziona e clicca "Uninstall"
2. **Installa** la nuova versione seguendo il Metodo 1

**IMPORTANTE**: La disinstallazione del componente NON elimina i dati delle landing pages. Le pagine e i blocchi rimangono nel database.

### 🔄 Compatibilità

Il componente è compatibile con:
- Joomla 3.x e 4.x
- PHP 7.4+
- Tutte le landing pages create con la versione principale dell'applicazione

### 📋 Blocchi Supportati

Il componente Joomla ora supporta tutti e **15 blocchi** disponibili:

1. ✅ Header (con social media)
2. ✅ Hero
3. ✅ Text
4. ✅ Two Column Text-Image
5. ✅ Two Column Image-Text
6. ✅ Features
7. ✅ Services Grid
8. ✅ CTA (Call to Action)
9. ✅ Form
10. ✅ Video
11. ✅ Video Info
12. ✅ Image Slide
13. ✅ Slider
14. ✅ **Map** (NUOVO!)
15. ✅ Footer

### 🎨 Come Usare le Nuove Funzionalità

#### Blocco Map
1. Crea una landing page dall'applicazione principale
2. Aggiungi un blocco "Map"
3. Configura:
   - Inserisci l'URL di Google Maps (iframe embed)
   - Aggiungi titolo e descrizione (opzionali)
   - Imposta l'altezza della mappa
   - Attiva "Mostra informazioni contatto"
   - Inserisci indirizzo, telefono, email
4. Pubblica la pagina
5. Visualizza in Joomla tramite il componente

#### Social Media nel Header
1. Crea/modifica una landing page
2. Aggiungi o modifica il blocco "Header"
3. Nel pannello proprietà blocco, espandi "Social Media"
4. Inserisci gli URL per:
   - Facebook (es: https://facebook.com/tuapagina)
   - Instagram (es: https://instagram.com/tuoaccount)
   - Twitter/X (es: https://twitter.com/tuoaccount)
5. Personalizza lo stile dei pulsanti (colori, bordi, padding)
6. Salva e pubblica

### 🔗 Collegamenti Utili

- **Documentazione Principale**: Vedi `../CLAUDE.md`
- **Changelog Completo**: Vedi `CHANGELOG.md`
- **Guida Quick Start**: Vedi `QUICKSTART.md`
- **README Componente**: Vedi `README.md`

### 🆘 Supporto

Per problemi o domande:
1. Consulta la documentazione principale del progetto
2. Verifica che l'API backend sia configurata correttamente
3. Controlla i log di Joomla in caso di errori

### 📊 Testing

Dopo l'aggiornamento, testa:
- [ ] Rendering corretto del blocco Map
- [ ] Funzionamento dei pulsanti social nell'Header
- [ ] Tutti gli altri blocchi renderizzano correttamente
- [ ] Form funziona e invia lead
- [ ] Slider funziona con Swiper.js

---

**Versione Package**: `com_landingpages.zip` (Ricompilato il 19 Dicembre 2025)
