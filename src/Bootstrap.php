<?php

namespace pagopa\jirasnow;

class Bootstrap
{

    /**
     * Contiene la url richiamata (solo il context, senza host, port, schema, etc)
     * @var string
     */
    protected string $uri;

    public function __construct()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->uri = $uri;
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
            $controllerInstance = new $class();
            $controllerInstance->init();
        }
        else
        {
            header('Non trovata', true, 404);
        }

    }

}