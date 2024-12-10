<?php

namespace pagopa\jirasnow;

class HTTPClient
{

    protected HTTPRequest $request;

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