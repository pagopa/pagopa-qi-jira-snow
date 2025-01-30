<?php

namespace pagopa\jirasnow\controller;


/**
 * Controller che server a k8 per la disponibilità del servizio
 */
class Info extends AbstractController
{

    /**
     * @inheritdoc
     * @var array|string[]
     */
    protected array $http_method_allowed = ['GET'];

    /**
     * @inheritdoc
     * @var array|string[]
     */
    protected array $json_keys = [];


    /**
     * Inizio logica per chiamate con context /create
     * @return void
     */
    public function init() : void
    {
        $this->bootstrap->setPayload(['status' => 'OK']);
    }

 }