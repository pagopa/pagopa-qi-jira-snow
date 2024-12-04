<?php

namespace pagopa\jirasnow\controller;

use pagopa\jirasnow\Bootstrap;

abstract class AbstractController
{


    /**
     * Lista dei metodi permessi per il controller
     * @var array|string[]
     */
    protected array $http_method_allowed = ['POST', 'GET'];

    /**
     * Contiene le chiavi obbligatorie che devono essere presenti nel json della Request
     * @var array
     */
    protected array $json_keys = [];

    /**
     * Contiene l'istanza bootstrap che ha richiamato il controller
     * @var Bootstrap
     */
    protected Bootstrap $bootstrap;

    /**
     * Costruttore
     * @param Bootstrap $bootstrap
     * @throws ControllerException
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->checkMethod();
        $this->checkData();
        $this->bootstrap->setContentTypeJson();
    }

    /**
     * Restituisce il metodo della richiesta
     * @return string
     */
    public function getMethod() : string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Effettua il check del metodo. Se non permesso, lancia una exception
     * @throws ControllerException
     */
    private function checkMethod() : void
    {
        $method = $this->getMethod();
        if (!in_array($method, $this->http_method_allowed))
        {
            throw new ControllerException('Method not allowed');
        }
    }

    /**
     * Verifica che le chiavi nel payload siano presenti e corrette e che l'azione sia di create ticket
     * @return void
     * @throws ControllerException
     */
    private function checkData() : void
    {
        $post = $this->bootstrap->getPostData();
        $data = (array) json_decode($post);
        $keyExists = $this->json_keys;
        $diff_array = array_intersect_key(array_flip($keyExists), $data);

        if (count($diff_array) !== count($keyExists))
        {
            $diff_keys = implode(', ', array_keys(array_diff_key(array_flip($keyExists), $data)));
            throw new ControllerException(sprintf('Field(s) not found: %s', $diff_keys));
        }
    }

}