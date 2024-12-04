<?php

/**
 * La classe Bootstrap si occupa di gestire una request e fornire una response richiamando il controller corretto
 * in base al context della URL
 * La classe gestisce header e payload, ed è iniettata nella classe Controller
 */
namespace pagopa\jirasnow;

class Bootstrap
{

    /**
     * Contiene la url richiamata (solo il context, senza host, port, schema, etc)
     * @var string
     */
    protected string $uri;


    /**
     * Contiene i dati ricevuti da una chiamata POST
     * @var string
     */
    protected string $postdata;


    /**
     * Contiene gli headers da inviare verso il client
     * @var array
     */
    protected array $headers = array();


    /**
     * Http code to send
     * @var int
     */
    protected int $httpCode = 200;


    /**
     * Contiene i dati da inviare come risposta
     * @var mixed
     */
    protected mixed $response = null;




    public function __construct()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->uri = $uri;
    }


    /**
     * Restituisce i dati ricevuti da una chiamata POST
     * @return string
     */
    public function getPostData() : string
    {
        if (empty($this->postdata))
        {
            $this->postdata = file_get_contents('php://input');
        }
        return $this->postdata;
    }
    /**
     * Esegue il controller corretto in base al context
     * @return void
     */
    public function run()
    {
        $controller = str_replace('/', '', $this->uri);
        $class = sprintf('%s\%s', 'pagopa\jirasnow\controller', ucfirst($controller));
        if (class_exists($class))
        {
            $controllerInstance = new $class($this);
            $controllerInstance->init();
        }
        else
        {
            header('HTTP/1.0 404 Not Found', true, 404);
            header('Content-Type: text/json', true);
            $json = json_encode(array('code' => 404, 'message' => 'Action not found'));
            echo $json;
            exit;
        }
    }

    /**
     * Configura un header da inviare in fase di risposta
     * @param string $header_name
     * @param string $header_value
     * @return self
     */
    public function setHeader(string $header_name, string $header_value) : self
    {
        $this->headers[$header_name] = $header_value;
        return $this;
    }

    /**
     * Restituisce il valore di un header
     * @param string $header_name
     * @return string|null
     */
    public function getHeader(string $header_name) : string|null
    {
        return (array_key_exists($header_name, $this->headers)) ? $this->headers[$header_name] : null;
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
     * Configura l'header Content-Type per inviare un json di risposta
     * @return $this
     */
    public function setContentTypeJson() : self
    {
        $this->setHeader('Content-Type', 'application/json');
        return $this;
    }

    /**
     * Restituisce true/false se è stato configurato il content-type della risposta come application/json
     * @return bool
     */
    public function isOutputJson() : bool
    {
        return ($this->getHeader('Content-Type') == 'application/json');
    }

    /**
     * Configura il payload
     * @param mixed $payload
     * @return self
     */
    public function setPayload(mixed $payload) : self
    {
        $this->response = $payload;
        return $this;
    }


    /**
     * Invia gli headers configurati. Se esiste un header Location viene inviato per primo
     * @return self
     */
    private function sendHeader() : self
    {
        $headers = $this->headers;
        if ($this->hasHeader('Location'))
        {
            header('Location: ' . $this->getHeader('Location'), true, 302);
        }
        unset($headers['Location']);
        foreach($headers as $header_name => $header_value)
        {
            header($header_name . ': ' . $header_value);
        }
        return $this;
    }

    /**
     * Invia l'output al client. Se si tratta di output in json, effettua la codifica
     * @return self
     */
    private function sendPayload() : self
    {
        if ($this->isOutputJson())
        {
            echo json_encode($this->response);
        }
        else
        {
            echo $this->response;
        }
        return $this;
    }


    /**
     * Invia header e payload insieme
     * @return void
     */
    public function send()
    {
        $this->sendHeader()->sendPayload();
    }

}