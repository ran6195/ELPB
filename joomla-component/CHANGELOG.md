# Changelog - Landing Pages Joomla Component

## [Unreleased] - 2025-12-19

### Added
- **Map Block**: Aggiunto supporto completo per il blocco Google Maps con:
  - Integrazione iframe Google Maps
  - Titolo e descrizione opzionali
  - Informazioni di contatto (indirizzo, telefono, email) con icone
  - Altezza personalizzabile
  - Supporto angoli arrotondati

### Updated
- **Header Block**: Aggiornato con supporto per pulsanti social media:
  - Facebook
  - Instagram
  - X (Twitter)
  - Stile pulsanti personalizzabile (colore, padding, bordi, background)
  - Responsive e con hover effect

### Fixed
- Corretto bug nel rendering del Footer block (inizializzazione variabile `$html`)

### Technical Changes
- Aggiunto metodo `renderMap()` in `BlockRenderer.php`
- Aggiornato metodo `renderHeader()` per gestire social links
- Ricompilato package component `com_landingpages.zip`

## Previous Versions
- Versione base con 14 blocchi (Header, Hero, Text, Features, Services Grid, CTA, Two Column layouts, Video, Video Info, Form, Slider, Image Slide, Footer)
