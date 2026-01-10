# Fix Font Family per Slider - Componente Joomla

## File da aggiornare

1. blockrenderer.php
   → Carica in: /components/com_landingpages/helpers/blockrenderer.php
   
2. default.php
   → Carica in: /components/com_landingpages/views/page/tmpl/default.php

## Cosa è stato fixato

- Il font family della pagina ora viene applicato correttamente a tutti i testi dello slider
- Aggiunto metodo setFontFamily() e getFontFamilyStyle() al BlockRenderer
- Il template ora imposta il font family nel renderer prima di renderizzare i blocchi
- I titoli e descrizioni delle slide ora ereditano il font corretto

## Come caricare

Via FTP/SFTP:
1. Connettiti al server
2. Naviga in /components/com_landingpages/
3. Sostituisci i file nelle rispettive posizioni
4. Verifica che i permessi siano 644

Via pannello di controllo:
1. File Manager
2. Naviga in /components/com_landingpages/
3. Carica i file sostituendo quelli esistenti

## Test

Dopo l'upload:
1. Apri una landing page con uno slider
2. Verifica che il font degli slider corrisponda al font della pagina
3. Controlla nella console che non ci siano errori
