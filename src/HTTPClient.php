<?php

namespace pagopa\jirasnow;

/**
 * Questa classe funge da semplice wrapper di due classi, pagopa\jirasnow\HTTPRequest, pagopa\jirasnow\HTTPResponse,
 * tentando quindi di fornire un client https minimale adatto allo scopo del progetto
 */
class HTTPClient
{

    /**
     * Classe che serve a gestire la richiesta
     * @var HTTPRequest
     */
    protected HTTPRequest $request;

    /**
     * Classe che serve a gestire la risposta a seguito di una richiesta
     * @var HTTPResponse
     */
    protected HTTPResponse $response;

    public function __construct(string $url, string $method = 'GET', array $headers = [], string $payload = '')
    {
        $this->request = new HTTPRequest($url, $method, $headers, $payload);
    }

    /**
     * Restituisce la classe HTTPRequest
     * @return HTTPRequest
     */
    public function getRequest(): HTTPRequest
    {
        return $this->request;
    }

    /**
     * Restituisce la classe HTTPResponse
     * @return HTTPResponse
     */
    public function getResponse(): HTTPResponse
    {
        return $this->response;
    }

    /**
     * Esegue la chiamata e memorizza la risposta
     * @return void
     */
    public function exec() : void
    {
        $this->request->run();
        $this->response = $this->request->getResponse();
    }

}