<?php

namespace App\Controllers;

use App\Models\Page;
use App\Models\Block;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DebugController
{
    /**
     * Verifica lo stato del database e mostra i dati raw
     */
    public function checkDatabase(Request $request, Response $response)
    {
        $debug = [];

        // Versione MySQL
        try {
            $version = \Illuminate\Database\Capsule\Manager::select('SELECT VERSION() as version');
            $debug['mysql_version'] = $version[0]->version ?? 'Unknown';
        } catch (\Exception $e) {
            $debug['mysql_version'] = 'Error: ' . $e->getMessage();
        }

        // Conteggio record
        $debug['pages_count'] = Page::count();
        $debug['blocks_count'] = Block::count();

        // Ultima pagina con tutti i suoi blocchi (raw)
        $lastPage = Page::with('blocks')->orderBy('id', 'desc')->first();

        if ($lastPage) {
            $debug['last_page'] = [
                'id' => $lastPage->id,
                'title' => $lastPage->title,
                'slug' => $lastPage->slug,
                'styles_raw' => $lastPage->getAttributes()['styles'] ?? null, // Valore raw dal database
                'styles_parsed' => $lastPage->styles, // Valore dopo il cast
            ];

            // Blocchi della pagina
            $debug['last_page_blocks'] = [];
            foreach ($lastPage->blocks as $block) {
                $debug['last_page_blocks'][] = [
                    'id' => $block->id,
                    'type' => $block->type,
                    'order' => $block->order,
                    'styles_raw' => $block->getAttributes()['styles'] ?? null, // Raw dal DB
                    'styles_parsed' => $block->styles, // Dopo il cast
                    'content_raw' => $block->getAttributes()['content'] ?? null,
                    'content_parsed' => $block->content,
                ];
            }
        }

        $response->getBody()->write(json_encode($debug, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Test endpoint per verificare cosa viene ricevuto in una richiesta di update
     */
    public function testUpdate(Request $request, Response $response)
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        $debug = [
            'received_raw_body' => $body,
            'received_parsed' => $data,
            'blocks_in_request' => isset($data['blocks']) ? count($data['blocks']) : 0,
        ];

        // Mostra il primo blocco se esiste
        if (isset($data['blocks'][0])) {
            $debug['first_block_example'] = [
                'type' => $data['blocks'][0]['type'] ?? null,
                'has_styles' => isset($data['blocks'][0]['styles']),
                'styles_value' => $data['blocks'][0]['styles'] ?? null,
                'styles_type' => isset($data['blocks'][0]['styles']) ? gettype($data['blocks'][0]['styles']) : null,
            ];
        }

        $response->getBody()->write(json_encode($debug, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
