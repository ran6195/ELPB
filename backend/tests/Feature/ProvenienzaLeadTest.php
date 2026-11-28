<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\EmailService;
use Tests\TestCase;

/**
 * Provenienza della visita per il CRM Edysma: i renderer mandano UTM, click
 * ID e indirizzo della pagina insieme al form. Il backend li salva nei
 * metadata come gli altri campi (da lì il sync li porta al CRM), ma non li
 * mette tra gli "altri campi" della notifica.
 */
class ProvenienzaLeadTest extends TestCase
{
    public function test_utm_e_click_id_finiscono_nei_metadata(): void
    {
        $pageId = $this->createPage();

        $response = $this->request('POST', '/api/leads', [
            'page_id' => $pageId,
            'email' => 'visitatore@example.test',
            'citta' => 'Brescia',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'autunno',
            'gclid' => 'Cj0KCQ',
            'page_url' => 'https://www.cliente.it/promo',
        ], ['Origin' => 'https://www.cliente.it']);

        $this->assertSame(201, $response->getStatusCode());
        $metadata = Lead::where('email', 'visitatore@example.test')->firstOrFail()->metadata;
        $this->assertSame('google', $metadata['utm_source']);
        $this->assertSame('autunno', $metadata['utm_campaign']);
        $this->assertSame('Cj0KCQ', $metadata['gclid']);
        $this->assertSame('https://www.cliente.it/promo', $metadata['page_url']);
        $this->assertSame('Brescia', $metadata['citta']);
    }

    public function test_la_notifica_non_mostra_la_provenienza(): void
    {
        $campi = EmailService::campiExtra([
            'citta' => 'Brescia',
            'privacy_accepted' => true,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'autunno',
            'utm_term' => 'cucine',
            'utm_content' => 'annuncio-1',
            'gclid' => 'Cj0KCQ',
            'fbclid' => 'IwAR',
            'page_url' => 'https://www.cliente.it/promo',
            '_notes' => [],
        ]);

        $this->assertSame(['citta' => 'Brescia'], $campi);
    }
}
