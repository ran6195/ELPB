<?php
/**
 * Standalone Block Renderer
 * Converts block JSON data to HTML
 */
class BlockRenderer
{
    private $roundedCorners = true;
    private $apiUrl = '';
    private $pageId = 0;

    /**
     * Constructor
     *
     * @param   string  $apiUrl  API base URL for form submissions
     * @param   int     $pageId  Page ID for form submissions
     */
    public function __construct($apiUrl = '', $pageId = 0)
    {
        $this->apiUrl = $apiUrl;
        $this->pageId = $pageId;
    }

    /**
     * Render a block based on its type
     *
     * @param   array  $block            Block data
     * @param   bool   $roundedCorners   Apply rounded corners
     *
     * @return  string  HTML output
     */
    public function render($block, $roundedCorners = true)
    {
        $this->roundedCorners = $roundedCorners;

        $type = $block['type'] ?? '';
        $content = $block['content'] ?? [];
        $styles = $block['styles'] ?? [];

        $method = 'render' . str_replace('-', '', ucwords($type, '-'));

        if (method_exists($this, $method)) {
            return $this->$method($content, $styles, $block);
        }

        return '<!-- Unknown block type: ' . htmlspecialchars($type) . ' -->';
    }

    /**
     * Get block container style
     *
     * @param   array  $styles  Block styles
     *
     * @return  string  CSS style attribute
     */
    protected function getBlockStyle($styles)
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

        if (!empty($styles['fontFamily'])) {
            $css[] = 'font-family: \'' . $styles['fontFamily'] . '\', sans-serif';
        }

        return !empty($css) ? 'style="' . implode('; ', $css) . '"' : '';
    }

    /**
     * Get rounded class based on settings
     *
     * @return  string  CSS class
     */
    protected function getRoundedClass()
    {
        return $this->roundedCorners ? 'rounded-lg' : '';
    }

    /**
     * Render Header block (Navbar with logo and social media)
     */
    protected function renderHeader($content, $styles, $block)
    {
        $logoUrl = htmlspecialchars($content['logoUrl'] ?? '');
        $logoAlt = htmlspecialchars($content['logoAlt'] ?? 'Logo');
        $logoLink = htmlspecialchars($content['logoLink'] ?? '/');
        $logoHeight = htmlspecialchars($content['logoHeight'] ?? '50px');
        $marginTop = htmlspecialchars($content['marginTop'] ?? '0px');
        $showMenu = $content['showMenu'] ?? false;
        $menuLinks = $content['menuLinks'] ?? [];
        $socialLinks = $content['socialLinks'] ?? [];
        $socialButtonStyle = $content['socialButtonStyle'] ?? [];
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);

        // Social button styling
        $socialBg = htmlspecialchars($socialButtonStyle['backgroundColor'] ?? 'transparent');
        $socialColor = htmlspecialchars($socialButtonStyle['color'] ?? ($styles['textColor'] ?? '#FFFFFF'));
        $socialPadding = htmlspecialchars($socialButtonStyle['padding'] ?? '8px');
        $socialBorderRadius = htmlspecialchars($socialButtonStyle['borderRadius'] ?? '8px');
        $socialBorder = !empty($socialButtonStyle['borderWidth'])
            ? 'border: ' . htmlspecialchars($socialButtonStyle['borderWidth']) . ' solid ' . htmlspecialchars($socialButtonStyle['borderColor'] ?? '#FFFFFF')
            : '';

        $socialStyle = "background-color: {$socialBg}; color: {$socialColor}; padding: {$socialPadding}; border-radius: {$socialBorderRadius}; {$socialBorder}; display: inline-flex; align-items: center; justify-content: center;";

        $hasSocialLinks = !empty($socialLinks['facebook']) || !empty($socialLinks['instagram']) || !empty($socialLinks['twitter']);

        $html = <<<HTML
<nav style="margin-top: {$marginTop}">
    <div class="max-w-7xl mx-auto px-6 py-4 {$roundedClass}" {$blockStyle}>
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="{$logoLink}" class="flex items-center">
HTML;

        if (!empty($logoUrl)) {
            $html .= <<<HTML

                <img src="{$logoUrl}" alt="{$logoAlt}" style="height: {$logoHeight}; width: auto;">
HTML;
        } else {
            $html .= <<<HTML

                <div class="h-12 w-48 bg-gray-300 rounded flex items-center justify-center text-sm text-gray-600">
                    Logo
                </div>
HTML;
        }

        $html .= '</a>';

        // Right side: Menu + Social Buttons
        $html .= '<div class="flex items-center gap-6">';

        // Menu links (if enabled)
        if ($showMenu && !empty($menuLinks)) {
            $html .= '<div class="hidden md:flex items-center space-x-6">';
            foreach ($menuLinks as $link) {
                $linkUrl = htmlspecialchars($link['url'] ?? '#');
                $linkText = htmlspecialchars($link['text'] ?? '');
                $html .= <<<HTML

                <a href="{$linkUrl}" class="text-sm font-medium hover:opacity-80 transition-opacity">
                    {$linkText}
                </a>
HTML;
            }
            $html .= '</div>';
        }

        // Social Buttons
        if ($hasSocialLinks) {
            $html .= '<div class="flex items-center gap-3">';

            // Facebook
            if (!empty($socialLinks['facebook'])) {
                $fbUrl = htmlspecialchars($socialLinks['facebook']);
                $html .= <<<HTML

                <a href="{$fbUrl}" target="_blank" rel="noopener noreferrer" class="social-button transition-all hover:opacity-80" style="{$socialStyle}">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
HTML;
            }

            // Instagram
            if (!empty($socialLinks['instagram'])) {
                $igUrl = htmlspecialchars($socialLinks['instagram']);
                $html .= <<<HTML

                <a href="{$igUrl}" target="_blank" rel="noopener noreferrer" class="social-button transition-all hover:opacity-80" style="{$socialStyle}">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
HTML;
            }

            // X (Twitter)
            if (!empty($socialLinks['twitter'])) {
                $twUrl = htmlspecialchars($socialLinks['twitter']);
                $html .= <<<HTML

                <a href="{$twUrl}" target="_blank" rel="noopener noreferrer" class="social-button transition-all hover:opacity-80" style="{$socialStyle}">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
HTML;
            }

            $html .= '</div>'; // Close social buttons
        }

        $html .= '</div>'; // Close right side

        $html .= <<<HTML

        </div>
    </div>
</nav>
HTML;

        return $html;
    }

    /**
     * Render Image Slide block
     */
    protected function renderImageslide($content, $styles, $block)
    {
        $image = htmlspecialchars($content['image'] ?? '');
        $alt = htmlspecialchars($content['alt'] ?? 'Immagine diapositiva');
        $height = htmlspecialchars($content['height'] ?? '600px');
        $fullWidth = $content['fullWidth'] ?? true;
        $showOverlay = $content['showOverlay'] ?? false;
        $overlayTitle = htmlspecialchars($content['overlayTitle'] ?? '');
        $overlayText = htmlspecialchars($content['overlayText'] ?? '');
        $overlayColor = $content['overlayColor'] ?? '#000000';
        $overlayOpacity = $content['overlayOpacity'] ?? 0.5;
        $overlayTextColor = htmlspecialchars($content['overlayTextColor'] ?? '#FFFFFF');
        $roundedClass = $this->getRoundedClass();

        // Convert hex to rgba for overlay
        $hex = str_replace('#', '', $overlayColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $overlayBg = "rgba({$r}, {$g}, {$b}, {$overlayOpacity})";

        $wrapperClass = $fullWidth ? '' : 'max-w-7xl mx-auto';

        $html = <<<HTML
<div class="image-slide-block">
    <div class="{$wrapperClass}">
        <div class="relative w-full overflow-hidden {$roundedClass}" style="height: {$height}; min-height: {$height};">
HTML;

        if (!empty($image)) {
            $html .= <<<HTML

            <img src="{$image}" alt="{$alt}" class="w-full h-full object-cover">
HTML;
        } else {
            $html .= <<<HTML

            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                <div class="text-center text-gray-400">
                    <p class="text-lg font-medium">Carica un'immagine</p>
                </div>
            </div>
HTML;
        }

        if ($showOverlay && (!empty($overlayTitle) || !empty($overlayText))) {
            $html .= <<<HTML

            <div class="absolute inset-0 flex items-center justify-center" style="background-color: {$overlayBg};">
                <div class="text-center px-6 max-w-4xl">
HTML;
            if (!empty($overlayTitle)) {
                $html .= <<<HTML

                    <h2 class="text-4xl md:text-6xl font-bold mb-4" style="color: {$overlayTextColor};">
                        {$overlayTitle}
                    </h2>
HTML;
            }
            if (!empty($overlayText)) {
                $html .= <<<HTML

                    <p class="text-xl md:text-2xl" style="color: {$overlayTextColor};">
                        {$overlayText}
                    </p>
HTML;
            }
            $html .= '</div></div>';
        }

        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Render Video block
     */
    protected function renderVideo($content, $styles, $block)
    {
        $videoUrl = $content['videoUrl'] ?? '';
        $height = htmlspecialchars($content['height'] ?? '600px');
        $fullWidth = $content['fullWidth'] ?? true;
        $autoplay = $content['autoplay'] ?? false;
        $loop = $content['loop'] ?? false;
        $muted = $content['muted'] ?? false;
        $showControls = $content['showControls'] ?? true;
        $playOnScroll = $content['playOnScroll'] ?? false;
        $roundedClass = $this->getRoundedClass();

        $wrapperClass = $fullWidth ? '' : 'max-w-7xl mx-auto';
        $blockStyle = $this->getBlockStyle($styles);
        $containerId = 'video-container-' . uniqid();
        $videoId = 'video-' . uniqid();

        // Rileva se è YouTube o Vimeo
        $isEmbed = strpos($videoUrl, 'youtube.com') !== false ||
                   strpos($videoUrl, 'youtu.be') !== false ||
                   strpos($videoUrl, 'vimeo.com') !== false;

        $html = <<<HTML
<div class="video-block" id="{$containerId}">
    <div class="{$wrapperClass}" {$blockStyle}>
        <div class="relative w-full overflow-hidden {$roundedClass}" style="height: {$height}; min-height: {$height};">
HTML;

        if (!empty($videoUrl)) {
            if ($isEmbed) {
                // YouTube/Vimeo Embed
                $embedUrl = $this->getEmbedUrl($videoUrl, $autoplay, $loop, $muted, $showControls, $playOnScroll);
                $embedUrlEscaped = htmlspecialchars($embedUrl);

                $html .= <<<HTML

            <iframe id="{$videoId}" src="{$embedUrlEscaped}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
HTML;
            } else {
                // File video diretto
                $videoUrlEscaped = htmlspecialchars($videoUrl);
                $videoType = $this->getVideoMimeType($videoUrl);
                // Se playOnScroll è attivo, rimuovi autoplay
                $autoplayAttr = ($autoplay && !$playOnScroll) ? 'autoplay' : '';
                $loopAttr = $loop ? 'loop' : '';
                $mutedAttr = $muted ? 'muted' : '';
                $controlsAttr = $showControls ? 'controls' : '';

                $html .= <<<HTML

            <video id="{$videoId}" class="w-full h-full object-cover" {$autoplayAttr} {$loopAttr} {$mutedAttr} {$controlsAttr} playsinline>
                <source src="{$videoUrlEscaped}" type="{$videoType}">
                Il tuo browser non supporta il tag video.
            </video>
HTML;
            }
        } else {
            $html .= <<<HTML

            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                <div class="text-center text-gray-400">
                    <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-lg font-medium">Inserisci URL video o YouTube</p>
                    <p class="text-sm mt-2">Supporta: YouTube, Vimeo, MP4, WebM</p>
                </div>
            </div>
HTML;
        }

        $html .= '</div></div></div>';

        // Aggiungi JavaScript per Intersection Observer se playOnScroll è attivo
        if ($playOnScroll && !empty($videoUrl)) {
            $isYoutube = strpos($videoUrl, 'youtube') !== false || strpos($videoUrl, 'youtu.be') !== false;
            $isVimeo = strpos($videoUrl, 'vimeo') !== false;

            $html .= <<<HTML

<script>
(function() {
    var container = document.getElementById('{$containerId}');
    var videoElement = document.getElementById('{$videoId}');
    var hasPlayed = false;

    if (container && videoElement) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !hasPlayed) {
                    hasPlayed = true;

                    // Se è un video HTML5
                    if (videoElement.tagName === 'VIDEO') {
                        videoElement.play().catch(function(err) {
                            console.log('Video play prevented:', err);
                        });
                    }

                    // Se è un iframe YouTube
                    if (videoElement.tagName === 'IFRAME' && {$isYoutube}) {
                        videoElement.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
                    }

                    // Se è un iframe Vimeo
                    if (videoElement.tagName === 'IFRAME' && {$isVimeo}) {
                        videoElement.contentWindow.postMessage('{"method":"play"}', '*');
                    }
                }
            });
        }, {
            threshold: 0.5
        });

        observer.observe(container);
    }
})();
</script>
HTML;
        }

        return $html;
    }

    /**
     * Get video MIME type from extension
     */
    protected function getVideoMimeType($url)
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime'
        ];
        return $mimeTypes[strtolower($extension)] ?? 'video/mp4';
    }

    /**
     * Convert YouTube/Vimeo URL to embed URL
     */
    protected function getEmbedUrl($url, $autoplay, $loop, $muted, $showControls, $playOnScroll = false)
    {
        // YouTube
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            $videoId = '';

            // https://www.youtube.com/watch?v=VIDEO_ID
            if (strpos($url, 'watch?v=') !== false) {
                $parts = explode('watch?v=', $url);
                $videoId = explode('&', $parts[1])[0];
            }
            // https://youtu.be/VIDEO_ID
            else if (strpos($url, 'youtu.be/') !== false) {
                $parts = explode('youtu.be/', $url);
                $videoId = explode('?', $parts[1])[0];
            }
            // https://www.youtube.com/embed/VIDEO_ID (già embed)
            else if (strpos($url, 'youtube.com/embed/') !== false) {
                return $url;
            }

            if ($videoId) {
                $params = ['enablejsapi=1']; // Abilita JS API

                // Se playOnScroll è attivo, non fare autoplay subito
                if ($autoplay && !$playOnScroll) {
                    $params[] = 'autoplay=1';
                }

                if ($loop) {
                    $params[] = 'loop=1';
                    $params[] = 'playlist=' . $videoId;
                }
                if ($muted) $params[] = 'mute=1';
                if (!$showControls) $params[] = 'controls=0';

                $queryString = '?' . implode('&', $params);
                return 'https://www.youtube.com/embed/' . $videoId . $queryString;
            }
        }

        // Vimeo
        if (strpos($url, 'vimeo.com') !== false) {
            $videoId = '';

            // https://vimeo.com/VIDEO_ID
            if (strpos($url, 'vimeo.com/') !== false && strpos($url, 'player.vimeo.com') === false) {
                $parts = explode('vimeo.com/', $url);
                $videoId = explode('?', explode('/', $parts[1])[0])[0];
            }
            // https://player.vimeo.com/video/VIDEO_ID (già embed)
            else if (strpos($url, 'player.vimeo.com/video/') !== false) {
                return $url;
            }

            if ($videoId) {
                $params = [];

                // Se playOnScroll è attivo, non fare autoplay subito
                if ($autoplay && !$playOnScroll) {
                    $params[] = 'autoplay=1';
                }

                if ($loop) $params[] = 'loop=1';
                if ($muted) $params[] = 'muted=1';

                $queryString = !empty($params) ? '?' . implode('&', $params) : '';
                return 'https://player.vimeo.com/video/' . $videoId . $queryString;
            }
        }

        return $url;
    }

    /**
     * Render Features block (3-column grid)
     */
    protected function renderFeatures($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? '');
        $features = $content['features'] ?? [];
        $roundedClass = $this->getRoundedClass();
        $blockStyle = $this->getBlockStyle($styles);

        $html = <<<HTML
<div class="features-block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16 {$roundedClass}" {$blockStyle}>
HTML;

        if (!empty($title)) {
            $html .= <<<HTML

        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12">{$title}</h2>
HTML;
        }

        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">';

        foreach ($features as $feature) {
            $featureTitle = htmlspecialchars($feature['title'] ?? '');
            $featureDesc = htmlspecialchars($feature['description'] ?? '');

            $html .= <<<HTML

            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">{$featureTitle}</h3>
                <p class="text-sm sm:text-base text-gray-600 leading-relaxed">{$featureDesc}</p>
            </div>
HTML;
        }

        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Render Services Grid block
     */
    protected function renderServicesgrid($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? '');
        $services = $content['services'] ?? [];
        $roundedClass = $this->getRoundedClass();
        $blockStyle = $this->getBlockStyle($styles);

        $html = <<<HTML
<div class="services-grid-block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16 {$roundedClass}" {$blockStyle}>
HTML;

        if (!empty($title)) {
            $html .= <<<HTML

        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12">{$title}</h2>
HTML;
        }

        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">';

        foreach ($services as $service) {
            $serviceTitle = htmlspecialchars($service['title'] ?? '');
            $serviceDesc = htmlspecialchars($service['description'] ?? '');
            $serviceImage = htmlspecialchars($service['image'] ?? '');
            $serviceLink = htmlspecialchars($service['link'] ?? '');

            $html .= <<<HTML

            <div class="bg-white shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl {$roundedClass}">
                <div class="relative h-48 bg-gray-200 overflow-hidden">
HTML;

            if (!empty($serviceImage)) {
                $html .= <<<HTML

                    <img src="{$serviceImage}" alt="{$serviceTitle}" class="w-full h-full object-cover">
HTML;
            } else {
                $html .= <<<HTML

                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        Carica immagine
                    </div>
HTML;
            }

            $html .= <<<HTML

                </div>
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3">{$serviceTitle}</h3>
                    <p class="text-sm sm:text-base text-gray-600 leading-relaxed">{$serviceDesc}</p>
HTML;

            if (!empty($serviceLink)) {
                $html .= <<<HTML

                    <a href="{$serviceLink}" class="inline-block mt-3 sm:mt-4 text-sm sm:text-base text-blue-600 hover:text-blue-700 font-medium">
                        Scopri di più →
                    </a>
HTML;
            }

            $html .= '</div></div>';
        }

        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Render CTA (Call to Action) block
     */
    protected function renderCta($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Pronto per iniziare?');
        $description = htmlspecialchars($content['description'] ?? '');
        $buttonText = htmlspecialchars($content['buttonText'] ?? 'Inizia ora');
        $buttonLink = htmlspecialchars($content['buttonLink'] ?? '#');
        $secondaryText = htmlspecialchars($content['secondaryText'] ?? '');
        $roundedClass = $this->getRoundedClass();
        $blockStyle = $this->getBlockStyle($styles);

        $html = <<<HTML
<div class="cta-block">
    <div class="max-w-7xl mx-auto px-6 py-16 text-center {$roundedClass}" {$blockStyle}>
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{$title}</h2>
HTML;

        if (!empty($description)) {
            $html .= <<<HTML

        <p class="text-lg md:text-xl text-gray-600 mb-8">{$description}</p>
HTML;
        }

        $html .= <<<HTML

        <a href="{$buttonLink}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 text-lg font-semibold transition-colors shadow-lg hover:shadow-xl {$roundedClass}">
            {$buttonText}
        </a>
HTML;

        if (!empty($secondaryText)) {
            $html .= <<<HTML

        <p class="mt-4 text-sm text-gray-500">{$secondaryText}</p>
HTML;
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Render Hero block
     */
    protected function renderHero($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Title');
        $subtitle = htmlspecialchars($content['subtitle'] ?? 'Subtitle');
        $buttonText = htmlspecialchars($content['buttonText'] ?? 'Call to Action');
        $buttonLink = htmlspecialchars($content['buttonLink'] ?? '#');
        $backgroundImage = htmlspecialchars($content['backgroundImage'] ?? '');
        $roundedClass = $this->getRoundedClass();

        // Build inline styles
        $inlineStyles = [];

        if (!empty($styles['backgroundColor'])) {
            $inlineStyles[] = 'background-color: ' . $styles['backgroundColor'];
        }

        if (!empty($styles['textColor'])) {
            $inlineStyles[] = 'color: ' . $styles['textColor'];
        }

        if (!empty($styles['padding'])) {
            $inlineStyles[] = 'padding: ' . $styles['padding'];
        }

        if (!empty($styles['fontFamily'])) {
            $inlineStyles[] = 'font-family: \'' . $styles['fontFamily'] . '\', sans-serif';
        }

        // Add background image if present
        if (!empty($backgroundImage)) {
            $inlineStyles[] = 'background-image: url(' . $backgroundImage . ')';
            $inlineStyles[] = 'background-size: cover';
            $inlineStyles[] = 'background-position: center';
        }

        // Always add min-height
        $inlineStyles[] = 'min-height: 400px';

        $styleAttr = !empty($inlineStyles) ? 'style="' . implode('; ', $inlineStyles) . '"' : '';

        // Button styles
        $buttonStyle = $content['buttonStyle'] ?? [];
        $buttonBg = htmlspecialchars($buttonStyle['backgroundColor'] ?? '#4F46E5');
        $buttonColor = htmlspecialchars($buttonStyle['textColor'] ?? '#FFFFFF');
        $buttonFontSize = htmlspecialchars($buttonStyle['fontSize'] ?? '16px');
        $buttonPadding = htmlspecialchars($buttonStyle['padding'] ?? '12px 32px');
        $buttonRadius = htmlspecialchars($buttonStyle['borderRadius'] ?? '8px');
        $buttonBorderWidth = htmlspecialchars($buttonStyle['borderWidth'] ?? '0px');
        $buttonBorderColor = htmlspecialchars($buttonStyle['borderColor'] ?? 'transparent');
        $buttonBorderStyle = htmlspecialchars($buttonStyle['borderStyle'] ?? 'solid');
        $buttonShadow = $buttonStyle['shadow'] ?? 'md';

        $shadowMap = [
            'none' => 'none',
            'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
        ];
        $boxShadow = $shadowMap[$buttonShadow] ?? $shadowMap['md'];

        $buttonStyles = "background-color: {$buttonBg}; color: {$buttonColor}; font-size: {$buttonFontSize}; padding: {$buttonPadding}; border-radius: {$buttonRadius}; border-width: {$buttonBorderWidth}; border-color: {$buttonBorderColor}; border-style: {$buttonBorderStyle}; box-shadow: {$boxShadow};";

        return <<<HTML
<div class="hero-block">
    <div class="max-w-7xl mx-auto px-6 py-20 text-center {$roundedClass}" {$styleAttr}>
        <h1 class="text-5xl font-bold mb-4">{$title}</h1>
        <p class="text-xl mb-8">{$subtitle}</p>
        <a href="{$buttonLink}" class="inline-block font-semibold transition-all hover:opacity-90" style="{$buttonStyles}">
            {$buttonText}
        </a>
    </div>
</div>
HTML;
    }

    /**
     * Render Text block
     */
    protected function renderText($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Title');
        // Don't escape HTML in text content - it contains formatted HTML
        $text = $content['text'] ?? '<p>Text content</p>';
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);

        return <<<HTML
<div class="text-block">
    <div class="max-w-7xl mx-auto px-6 py-12 {$roundedClass}" {$blockStyle}>
        <h2 class="text-3xl font-bold mb-4">{$title}</h2>
        <div class="text-lg leading-relaxed prose max-w-none">{$text}</div>
    </div>
</div>
HTML;
    }

    /**
     * Render Two Column Text-Image block
     */
    protected function renderTwocolumntextimage($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Title');
        $text = htmlspecialchars($content['text'] ?? 'Text');
        $image = htmlspecialchars($content['image'] ?? '');
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);

        return <<<HTML
<section class="two-column-text-image py-8 sm:py-12 px-4 sm:px-6" {$blockStyle}>
    <div class="container mx-auto max-w-7xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-center">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold mb-3 sm:mb-4">{$title}</h2>
                <p class="text-sm sm:text-base text-gray-600">{$text}</p>
            </div>
            <div>
                <img src="{$image}" alt="{$title}" class="w-full h-auto shadow-lg {$roundedClass}">
            </div>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * Render Two Column Image-Text block
     */
    protected function renderTwocolumnimagetext($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Title');
        $text = htmlspecialchars($content['text'] ?? 'Text');
        $image = htmlspecialchars($content['image'] ?? '');
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);

        return <<<HTML
<section class="two-column-image-text py-8 sm:py-12 px-4 sm:px-6" {$blockStyle}>
    <div class="container mx-auto max-w-7xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-center">
            <div>
                <img src="{$image}" alt="{$title}" class="w-full h-auto shadow-lg {$roundedClass}">
            </div>
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold mb-3 sm:mb-4">{$title}</h2>
                <p class="text-sm sm:text-base text-gray-600">{$text}</p>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * Render Video-Info block
     */
    protected function renderVideoinfo($content, $styles, $block)
    {
        $videoUrl = htmlspecialchars($content['videoUrl'] ?? '');
        $title = htmlspecialchars($content['title'] ?? 'Visita il nostro Showroom');
        $subtitle = htmlspecialchars($content['subtitle'] ?? '');
        $mapImage = htmlspecialchars($content['mapImage'] ?? '');
        $mapLink = htmlspecialchars($content['mapLink'] ?? '#');
        $scheduleText = $content['scheduleText'] ?? '<p>Orari di apertura</p>';
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);
        $bgColor = $styles['backgroundColor'] ?? '#1f2937';
        $textColor = $styles['textColor'] ?? '#ffffff';

        $videoHtml = '';
        if (!empty($videoUrl)) {
            $videoHtml = <<<HTML
                <video controls autoplay muted playsinline loop class="w-full h-auto mx-auto max-w-md">
                    <source src="{$videoUrl}" type="video/mp4">
                    Il tuo browser non supporta il tag video.
                </video>
HTML;
        } else {
            $videoHtml = '<div class="w-full h-96 bg-gray-800 flex items-center justify-center rounded"><p class="text-gray-400">Inserisci URL video</p></div>';
        }

        $mapHtml = '';
        if (!empty($mapImage)) {
            $mapHtml = <<<HTML
                <a href="{$mapLink}" target="_blank" class="inline-block">
                    <img src="{$mapImage}" alt="Mappa" class="w-3/4 h-auto rounded shadow-lg hover:opacity-90 transition-opacity">
                </a>
HTML;
        } else {
            $mapHtml = '<div class="w-3/4 h-48 bg-gray-800 flex items-center justify-center rounded"><p class="text-gray-400 text-sm">Carica immagine mappa</p></div>';
        }

        return <<<HTML
<section class="video-info-block w-full {$roundedClass}" {$blockStyle} style="background-color: {$bgColor}; color: {$textColor};">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-0">
            <!-- Video Column -->
            <div class="flex items-center justify-center p-8 bg-gray-900">
                <div class="w-full">
                    {$videoHtml}
                </div>
            </div>
            <!-- Info Column -->
            <div class="flex flex-col justify-center p-8 bg-gray-900 text-white">
                <h2 class="text-2xl font-bold mb-2">{$title}</h2>
                <p class="text-lg mb-4">{$subtitle}</p>
                <div class="mb-4">
                    {$mapHtml}
                </div>
                <div class="border-t border-gray-700 pt-4">
                    <div class="text-sm leading-relaxed">
                        {$scheduleText}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    /**
     * Render Form block
     */
    protected function renderForm($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? 'Contattaci');
        $fields = $content['fields'] ?? [];
        $buttonText = htmlspecialchars($content['buttonText'] ?? 'Invia');
        $showPrivacy = $content['showPrivacy'] ?? true;
        $privacyLink = htmlspecialchars($content['privacyLink'] ?? '/privacy-policy');
        $privacyTextColor = htmlspecialchars($content['privacyTextColor'] ?? '#374151');
        $recaptchaSiteKey = htmlspecialchars($content['recaptchaSiteKey'] ?? '');
        $thankYouUrl = htmlspecialchars($content['thankYouUrl'] ?? '');
        $roundedClass = $this->getRoundedClass();

        // Use API URL and page ID from constructor
        $apiUrl = $this->apiUrl;
        $pageId = $this->pageId;

        $formId = 'landing-form-' . ($block['id'] ?? uniqid());

        $blockStyle = $this->getBlockStyle($styles);

        // Button styles
        $buttonStyle = $content['buttonStyle'] ?? [];
        $buttonBg = htmlspecialchars($buttonStyle['backgroundColor'] ?? '#4F46E5');
        $buttonColor = htmlspecialchars($buttonStyle['textColor'] ?? '#FFFFFF');
        $buttonFontSize = htmlspecialchars($buttonStyle['fontSize'] ?? '16px');
        $buttonPadding = htmlspecialchars($buttonStyle['padding'] ?? '12px 32px');
        $buttonRadius = htmlspecialchars($buttonStyle['borderRadius'] ?? '8px');
        $buttonBorderWidth = htmlspecialchars($buttonStyle['borderWidth'] ?? '0px');
        $buttonBorderColor = htmlspecialchars($buttonStyle['borderColor'] ?? 'transparent');
        $buttonBorderStyle = htmlspecialchars($buttonStyle['borderStyle'] ?? 'solid');
        $buttonShadow = $buttonStyle['shadow'] ?? 'md';

        $shadowMap = [
            'none' => 'none',
            'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
        ];
        $boxShadow = $shadowMap[$buttonShadow] ?? $shadowMap['md'];

        $buttonStyles = "background-color: {$buttonBg}; color: {$buttonColor}; font-size: {$buttonFontSize}; padding: {$buttonPadding}; border-radius: {$buttonRadius}; border-width: {$buttonBorderWidth}; border-color: {$buttonBorderColor}; border-style: {$buttonBorderStyle}; box-shadow: {$boxShadow};";

        $html = <<<HTML
<section class="form-block py-12 px-4" {$blockStyle}>
    <div class="container mx-auto max-w-2xl">
        <h2 class="text-3xl font-bold mb-8 text-center">{$title}</h2>
        <form id="{$formId}" class="landing-page-form space-y-4" method="post" action="">
            <input type="hidden" name="page_id" value="{$pageId}">
            <input type="hidden" name="thank_you_url" value="{$thankYouUrl}">
HTML;

        // Render dynamic fields
        foreach ($fields as $field) {
            $fieldName = htmlspecialchars($field['name'] ?? '');
            $fieldLabel = htmlspecialchars($field['label'] ?? '');
            $fieldType = htmlspecialchars($field['type'] ?? 'text');
            $fieldRequired = !empty($field['required']) ? 'required' : '';
            $requiredMark = !empty($field['required']) ? '<span class="text-red-500">*</span>' : '';

            if ($fieldType === 'textarea') {
                $html .= <<<HTML

            <div class="form-field">
                <label for="{$fieldName}" class="block text-sm font-medium mb-2">{$fieldLabel} {$requiredMark}</label>
                <textarea id="{$fieldName}" name="{$fieldName}" rows="4" class="w-full px-3 py-2 border border-gray-300 {$roundedClass} focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none" {$fieldRequired}></textarea>
            </div>
HTML;
            } else {
                $html .= <<<HTML

            <div class="form-field">
                <label for="{$fieldName}" class="block text-sm font-medium mb-2">{$fieldLabel} {$requiredMark}</label>
                <input id="{$fieldName}" type="{$fieldType}" name="{$fieldName}" class="w-full px-3 py-2 border border-gray-300 {$roundedClass} focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none" {$fieldRequired}>
            </div>
HTML;
            }
        }

        // Privacy checkbox
        if ($showPrivacy) {
            $html .= <<<HTML

            <div class="form-field">
                <label class="flex items-start space-x-2">
                    <input type="checkbox" name="privacy_accepted" required class="mt-1 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <span class="text-sm" style="color: {$privacyTextColor};">
                        Accetto la
                        <a href="{$privacyLink}" target="_blank" class="text-primary-600 hover:text-primary-800 underline">
                            privacy policy e il trattamento dei dati
                        </a>
                        <span class="text-red-500">*</span>
                    </span>
                </label>
            </div>
HTML;
        }

        // reCAPTCHA
        if (!empty($recaptchaSiteKey)) {
            $html .= <<<HTML

            <div class="form-field">
                <div class="g-recaptcha" data-sitekey="{$recaptchaSiteKey}"></div>
            </div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
        }

        $html .= <<<HTML

            <button type="submit" class="w-full font-medium transition-colors" style="{$buttonStyles}">
                {$buttonText}
            </button>

            <div id="{$formId}-message" class="hidden mt-4 p-4 rounded"></div>
        </form>
    </div>
</section>

<script>
(function() {
    const form = document.getElementById('{$formId}');
    const messageDiv = document.getElementById('{$formId}-message');
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    const apiUrl = '{$apiUrl}';

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Get form data
        const formData = new FormData(form);
        const data = {
            page_id: parseInt(formData.get('page_id')) || null,
            privacy_accepted: formData.get('privacy_accepted') === 'on'
        };

        // Add reCAPTCHA token if present
        if ('{$recaptchaSiteKey}' && window.grecaptcha) {
            const recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse) {
                showMessage('Per favore completa il reCAPTCHA', 'error');
                return;
            }
            data.recaptcha_token = recaptchaResponse;
        }

        // Add dynamic field values
        const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), textarea');
        fields.forEach(field => {
            if (field.name && field.name !== 'privacy_accepted') {
                data[field.name] = field.value;
            }
        });

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = 'Invio in corso...';

        try {
            const response = await fetch(apiUrl + '/leads', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                // Success - redirect to thank you page
                const thankYouUrl = formData.get('thank_you_url');
                if (thankYouUrl && thankYouUrl.trim() !== '') {
                    window.location.href = thankYouUrl;
                } else {
                    // Default thank you message
                    showMessage('Grazie! La tua richiesta è stata inviata con successo.', 'success');
                    form.reset();
                    if (window.grecaptcha) {
                        grecaptcha.reset();
                    }
                }
            } else {
                showMessage(result.error || 'Si è verificato un errore. Riprova più tardi.', 'error');
                if (window.grecaptcha) {
                    grecaptcha.reset();
                }
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showMessage('Si è verificato un errore. Riprova più tardi.', 'error');
            if (window.grecaptcha) {
                grecaptcha.reset();
            }
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });

    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'mt-4 p-4 rounded ' + (type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-700');
        messageDiv.classList.remove('hidden');

        if (type === 'success') {
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }
    }
})();
</script>
HTML;

        return $html;
    }

    /**
     * Render Slider block
     */
    protected function renderSlider($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? '');
        $slides = $content['slides'] ?? [];
        $autoplay = $content['autoplay'] ?? true;
        $autoplayDelay = intval($content['autoplayDelay'] ?? 3000);
        $loop = $content['loop'] ?? true;
        $slidesPerViewDesktop = intval($content['slidesPerViewDesktop'] ?? 3);
        $slideGap = intval($content['slideGap'] ?? 20);
        $slideHeight = $content['slideHeight'] ?? '';
        $slideAspectRatio = $content['slideAspectRatio'] ?? 'square';
        $showNavigation = $content['showNavigation'] ?? true;
        $showPagination = $content['showPagination'] ?? true;
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);
        $sliderId = 'slider-' . uniqid();

        // Map aspect ratios to CSS classes
        $aspectRatioClasses = [
            'square' => 'aspect-square',
            '16-9' => 'aspect-video',
            '4-3' => 'aspect-[4/3]',
            '3-4' => 'aspect-[3/4]',
            '21-9' => 'aspect-[21/9]',
            '3-2' => 'aspect-[3/2]'
        ];
        $aspectClass = $aspectRatioClasses[$slideAspectRatio] ?? 'aspect-square';

        $html = <<<HTML
<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<section class="slider-block py-12 px-4" {$blockStyle}>
    <div class="max-w-7xl mx-auto">
HTML;

        if (!empty($title)) {
            $html .= <<<HTML

        <h2 class="text-3xl font-bold text-center mb-8">{$title}</h2>
HTML;
        }

        $html .= <<<HTML

        <div class="relative">
            <div class="swiper" id="{$sliderId}">
                <div class="swiper-wrapper">
HTML;

        foreach ($slides as $index => $slide) {
            $image = htmlspecialchars($slide['image'] ?? 'https://placehold.co/800x800');
            $alt = htmlspecialchars($slide['alt'] ?? 'Slide ' . ($index + 1));
            $slideTitle = htmlspecialchars($slide['title'] ?? '');
            $slideDescription = htmlspecialchars($slide['description'] ?? '');

            $imageContainerStyle = '';
            if (!empty($slideHeight)) {
                $imageContainerStyle = 'style="height: ' . htmlspecialchars($slideHeight) . ';"';
            }

            $html .= <<<HTML

                    <div class="swiper-slide">
                        <div class="slide-item overflow-hidden {$roundedClass}">
                            <div class="overflow-hidden {$aspectClass}" {$imageContainerStyle}>
                                <img src="{$image}" alt="{$alt}" class="w-full h-full object-cover">
                            </div>
HTML;

            if (!empty($slideTitle) || !empty($slideDescription)) {
                $html .= '<div class="p-6">';
                if (!empty($slideTitle)) {
                    $html .= "<h3 class=\"text-xl font-semibold mb-2\">{$slideTitle}</h3>";
                }
                if (!empty($slideDescription)) {
                    $html .= "<p class=\"text-gray-600\">{$slideDescription}</p>";
                }
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        $html .= '</div>'; // Close swiper-wrapper
        $html .= '</div>'; // Close swiper

        // Navigation arrows - positioned outside swiper for better visibility
        if ($showNavigation) {
            $html .= <<<HTML

            <div id="{$sliderId}-prev" class="swiper-button-prev-custom absolute left-2 top-1/2 -translate-y-1/2 z-50 w-12 h-12 bg-white/90 rounded-full shadow-xl flex items-center justify-center cursor-pointer hover:bg-white transition-all">
                <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </div>
            <div id="{$sliderId}-next" class="swiper-button-next-custom absolute right-2 top-1/2 -translate-y-1/2 z-50 w-12 h-12 bg-white/90 rounded-full shadow-xl flex items-center justify-center cursor-pointer hover:bg-white transition-all">
                <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
HTML;
        }

        // Pagination dots
        if ($showPagination) {
            $html .= <<<HTML

            <div id="{$sliderId}-pagination" class="swiper-pagination-custom mt-8 flex justify-center gap-2"></div>
HTML;
        }

        $html .= '</div>'; // Close relative
        $html .= '</div></section>'; // Close max-w-7xl and slider-block

        // Initialize Swiper - convert PHP booleans to JavaScript strings
        $loopJs = $loop ? 'true' : 'false';
        $autoplayConfig = $autoplay ? "{delay: {$autoplayDelay}, disableOnInteraction: false}" : 'false';
        $navigationConfig = $showNavigation ? "{nextEl: '#{$sliderId}-next', prevEl: '#{$sliderId}-prev'}" : 'false';
        $paginationConfig = $showPagination ? "{clickable: true, el: '#{$sliderId}-pagination'}" : 'false';

        $html .= <<<HTML

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
(function() {
    if (typeof Swiper === 'undefined') {
        console.error('Swiper library not loaded');
        return;
    }

    const swiper = new Swiper('#{$sliderId}', {
        slidesPerView: 1,
        spaceBetween: {$slideGap},
        loop: {$loopJs},
        autoplay: {$autoplayConfig},
        navigation: {$navigationConfig},
        pagination: {$paginationConfig},
        breakpoints: {
            768: {
                slidesPerView: {$slidesPerViewDesktop},
                spaceBetween: {$slideGap}
            }
        }
    });
})();
</script>
HTML;

        return $html;
    }

    /**
     * Render Map block (Google Maps)
     */
    protected function renderMap($content, $styles, $block)
    {
        $title = htmlspecialchars($content['title'] ?? '');
        $description = htmlspecialchars($content['description'] ?? '');
        $mapUrl = htmlspecialchars($content['mapUrl'] ?? '');
        $height = htmlspecialchars($content['height'] ?? '450px');
        $showContactInfo = $content['showContactInfo'] ?? false;
        $address = htmlspecialchars($content['address'] ?? '');
        $phone = htmlspecialchars($content['phone'] ?? '');
        $email = htmlspecialchars($content['email'] ?? '');
        $roundedClass = $this->getRoundedClass();

        $blockStyle = $this->getBlockStyle($styles);

        $html = <<<HTML
<div class="map-block">
    <div class="max-w-7xl mx-auto px-6 py-16 {$roundedClass}" {$blockStyle}>
HTML;

        // Title
        if (!empty($title)) {
            $html .= <<<HTML

        <h2 class="text-3xl font-bold text-center mb-8">{$title}</h2>
HTML;
        }

        // Description
        if (!empty($description)) {
            $html .= <<<HTML

        <p class="text-center text-gray-600 mb-8">{$description}</p>
HTML;
        }

        // Map
        $html .= <<<HTML

        <div class="overflow-hidden {$roundedClass}" style="height: {$height};">
HTML;

        if (!empty($mapUrl)) {
            $html .= <<<HTML

            <iframe src="{$mapUrl}" width="100%" height="{$height}" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
HTML;
        } else {
            $html .= <<<HTML

            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="text-lg font-medium">Inserisci l'URL della mappa</p>
                    <p class="text-sm mt-2">Usa il link di Google Maps</p>
                </div>
            </div>
HTML;
        }

        $html .= '</div>'; // Close map container

        // Contact info
        if ($showContactInfo && (!empty($address) || !empty($phone) || !empty($email))) {
            $html .= '<div class="mt-8 grid md:grid-cols-3 gap-6">';

            // Address
            if (!empty($address)) {
                $html .= <<<HTML

                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold mb-1">Indirizzo</h4>
                        <p class="text-gray-600">{$address}</p>
                    </div>
                </div>
HTML;
            }

            // Phone
            if (!empty($phone)) {
                $html .= <<<HTML

                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold mb-1">Telefono</h4>
                        <p class="text-gray-600">{$phone}</p>
                    </div>
                </div>
HTML;
            }

            // Email
            if (!empty($email)) {
                $html .= <<<HTML

                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold mb-1">Email</h4>
                        <p class="text-gray-600">{$email}</p>
                    </div>
                </div>
HTML;
            }

            $html .= '</div>'; // Close contact info grid
        }

        $html .= '</div></div>'; // Close containers

        return $html;
    }

    /**
     * Render Quick Contact floating buttons (WhatsApp & Phone)
     */
    protected function renderQuickcontact($content, $styles, $block)
    {
        $html = '';

        // WhatsApp Button
        $whatsapp = $content['whatsapp'] ?? [];
        if (($whatsapp['enabled'] ?? false) && !empty($whatsapp['number'])) {
            $number = preg_replace('/\s+/', '', $whatsapp['number']);
            $message = urlencode($whatsapp['message'] ?? '');
            $link = "https://wa.me/{$number}" . ($message ? "?text={$message}" : '');
            $tooltip = htmlspecialchars($whatsapp['tooltip'] ?? 'Contattaci su WhatsApp');
            $showText = $whatsapp['showText'] ?? false;
            $text = htmlspecialchars($whatsapp['text'] ?? 'WhatsApp');

            $style = $whatsapp['style'] ?? [];
            $bgColor = $style['backgroundColor'] ?? '#25D366';
            $color = $style['color'] ?? '#FFFFFF';
            $bottom = $style['bottom'] ?? '20px';
            $right = $style['right'] ?? '20px';
            $width = $style['width'] ?? '60px';
            $height = $style['height'] ?? '60px';
            $borderRadius = $style['borderRadius'] ?? '50%';
            $fontSize = $style['fontSize'] ?? '24px';

            $buttonStyle = "background-color: {$bgColor}; color: {$color}; bottom: {$bottom}; right: {$right}; width: {$width}; height: {$height}; border-radius: {$borderRadius}; font-size: {$fontSize};";

            $html .= <<<HTML
<a href="{$link}" target="_blank" rel="noopener noreferrer"
   class="quick-contact-btn whatsapp-btn"
   style="{$buttonStyle}"
   title="{$tooltip}">
    <svg class="icon" viewBox="0 0 24 24" fill="currentColor" style="width: 1em; height: 1em;">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
HTML;
            if ($showText) {
                $html .= "<span class=\"btn-text\" style=\"font-weight: 600; font-size: 14px; white-space: nowrap; padding-right: 12px;\">{$text}</span>";
            }
            $html .= "</a>\n";
        }

        // Phone Button
        $phone = $content['phone'] ?? [];
        if (($phone['enabled'] ?? false) && !empty($phone['number'])) {
            $number = $phone['number'];
            $link = "tel:{$number}";
            $tooltip = htmlspecialchars($phone['tooltip'] ?? 'Chiamaci');
            $showText = $phone['showText'] ?? false;
            $text = htmlspecialchars($phone['text'] ?? 'Chiama');

            $style = $phone['style'] ?? [];
            $bgColor = $style['backgroundColor'] ?? '#007BFF';
            $color = $style['color'] ?? '#FFFFFF';
            $bottom = $style['bottom'] ?? '20px';
            $left = $style['left'] ?? '20px';
            $width = $style['width'] ?? '60px';
            $height = $style['height'] ?? '60px';
            $borderRadius = $style['borderRadius'] ?? '50%';
            $fontSize = $style['fontSize'] ?? '24px';

            $buttonStyle = "background-color: {$bgColor}; color: {$color}; bottom: {$bottom}; left: {$left}; width: {$width}; height: {$height}; border-radius: {$borderRadius}; font-size: {$fontSize};";

            $html .= <<<HTML
<a href="{$link}"
   class="quick-contact-btn phone-btn"
   style="{$buttonStyle}"
   title="{$tooltip}">
    <svg class="icon" viewBox="0 0 24 24" fill="currentColor" style="width: 1em; height: 1em;">
        <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
    </svg>
HTML;
            if ($showText) {
                $html .= "<span class=\"btn-text\" style=\"font-weight: 600; font-size: 14px; white-space: nowrap; padding-right: 12px;\">{$text}</span>";
            }
            $html .= "</a>\n";
        }

        // Add inline styles for the buttons
        if ($html !== '') {
            $html = <<<HTML
<style>
.quick-contact-btn {
    position: fixed;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
}
.quick-contact-btn:hover {
    transform: scale(1.1) translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}
.quick-contact-btn .icon {
    flex-shrink: 0;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}
.whatsapp-btn {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4); }
    50% { box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4), 0 0 0 10px rgba(37, 211, 102, 0); }
}
.phone-btn {
    animation: pulse-phone 2s infinite;
}
@keyframes pulse-phone {
    0%, 100% { box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4); }
    50% { box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4), 0 0 0 10px rgba(0, 123, 255, 0); }
}
@media (max-width: 768px) {
    .quick-contact-btn {
        width: 50px !important;
        height: 50px !important;
        font-size: 20px !important;
    }
    .quick-contact-btn .btn-text {
        display: none;
    }
    .whatsapp-btn {
        right: 15px !important;
        bottom: 15px !important;
    }
    .phone-btn {
        left: 15px !important;
        bottom: 15px !important;
    }
}
</style>
{$html}
HTML;
        }

        return $html;
    }

    /**
     * Render Social block
     */
    protected function renderSocial($content, $styles, $block)
    {
        $facebookUrl = htmlspecialchars($content['facebookUrl'] ?? '');
        $instagramUrl = htmlspecialchars($content['instagramUrl'] ?? '');
        $xUrl = htmlspecialchars($content['xUrl'] ?? '');
        $linkedinUrl = htmlspecialchars($content['linkedinUrl'] ?? '');
        $youtubeUrl = htmlspecialchars($content['youtubeUrl'] ?? '');

        $iconStyle = $content['iconStyle'] ?? 'colored';
        $buttonSize = intval($content['buttonSize'] ?? 48);
        $buttonSpacing = intval($content['buttonSpacing'] ?? 16);
        $buttonBackground = htmlspecialchars($content['buttonBackground'] ?? 'transparent');
        $borderRadius = intval($content['borderRadius'] ?? 8);
        $shadow = $content['shadow'] ?? 'md';
        $borderWidth = intval($content['borderWidth'] ?? 0);
        $borderColor = htmlspecialchars($content['borderColor'] ?? '#e5e7eb');
        $borderStyle = $content['borderStyle'] ?? 'solid';

        $roundedClass = $this->getRoundedClass();
        $blockStyle = $this->getBlockStyle($styles);

        // Shadow map
        $shadowMap = [
            'none' => 'none',
            'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
        ];
        $boxShadow = $shadowMap[$shadow] ?? $shadowMap['md'];

        $buttonStyle = sprintf(
            'display: inline-flex; align-items: center; justify-content: center; ' .
            'border-radius: %dpx; box-shadow: %s; border-width: %dpx; ' .
            'border-color: %s; border-style: %s; padding: 12px; background-color: %s; ' .
            'transition: opacity 0.3s;',
            $borderRadius,
            $boxShadow,
            $borderWidth,
            $borderColor,
            $borderStyle,
            $buttonBackground
        );

        $currentColor = ($iconStyle === 'monochrome') ? 'currentColor' : '';

        $html = <<<HTML
<div class="social-block">
    <div class="max-w-7xl mx-auto px-6 py-12 {$roundedClass}" {$blockStyle}>
        <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: {$buttonSpacing}px;">
HTML;

        // Facebook
        if (!empty($facebookUrl)) {
            $fbIcon = ($iconStyle === 'colored')
                ? '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>'
                : '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="' . $currentColor . '"/>';

            $html .= <<<HTML

            <a href="{$facebookUrl}" target="_blank" rel="noopener noreferrer" style="{$buttonStyle}" title="Facebook" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <svg width="{$buttonSize}" height="{$buttonSize}" viewBox="0 0 24 24" fill="none">
                    {$fbIcon}
                </svg>
            </a>
HTML;
        }

        // Instagram
        if (!empty($instagramUrl)) {
            if ($iconStyle === 'colored') {
                $igIcon = <<<SVG
<defs>
    <linearGradient id="instagram-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
        <stop offset="0%" style="stop-color:#FD5949;stop-opacity:1" />
        <stop offset="50%" style="stop-color:#D6249F;stop-opacity:1" />
        <stop offset="100%" style="stop-color:#285AEB;stop-opacity:1" />
    </linearGradient>
</defs>
<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" fill="url(#instagram-gradient)"/>
SVG;
            } else {
                $igIcon = '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" fill="' . $currentColor . '"/>';
            }

            $html .= <<<HTML

            <a href="{$instagramUrl}" target="_blank" rel="noopener noreferrer" style="{$buttonStyle}" title="Instagram" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <svg width="{$buttonSize}" height="{$buttonSize}" viewBox="0 0 24 24" fill="none">
                    {$igIcon}
                </svg>
            </a>
HTML;
        }

        // X (Twitter)
        if (!empty($xUrl)) {
            $xColor = ($iconStyle === 'colored') ? '#000000' : $currentColor;
            $html .= <<<HTML

            <a href="{$xUrl}" target="_blank" rel="noopener noreferrer" style="{$buttonStyle}" title="X (Twitter)" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <svg width="{$buttonSize}" height="{$buttonSize}" viewBox="0 0 24 24" fill="{$xColor}">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
            </a>
HTML;
        }

        // LinkedIn
        if (!empty($linkedinUrl)) {
            $liIcon = ($iconStyle === 'colored')
                ? '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" fill="#0A66C2"/>'
                : '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" fill="' . $currentColor . '"/>';

            $html .= <<<HTML

            <a href="{$linkedinUrl}" target="_blank" rel="noopener noreferrer" style="{$buttonStyle}" title="LinkedIn" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <svg width="{$buttonSize}" height="{$buttonSize}" viewBox="0 0 24 24" fill="none">
                    {$liIcon}
                </svg>
            </a>
HTML;
        }

        // YouTube
        if (!empty($youtubeUrl)) {
            $ytIcon = ($iconStyle === 'colored')
                ? '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" fill="#FF0000"/>'
                : '<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" fill="' . $currentColor . '"/>';

            $html .= <<<HTML

            <a href="{$youtubeUrl}" target="_blank" rel="noopener noreferrer" style="{$buttonStyle}" title="YouTube" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <svg width="{$buttonSize}" height="{$buttonSize}" viewBox="0 0 24 24" fill="none">
                    {$ytIcon}
                </svg>
            </a>
HTML;
        }

        $html .= <<<HTML

        </div>
    </div>
</div>
HTML;

        return $html;
    }

    /**
     * Render Footer block
     */
    protected function renderFooter($content, $styles, $block)
    {
        $companyTitle = htmlspecialchars($content['companyTitle'] ?? '');
        $companyName = htmlspecialchars($content['companyName'] ?? 'La Tua Azienda');
        $companyDescription = htmlspecialchars($content['companyDescription'] ?? '');
        $linksTitle = htmlspecialchars($content['linksTitle'] ?? 'Link Utili');
        $links = $content['links'] ?? [];
        $contactTitle = htmlspecialchars($content['contactTitle'] ?? 'Contatti');
        $contactText = $content['contactText'] ?? ''; // HTML non escapato
        $copyright = htmlspecialchars($content['copyright'] ?? '© ' . date('Y') . ' La Tua Azienda. Tutti i diritti riservati.');
        $roundedClass = $this->getRoundedClass();

        // Gestisci fullWidth (default true per retrocompatibilità)
        $fullWidth = !isset($content['fullWidth']) || $content['fullWidth'] === true;

        // Stili di default
        $defaultBgColor = '#1F2937'; // gray-900
        $defaultTextColor = '#FFFFFF';

        if ($fullWidth) {
            // FullWidth: stili sul wrapper esterno, contenuto limitato
            $wrapperStyle = 'style="';
            $wrapperStyle .= 'background-color: ' . ($styles['backgroundColor'] ?? $defaultBgColor) . '; ';
            $wrapperStyle .= 'color: ' . ($styles['textColor'] ?? $defaultTextColor) . ';';
            $wrapperStyle .= '"';

            $containerClass = 'max-w-7xl mx-auto';

            // Stili interni: solo padding e font
            $innerStyles = [];
            if (!empty($styles['padding'])) {
                $innerStyles[] = 'padding: ' . $styles['padding'];
            }
            if (!empty($styles['fontFamily'])) {
                $innerStyles[] = 'font-family: \'' . $styles['fontFamily'] . '\', sans-serif';
            }
            $innerStyle = !empty($innerStyles) ? 'style="' . implode('; ', $innerStyles) . '"' : '';
        } else {
            // NON fullWidth: wrapper limitato, stili sul div interno
            $wrapperClass = 'max-w-7xl mx-auto';
            $wrapperStyle = '';
            $containerClass = '';

            // Tutti gli stili sul div interno
            $innerStyles = [];
            $innerStyles[] = 'background-color: ' . ($styles['backgroundColor'] ?? $defaultBgColor);
            $innerStyles[] = 'color: ' . ($styles['textColor'] ?? $defaultTextColor);
            if (!empty($styles['padding'])) {
                $innerStyles[] = 'padding: ' . $styles['padding'];
            }
            if (!empty($styles['fontFamily'])) {
                $innerStyles[] = 'font-family: \'' . $styles['fontFamily'] . '\', sans-serif';
            }
            $innerStyle = 'style="' . implode('; ', $innerStyles) . '"';
        }

        $footerClass = $fullWidth ? '' : $wrapperClass;

        $html = '<footer';
        if (!empty($footerClass)) {
            $html .= ' class="' . $footerClass . '"';
        }
        if (!empty($wrapperStyle)) {
            $html .= ' ' . $wrapperStyle;
        }
        $html .= '>';

        $html .= '<div class="px-6 py-12 ' . $containerClass . ' ' . $roundedClass . '" ' . $innerStyle . '>';
        $html .= '<div class="grid md:grid-cols-3 gap-8">';

        // Colonna 1: Info azienda
        $html .= '<div>';
        if (!empty($companyTitle)) {
            $html .= '<h4 class="text-lg font-semibold mb-4">' . $companyTitle . '</h4>';
        }
        $html .= '<h3 class="text-xl font-bold mb-4">' . $companyName . '</h3>';
        $html .= '<p class="text-gray-400 leading-relaxed">' . $companyDescription . '</p>';
        $html .= '</div>';

        // Colonna 2: Link utili
        $html .= '<div>';
        $html .= '<h4 class="text-lg font-semibold mb-4">' . $linksTitle . '</h4>';
        $html .= '<ul class="space-y-2">';
        foreach ($links as $link) {
            $linkText = htmlspecialchars($link['text'] ?? '');
            $linkUrl = htmlspecialchars($link['url'] ?? '#');
            $html .= '<li><a href="' . $linkUrl . '" class="text-gray-400 hover:text-white transition-colors">' . $linkText . '</a></li>';
        }
        $html .= '</ul></div>';

        // Colonna 3: Contatti
        $html .= '<div>';
        $html .= '<h4 class="text-lg font-semibold mb-4">' . $contactTitle . '</h4>';
        $html .= '<div class="text-gray-400 prose prose-sm prose-invert max-w-none">' . $contactText . '</div>';
        $html .= '</div>';

        $html .= '</div>'; // Close grid

        // Copyright
        $html .= '<div class="border-t border-gray-800 mt-8 pt-8 text-center">';
        $html .= '<p class="text-gray-400 text-sm">' . $copyright . '</p>';
        $html .= '</div>';

        $html .= '</div>'; // Close inner div
        $html .= '</footer>';

        return $html;
    }
}
