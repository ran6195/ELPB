<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;

/**
 * Landing Pages API Helper
 */
class LandingPagesHelper
{
    /** Identifica sito e versione nelle chiamate al backend (registro renderer, dalla v2.1) */
    const RENDERER_NAME = 'joomla';
    const RENDERER_VERSION = '2.1.0';

    /**
     * Get component parameters
     *
     * @return \Joomla\Registry\Registry
     */
    public static function getParams()
    {
        return ComponentHelper::getParams('com_landingpages');
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public static function getApiUrl()
    {
        $params = self::getParams();
        return rtrim($params->get('api_url', 'http://localhost:8000/api'), '/');
    }

    /**
     * Get API token
     *
     * @return string
     */
    public static function getApiToken()
    {
        $params = self::getParams();
        return $params->get('api_token', '');
    }

    /**
     * Header con sito, URL (senza query string) e versione del renderer:
     * il backend registra dove viene mostrata ogni pagina
     *
     * @return  array
     */
    public static function getRendererHeaders()
    {
        $uri = \Joomla\CMS\Uri\Uri::getInstance();

        return [
            'X-ELPB-Host' => $uri->getHost(),
            'X-ELPB-Url' => $uri->toString(['scheme', 'host', 'port', 'path']),
            'X-ELPB-Renderer' => self::RENDERER_NAME . '/' . self::RENDERER_VERSION,
        ];
    }

    /**
     * Script che segnala una visita al backend: una sola richiesta per pagina vista,
     * senza cookie né dati salvati nel browser. text/plain evita la richiesta preliminare CORS.
     *
     * @param   int  $pageId  ID della pagina
     *
     * @return  string
     */
    public static function getViewBeacon($pageId)
    {
        if (empty($pageId)) {
            return '';
        }
        $url = json_encode(self::getApiUrl() . '/track/view', JSON_UNESCAPED_SLASHES);
        $id = (int) $pageId;

        return <<<HTML
<script>
(function () {
    try {
        if (navigator.webdriver) return;
        var data = JSON.stringify({ page_id: {$id}, host: location.hostname });
        var url = {$url};
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([data], { type: 'text/plain' }));
        } else {
            fetch(url, { method: 'POST', body: data, keepalive: true, mode: 'no-cors', headers: { 'Content-Type': 'text/plain' } });
        }
    } catch (e) {}
})();
</script>
HTML;
    }

    /**
     * Make HTTP request to API
     *
     * @param   string  $endpoint  API endpoint (e.g., 'page/my-slug')
     * @param   string  $method    HTTP method (GET, POST, etc.)
     * @param   array   $data      Request data for POST/PUT
     *
     * @return  mixed   Response data or false on error
     */
    public static function apiRequest($endpoint, $method = 'GET', $data = null)
    {
        $url = self::getApiUrl() . '/' . ltrim($endpoint, '/');
        $token = self::getApiToken();

        try {
            $http = HttpFactory::getHttp();
            $headers = array_merge([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], self::getRendererHeaders());

            if (!empty($token)) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            // Set timeout to 10 seconds
            $timeout = 10;

            if ($method === 'GET') {
                $response = $http->get($url, $headers, $timeout);
            } elseif ($method === 'POST') {
                $response = $http->post($url, json_encode($data), $headers, $timeout);
            } else {
                throw new \RuntimeException('Unsupported HTTP method: ' . $method);
            }

            if ($response->code >= 200 && $response->code < 300) {
                return json_decode($response->body, true);
            }

            Factory::getApplication()->enqueueMessage(
                'API Error: ' . $response->code . ' - ' . $response->body,
                'error'
            );
            return false;

        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage(
                'API Connection Error: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }

    /**
     * Get page by slug (with caching)
     *
     * @param   string  $slug  Page slug
     *
     * @return  mixed   Page data or false
     */
    public static function getPageBySlug($slug)
    {
        $params = self::getParams();
        $cacheEnabled = $params->get('cache_enabled', 1);
        $cacheTime = (int) $params->get('cache_time', 300);

        if ($cacheEnabled) {
            $cache = Factory::getCache('com_landingpages', 'output');
            $cache->setCaching(true);
            $cache->setLifeTime($cacheTime);

            $cacheId = 'page_' . md5($slug);

            $data = $cache->get($cacheId);

            if ($data === false) {
                $data = self::apiRequest('page/' . $slug);
                if ($data !== false) {
                    $cache->store(serialize($data), $cacheId);
                }
            } else {
                $data = unserialize($data);
            }

            return $data;
        }

        return self::apiRequest('page/' . $slug);
    }

    /**
     * Submit lead form
     *
     * @param   array  $leadData  Lead data
     *
     * @return  mixed  Response or false
     */
    public static function submitLead($leadData)
    {
        return self::apiRequest('leads', 'POST', $leadData);
    }

    /**
     * Get all pages
     *
     * @return  mixed  Pages list or false
     */
    public static function getAllPages()
    {
        return self::apiRequest('pages');
    }
}
