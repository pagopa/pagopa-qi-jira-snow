<?php

namespace pagopa\jirasnow;
use CurlHandle;

class HTTPResponse
{

    /**
     * Contiene la resource alla request per ricavare tutti i dati della risposta
     * @var CurlHandle
     */
    protected CurlHandle $request;


    /**
     * Contiene il payload della risposta.
     * @var string
     */
    protected string $response;


    /**
     * Contiene gli headers della risposta
     * @var array
     */
    protected array $headers = array();



    protected int $errno;

    protected string $errmsg;


    /**
     * Riceve in ingresso una CurlHandle resource non ancora avviata
     * La avvia e salva la risposta (header & body)
     * @param CurlHandle $request
     */
    public function __construct(CurlHandle $request)
    {
        $this->request = $request;
        curl_setopt($this->request, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                  return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        $this->response = curl_exec($this->request);
        $this->headers = $headers;

        $this->errno = curl_errno($this->request);
        $this->errmsg = curl_error($this->request);

        curl_close($this->request);
    }


    public function getError() : array
    {
        return ['errno' => $this->errno, 'errmsg' => $this->errmsg];
    }

    /**
     * Restituisce il payload della response
     * @return string
     */
    public function getResponseBody() : string
    {
        return $this->response;
    }

    /**
     * Restituisce l'http code della response
     * @return int
     */
    public function getCode() : int
    {
        return curl_getinfo($this->request, CURLINFO_RESPONSE_CODE);
    }

    /**
     * Restituisce la lista di tutti gli headers di risposta
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }


    /**
     * Restituisce il valore del singolo header $name. Se ci sono più header con il nome $name, restituisce
     * l'i-esimo alla posizione $position
     * @param string $name
     * @param int $position
     * @return string|null
     */
    public function getHeader(string $name, int $position = 0) : string|null
    {
        $hname = strtolower($name);
        if (array_key_exists($hname, $this->headers))
        {
            if (array_key_exists($position, $this->headers[$hname]))
            {
                return $this->headers[$hname][$position];
            }
            else
            {
                return null;
            }
        }
        return null;
    }
}