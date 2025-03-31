<?php

namespace pagopa\jirasnow;

use CurlHandle;

/**
 * Classe che si occupa di preparare una resource CurlHandle per effettuare
 * chiamate HTTP(s) verso un determinato URL
 * Può gestire header di richiesta e mutua autenticazione, oltre a poter lavorare
 * con un forwarder pagoPA
 */
class HTTPRequest
{

    /**
     * Risorsa per la gestione delle chiamate http tramite libreria cURL di PHP
     * @var CurlHandle
     */
    protected CurlHandle $httpClient;


    /**
     * Contiene la URL da chiamare
     * @var string
     */
    protected string $url;


    /**
     * Contiene il metodo da utilizzare per la chiamata
     * Supporta solo GET e POST
     * @var string
     */
    protected string $method;


    /**
     * Contiene il payload da inviare
     * @var string
     */
    protected string $payload;


    /**
     * Configura il client per usare la client authentications
     * Se true è necessario indicare private & public key
     * @var bool
     */
    protected bool $use_mTLS = false;


    /**
     * Chiave privata del certificato, in formato stringa
     * @var string
     */
    protected string $certificate_key;


    /**
     * Certificato pubblico, in formato stringa
     * @var string
     */
    protected string $certificate_public;


    /**
     * Certificate format. Default to PEM
     * @var string
     */
    protected string $certificate_format = 'PEM';


    /**
     * Contiene la lista degli headers da inviare
     * @var array
     */
    protected array $headers = [];


    /**
     * Indica se usare o meno il forwarder indicato nella variabile d'ambiente FORWARDER_URL
     * @var bool
     */
    protected bool $useForwarder = false;


    /**
     * Url del forwarder pagoPA. Utilizzato solo se $useForwarder = true
     * @var string
     */
    protected string $forwarderUrl;


    /**
     * Subscription Key del forwarder
     * @var string
     */
    protected string $forwarderSub;


    /**
     * Contiene la Response alla Request
     * @var HTTPResponse
     */
    protected HTTPResponse $response;


    /**
     * Contiene i dati da inviare nel caso in cui ci sia da fare una post
     * @var array
     */
    protected array $postFields = array();


    /**
     * Lista dei file da inviare
     * @var array
     */
    protected array $postFiles = array();


    /**
     * Attiva o disattiva la verifica della catena di certificazione
     * @var bool
     */
    protected bool $verifyPeer = false;


    public function __construct(string $url, string $method = 'GET', array $headers = [], string $payload = '')
    {
        $this->setUrl($url);
        $this->headers = $headers;
        if (!empty($payload))
        {
            $this->payload = $payload;
        }
        $this->method = $method;

        $this->useForwarder = (empty(Config::get('USE_PAGOPA_FORWARDER'))) ? false : filter_var(Config::get('USE_PAGOPA_FORWARDER'), FILTER_VALIDATE_BOOLEAN);
        $this->forwarderUrl = (empty(Config::get('FORWARDER_URL'))) ? "" : Config::get('FORWARDER_URL');
        $this->forwarderSub = (empty(Config::get('FORWARDER_SUBSCRIPTION_KEY'))) ? "" : Config::get('FORWARDER_SUBSCRIPTION_KEY');


        $this->certificate_format = (empty(Config::get('CERTIFICATE_FORMAT'))) ? 'PEM' : Config::get('CERTIFICATE_FORMAT');
        $this->use_mTLS = (empty(Config::get('USE_MTLS'))) ? false : filter_var(Config::get('USE_MTLS'), FILTER_VALIDATE_BOOLEAN);
        $this->certificate_key = (empty(Config::get('CERTIFICATE_KEY'))) ? "" : Config::get('CERTIFICATE_KEY');
        $this->certificate_public = (empty(Config::get('CERTIFICATE_PUBLIC'))) ? "" : Config::get('CERTIFICATE_PUBLIC');

        $this->verifyPeer = (empty(Config::get('VERIFY_CHAIN'))) ? false : Config::get('VERIFY_CHAIN');
    }

    /**
     * Configura la URL da chiamare
     * @param string $url
     * @return void
     */
    public function setUrl(string $url) : void
    {
        $this->url = $url;
    }

    /**
     * Restituisce la URL da chiamare
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Configura un header con rispettivo valore da inviare
     * Se l'header già esiste, viene sovrascritto
     * @param string $header_name
     * @param string $header_value
     * @return void
     */
    public function setHeader(string $header_name, string $header_value) : void
    {
        $this->headers[$header_name] = $header_value;
    }

    /**
     * Restituisce il valore di un header da inviare
     * Se non esiste l'header, restituisce null
     * @param string $header_name
     * @return string|null
     */
    public function getHeader(string $header_name) : string|null
    {
        return (array_key_exists($header_name, $this->headers)) ? $this->headers[$header_name] : null;

    }

    /**
     * Rimuove un header esistente dalla lista degli header pronti per l'invio della richiesta
     * @param string $header_name
     * @return void
     */
    public function removeHeader(string $header_name) : void
    {
        if ($this->hasHeader($header_name))
        {
            unset($this->headers[$header_name]);
        }
    }

    /**
     * Restituisce true/false se un header esiste o meno
     * @param string $header_name
     * @return bool
     */
    public function hasHeader(string $header_name) : bool
    {
        return array_key_exists($header_name, $this->headers);
    }


    /**
     * Configura GET come metodo
     * @return void
     */
    public function setGetMethod() : void
    {
        $this->method = 'GET';
    }

    /**
     * Configura Post come metodo
     * @return void
     */
    public function setPostMethod() : void
    {
        $this->method = 'POST';
    }

    /**
     * Restituisce il metodo impostato
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }


    /**
     * Prepara la chiamata
     * @return void
     */
    public function prepare() : void
    {
        $url = $this->url;
        $parse_url = parse_url($url);

        if ($this->useForwarder)
        {
            // uso il forwarder
            $host = $parse_url['host'];
            $port = 443;
            if (!array_key_exists('port', $parse_url))
            {
                $port = ($parse_url['scheme'] == 'https') ? 443 : 80;
            }
            $path = array_key_exists('path', $parse_url) ? $parse_url['path'] : '/';
            $qs = (array_key_exists('query', $parse_url)) ? sprintf('?%s', http_build_query($parse_url['query'], "", null, PHP_QUERY_RFC3986)) : '';
            $url = sprintf('%s%s', Config::get('FORWARDER_URL') , $qs); // se chiamo il forwarder, la url da chiamare sarà formata dalla url del forwarder + le QueryString della url da reale

            $this->setHeader('X-Host-Url', $host);
            $this->setHeader('X-Host-Port', $port);
            $this->setHeader('X-Host-Path', $path);
            $this->setHeader('Ocp-Apim-Subscription-Key', Config::get('FORWARDER_SUBSCRIPTION_KEY'));
        }

        $this->httpClient = curl_init($url);
        curl_setopt($this->httpClient, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->httpClient, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->httpClient, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
//        curl_setopt($this->httpClient, CURLOPT_HEADER, true);
        if ($this->method == 'POST')
        {
            curl_setopt($this->httpClient, CURLOPT_POST, true);
        }

        $curlHeaders = [];
        foreach($this->headers as $header_name => $header_value)
        {
            $curlHeaders[] = sprintf('%s: %s', $header_name, $header_value);
        }
        curl_setopt($this->httpClient, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($this->httpClient, CURLOPT_TIMEOUT, 30);
        if ($this->use_mTLS)
        {
            curl_setopt($this->httpClient, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($this->httpClient, CURLOPT_SSLCERTTYPE, 'PEM');

            curl_setopt($this->httpClient, CURLOPT_SSLKEY_BLOB, sprintf('-----BEGIN PRIVATE KEY-----
%s
-----END PRIVATE KEY-----', Config::get('CERTIFICATE_KEY')));

            curl_setopt($this->httpClient, CURLOPT_SSLCERT_BLOB, sprintf('-----BEGIN CERTIFICATE-----
%s
-----END CERTIFICATE-----', Config::get('CERTIFICATE_PUBLIC')));

        }

        if ($this->method == 'POST')
        {
            if (count($this->postFiles) > 0)
            {
                foreach($this->postFiles as $file)
                {
                    curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, file_get_contents($file));
                }
            }
            else
            {
                $postData = $this->postFields;
                if ($this->getHeader('Content-Type') == 'application/x-www-form-urlencoded')
                {
                    $postData = http_build_query($this->postFields);
                }
                if ($this->getHeader('Content-Type') == 'application/json')
                {
                    $postData = json_encode($postData);
                }
                curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, $postData);
            }
        }
    }


    /**
     * Prepara la chiamata e la esegue
     * @return void
     */
    public function run() : void
    {
        $this->prepare();
        $this->response = new HTTPResponse($this->httpClient);
    }



    /**
     * Restituisce l'oggetto HTTPResponse per gestire la risposta
     * @return HTTPResponse
     */
    public function getResponse() : HTTPResponse
    {
        return $this->response;
    }


    /**
     * Configura un campo ed il suo valore per le chiamate post. Se value è un file fisico, lo inserisce nella lista dei file
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setPostField(string $name, string $value) : self
    {
        if (file_exists($value))
        {
            $this->postFiles[$name] = $value;
            return $this;
        }
        $this->postFields[$name] = $value;
        return $this;
    }


}