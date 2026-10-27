<?php

use App\Audit\Auditor;

$app = require __DIR__ . '/../bootstrap/app.php';

// Registro attività: le voci si scrivono dopo l'invio della risposta. La funzione di
// chiusura copre anche gli errori fatali (flush non fa nulla se il buffer è vuoto).
register_shutdown_function(fn() => Auditor::get()->flush());

$app->run();

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
Auditor::get()->flush();
