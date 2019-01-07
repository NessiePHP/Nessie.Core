<?php

namespace Nessie\Core;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Aura\Payload\Payload;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\PsrLogMessageProcessor;


class Config extends ContainerConfig
{
    public function define(Container $di)
    {
        $di->set('nessie/project:router', $di->lazyNew('Aura\Router\RouterContainer'));
        $di->set('nessie/project:router.map', function () use ($di) {
            return $di->get('nessie/project:router')->getMap();
        });

        $router = new  \Aura\Router\RouterContainer;

        $router->setLoggerFactory(function () {
            $log = new Logger('routes');
            $log->pushProcessor(new PsrLogMessageProcessor);
            $handler = new ErrorLogHandler(ErrorLogHandler::SAPI, Logger::DEBUG);
            $log->pushHandler($handler);
            return $log;
        });

        $di->set('nessie/project:router',$router);
        $di->set('nessie/project:relay', function () use ($di) {
            return new \Relay\Relay($this->getMiddleware($di));
        });
    }

    public function modify(Container $di)
    {
        // todo env value here | Don't setup welcome route if in production
        if ($prod ?? 0) {
            return;
        }

        $map = $di->get('nessie/project:router.map');

        $map->get('welcome.to.nessie', '/', function ($request) {
            $payload = new Payload;
            $payload->setStatus(\Aura\Payload_Interface\PayloadStatus::FOUND);

            return (new class extends Responder {
                public function found()
                {
                    $this->response->getBody()->write(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'welcome.html'));
                }
            })->__invoke($request, $payload);
        });
    }

    protected function getMiddleware(Container $di): array
    {
        $middle = [
            new \Middlewares\AuraRouter($di->get('nessie/project:router')),
            new \Middlewares\RequestHandler(),
        ];

        return $middle;
    }
}
