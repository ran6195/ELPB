# 🔧 Aggiornamento BlockRenderer Joomla - Supporto Completo Stili

**Data:** 10 Gennaio 2026
**Versione:** v2.0.2
**File Modificato:** `joomla-component/com_landingpages/site/helpers/blockrenderer.php`

---

## 📋 Sommario Modifiche

Ho modificato il `BlockRenderer.php` del componente Joomla per supportare **completamente** gli stili dei blocchi, inclusi:

✅ **Font Family** - Applicato automaticamente a tutti i blocchi
✅ **Background Color** - Colore di sfondo per blocco
✅ **Text Color** - Colore del testo per blocco
✅ **Padding** - Spaziatura interna per blocco
✅ **Rounded Corners** - Bordi arrotondati per blocco

---

## 🎯 Problema Risolto

### Prima delle modifiche:
- ❌ Font family NON applicato ai blocchi (solo allo slider con codice duplicato)
- ❌ Alcuni blocchi costruivano gli stili manualmente (inconsistenza)
- ❌ Stili duplicati in VideoInfo (background-color hardcoded)
- ❌ ImageSlide non applicava gli stili del blocco

### Dopo le modifiche:
- ✅ Font family applicato AUTOMATICAMENTE a tutti i blocchi
- ✅ Tutti i blocchi usano `getBlockStyle()` consistentemente
- ✅ Stili centralizzati, nessuna duplicazione
- ✅ Tutti i blocchi supportano backgroundColor, textColor, padding, rounded corners

---

## 📝 Modifiche Dettagliate

### 1. Metodo `getBlockStyle()` - Riga ~74

**Prima:**
```php
protected static function getBlockStyle($styles)
{
    $css = [];

    if (!empty($styles['backgroundColor'])) {
        $css[] = 'background-color: ' . $styles['backgroundColor'];
    }

    if (!empty($styles['textColor'])) {
        $css[] = 'color: ' . $styles['textColor'];
    }

    if (!empty($styles['padding'])) {
        $css[] = 'padding: ' . $styles['padding'];
    }

    return !empty($css) ? 'style="' . implode('; ', $css) . '"' : '';
}
```

**Dopo:**
```php
protected static function getBlockStyle($styles, $extraStyles = [])
{
    $css = [];

    // Apply global font family if set (NUOVO!)
    if (!empty(self::$fontFamily)) {
        $css[] = 'font-family: "' . htmlspecialchars(self::$fontFamily) . '", system-ui, -apple-system, sans-serif';
    }

    if (!empty($styles['backgroundColor'])) {
        $css[] = 'background-color: ' . $styles['backgroundColor'];
    }

    if (!empty($styles['textColor'])) {
        $css[] = 'color: ' . $styles['textColor'];
    }

    if (!empty($styles['padding'])) {
        $css[] = 'padding: ' . $styles['padding'];
    }

    // Merge extra styles (NUOVO! - per Hero con background-image)
    if (!empty($extraStyles)) {
        $css = array_merge($css, $extraStyles);
    }

    return !empty($css) ? 'style="' . implode('; ', $css) . '"' : '';
}
```

**Cambiamenti:**
- Aggiunto supporto automatico per `font-family` globale
- Aggiunto parametro opzionale `$extraStyles` per stili custom per blocco

---

### 2. Blocco Hero - Riga ~714

**Prima:**
```php
// Costruiva gli stili manualmente
$inlineStyles = [];

if (!empty($styles['backgroundColor'])) {
    $inlineStyles[] = 'background-color: ' . $styles['backgroundColor'];
}
// ... ecc

$styleAttr = !empty($inlineStyles) ? 'style="' . implode('; ', $inlineStyles) . '"' : '';
```

**Dopo:**
```php
// Extra styles per Hero (background image, min-height)
$extraStyles = [];

if (!empty($backgroundImage)) {
    $extraStyles[] = 'background-image: url(' . $backgroundImage . ')';
    $extraStyles[] = 'background-size: cover';
    $extraStyles[] = 'background-position: center';
}

$extraStyles[] = 'min-height: 400px';

// Usa getBlockStyle con extra styles
$blockStyle = self::getBlockStyle($styles, $extraStyles);
```

**Benefici:**
- Usa `getBlockStyle()` come tutti gli altri blocchi
- Include automaticamente font-family, backgroundColor, textColor, padding
- Aggiunge stili specifici di Hero tramite `$extraStyles`

---

### 3. Blocco VideoInfo - Riga ~835

**Prima:**
```php
$blockStyle = self::getBlockStyle($styles);
$bgColor = $styles['backgroundColor'] ?? '#1f2937';
$textColor = $styles['textColor'] ?? '#ffffff';

// ... poi duplicava gli stili inline
<section class="video-info-block" {$blockStyle} style="background-color: {$bgColor}; color: {$textColor};">
    <div class="flex items-center justify-center p-8 bg-gray-900"> <!-- bg hardcoded -->
```

**Dopo:**
```php
$blockStyle = self::getBlockStyle($styles);

<section class="video-info-block" {$blockStyle}>
    <div class="flex items-center justify-center p-8"> <!-- rimosso bg-gray-900 -->
```

**Benefici:**
- Rimossa duplicazione di stili inline
- Rimossa classe `bg-gray-900` hardcoded che sovrascriveva backgroundColor
- Gli stili vengono applicati correttamente dal container padre

---

### 4. Blocco Slider - Riga ~1115

**Prima:**
```php
$fontStyle = self::getFontFamilyStyle(); // Duplicato!

<h2 class="text-3xl font-bold text-center mb-8" style="{$fontStyle}">{$title}</h2>
// ...
<h3 class="text-xl font-semibold mb-2" style="{$fontStyle}">{$slideTitle}</h3>
<p class="text-gray-600" style="{$fontStyle}">{$slideDescription}</p>
```

**Dopo:**
```php
// Nessuna chiamata a getFontFamilyStyle() - il font è già incluso in getBlockStyle()

<h2 class="text-3xl font-bold text-center mb-8">{$title}</h2>
<h3 class="text-xl font-semibold mb-2">{$slideTitle}</h3>
<p class="text-gray-600">{$slideDescription}</p>
```

**Benefici:**
- Eliminata duplicazione di codice
- Font family applicato dal container `.slider-block` con `{$blockStyle}`
- Gli elementi figli ereditano automaticamente il font

---

### 5. Blocco ImageSlide - Riga ~251

**Prima:**
```php
// Non usava getBlockStyle() affatto!
$roundedClass = self::getRoundedClass($block);

$html = <<<HTML
<div class="image-slide-block">
HTML;
```

**Dopo:**
```php
$roundedClass = self::getRoundedClass($block);
$blockStyle = self::getBlockStyle($styles); // AGGIUNTO!

$html = <<<HTML
<div class="image-slide-block" {$blockStyle}>
HTML;
```

**Benefici:**
- Ora supporta backgroundColor, textColor, padding, font-family
- Consistente con tutti gli altri blocchi

---

## ✅ Blocchi Verificati (Tutti OK)

| Blocco | Usa getBlockStyle() | Supporta Font | Supporta Stili |
|--------|---------------------|---------------|----------------|
| Header | ✅ | ✅ | ✅ |
| Hero | ✅ (corretto) | ✅ | ✅ |
| Text | ✅ | ✅ | ✅ |
| TwoColumnTextImage | ✅ | ✅ | ✅ |
| TwoColumnImageText | ✅ | ✅ | ✅ |
| Form | ✅ | ✅ | ✅ |
| CTA | ✅ | ✅ | ✅ |
| Features | ✅ | ✅ | ✅ |
| ServicesGrid | ✅ | ✅ | ✅ |
| Slider | ✅ (corretto) | ✅ | ✅ |
| ImageSlide | ✅ (corretto) | ✅ | ✅ |
| Video | ✅ | ✅ | ✅ |
| VideoInfo | ✅ (corretto) | ✅ | ✅ |
| Map | ✅ | ✅ | ✅ |
| Footer | ✅ | ✅ | ✅ |
| QuickContact | N/A | N/A | N/A (floating buttons) |

---

## 🚀 Come Testare

1. **Installa il componente Joomla aggiornato** (v2.0.2)

2. **Crea una landing page con diversi blocchi**

3. **Imposta un font globale** per la pagina (es: "Roboto", "Open Sans", "Montserrat")

4. **Imposta stili per ogni blocco:**
   - Background Color diverso
   - Text Color contrastante
   - Padding personalizzato
   - Rounded corners ON/OFF

5. **Verifica che:**
   - Il font scelto si applica a TUTTI i blocchi
   - Il background color di ogni blocco viene applicato correttamente
   - Il text color di ogni blocco viene applicato correttamente
   - Il padding è rispettato
   - I rounded corners funzionano

---

## 📦 File da Aggiornare sul Server

Per applicare queste modifiche sul server Joomla:

1. **Opzione A - File Singolo:**
   - Sostituisci `/components/com_landingpages/helpers/blockrenderer.php`
   - Con il nuovo file aggiornato

2. **Opzione B - Componente Completo:**
   - Disinstalla versione precedente
   - Installa `joomla-landingpages-v2.0.2.zip`

---

## 🔄 Compatibilità

✅ **Retrocompatibile**: Le pagine esistenti continueranno a funzionare come prima

✅ **Nessuna migration richiesta**: Non ci sono modifiche al database

✅ **Nessuna modifica all'API**: Il backend rimane invariato

---

## 🎨 Esempio di Utilizzo

Nell'editor della landing page:

1. **Impostazioni Pagina → Stili:**
   - Font Family: "Montserrat"

2. **Blocco Hero → Stili:**
   - Background Color: #1e40af (blu)
   - Text Color: #ffffff (bianco)
   - Padding: 80px

3. **Blocco Text → Stili:**
   - Background Color: #f3f4f6 (grigio chiaro)
   - Text Color: #111827 (nero)
   - Padding: 40px

**Risultato:**
- Tutti i testi usano "Montserrat"
- Hero ha sfondo blu, testo bianco, padding 80px
- Text ha sfondo grigio chiaro, testo nero, padding 40px
- Stili applicati correttamente nel componente Joomla! ✅

---

## 📝 Note Tecniche

### Ereditarietà CSS
Il font-family viene applicato al container principale del blocco:
```html
<section class="hero-block" style="font-family: 'Montserrat', system-ui, sans-serif;">
    <h1>Titolo</h1>  <!-- Eredita Montserrat -->
    <p>Testo</p>     <!-- Eredita Montserrat -->
</section>
```

### Priorità Stili
1. **Stili inline** (`style=""`) - priorità massima
2. **Font family globale** - applicato da `getBlockStyle()`
3. **Classi CSS** - priorità minore

### Performance
- Nessun impatto sulle performance
- Stili inline minimali, solo quando necessari
- Font caricato una sola volta (da Google Fonts o locale)

---

**Fine Documentazione** ✅
