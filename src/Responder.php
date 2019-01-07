<?php
namespace Nessie\Core;

use Aura\Payload\Payload;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Responder
{
    protected $request;

    protected $response;

    protected $payload;

    public function __invoke(Request $request, Payload $payload) : Response
    {
        $this->request = $request;
        $this->payload = $payload;
        $this->response = $this->newResponse();

        $method = $this->getMethodForPayload();
        $this->$method();
        return $this->response;
    }

    protected function newResponse()
    {
        return new Response();
    }

    protected function getMethodForPayload() : string
    {
        $method = str_replace('_', '', strtolower($this->payload->getStatus()));
        return method_exists($this, $method) ? $method : 'notRecognized';
    }

    protected function notRecognized() : void
    {
        $domain_status = $this->payload->getStatus();
        $this->buildJsonErrorObject(500, null, 'Unknown payload', "Unknown domain payload status: '$domain_status'");
    }

    protected function notFound() : void
    {
        $this->buildJsonErrorObject(404, null, '404 Not Found', '404 Not Found');
    }

    protected function error() : void
    {
        $e = $this->payload->getResult()['exception'];

        $this->buildJsonErrorObject(500, null, 'Exception', $e->getMessage());
    }

    protected function buildJsonErrorObject(int $status, $source = null, $title = null, $detail = null) : void
    {
        $this->response = $this->response->withContentType('application/vnd.api+json');
        $this->response = $this->response->withStatus($status);

        $body = [
            'errors' => new class($status, $source, $title, $detail) {
                public function __construct($status, $source, $title, $detail)
                {
                    $this->status  = (integer) $status;
                    $this->source = new class { public function __construct($source) { $this->pointer = $source; }};
                    $this->title = $title;
                    $this->detail = $detail;
                }
            }
        ];
        $this->response->getBody()->write(json_encode($body));
    }
}
