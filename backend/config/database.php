<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Events\Dispatcher;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// Dispatcher degli eventi: senza, Eloquent standalone non emette gli eventi dei modelli
// (creating, updated, deleted...) su cui si basa il registro attività
$capsule->setEventDispatcher(new Dispatcher(new Container));

$capsule->setAsGlobal();

// Gestore delle transazioni: permette di rimandare un'operazione a dopo il commit
// (afterCommit), così il registro non riporta modifiche annullate da un rollback
$capsule->getConnection()->setTransactionManager(new DatabaseTransactionsManager);
$capsule->bootEloquent();

return $capsule;
