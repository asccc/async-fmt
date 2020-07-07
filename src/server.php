<?php

declare(strict_types=1);

/*
 * This file is part of the DevCom Async Code Formatter
 * (c) The dev-community.de authors
 */

namespace DevCom\Fmt;

use Amp\Http\Server\HttpServer;
use Amp\Loop;
use Amp\Socket\Server;
use DevCom\Fmt\Parser\PregParser;

require __DIR__ . '/../vendor/autoload.php';

Loop::run(function () {
    $sockets = [
        Server::listen('0.0.0.0:8080'),
        Server::listen('[::]:8080'),
    ];

    $server = new HttpServer(
        $sockets,
        new Handler(new PregParser()),
        new Logger()
    );

    yield $server->start();

    Loop::onSignal(SIGINT, function (string $id) use ($server) {
        Loop::cancel($id);
        yield $server->stop();
        echo PHP_EOL;
    });
});
