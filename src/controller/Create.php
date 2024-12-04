<?php

namespace pagopa\jirasnow\controller;

use pagopa\jirasnow\Bootstrap;
use pagopa\jirasnow\BootstrapException;

class Create extends AbstractController
{


    /**
     * @inheritdoc
     * @var array|string[]
     */
    protected array $http_method_allowed = ['POST'];

    /**
     * @inheritdoc
     * @var array|string[]
     */
    protected array $json_keys = ['action' , 'jira_ticket'];


    public function init()
    {
        // da qui, sono stati già fatti tutti i check su
        // context: /create
        // method: POST
        // json keys presenti: action, ticket

        $data = json_decode($this->bootstrap->getPostData());
        $ticket_id = $data->jira_ticket;
        $this->bootstrap->setPayload(['ticket_id' => $ticket_id]);
    }

}