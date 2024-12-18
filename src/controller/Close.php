<?php

namespace pagopa\jirasnow\controller;

use pagopa\jirasnow\JiraTicket;
use pagopa\jirasnow\snow\ServiceNowAPI;
use pagopa\jirasnow\snow\ServiceNowApiException;

/**
 * Controller che si occupa di gestire le chiamate nel contesto /assign
 * Parametri di input per le chiamate API<br>
 * Dati input
 * <code>
 *     {
 *          "ticket_id": <jira_ticket>,
 *     }
 * </code>
 *
 *
 * Dati di output per chiamate OK
 * <code>
 *     {
 *          "status": "OK",
 *          "code": 200,
 *          "jira_ticket": <ticket_id>,
 *          "ticket_id": <snow_ticket>,
 *          "ticket_cs": <snow_ticket_cs>
 *     }
 * </code>
 *
 * Dati di output per chiamate KO
 * <code>
 *     {
 *          "status": "KO",
 *          "code": <errorCode>,
 *          "msg": <errorMsg>
 *     }
 * </code>
 */
class Close extends AbstractController
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
    protected array $json_keys = ['ticket_id'];


    /**
     * Inizio logica per chiamate con context /close
     * @return void
     */
    public function init() : void
    {
        $data = json_decode($this->bootstrap->getPostData());
        $ticket_id = $data->ticket_id;
        $this->close($ticket_id);
    }


    /**
     * Effettua l'assegnazione del ticket al team ServiceNow
     * @param string $ticket_id
     * @return void
     */
    public function close(string $ticket_id) : void
    {
        /**
         * {
         * "account": "<account-CN>>",
         * "ticket_id": "<jira_ticket_id>",
         * "comments": "Ticket Chiuso"
         * }
 */
        try {
            $jira_ticket = new JiraTicket($ticket_id);
            $ticket_snow = $jira_ticket->getServiceNowId();
            $ticket_snow_cs = $jira_ticket->getServiceNowNumber();

            $serviceNowAPI = new ServiceNowAPI();
            $serviceNowAPI->closeTicket($ticket_snow);
            $output = [
                'status' => 'OK',
                'code' => 200,
                'jira_ticket' => $ticket_id,
                'ticket_id' => $ticket_snow,
                'ticket_cs' => $ticket_snow_cs
            ];
        }
        catch(ServiceNowApiException $e)
        {
            $output = [
                'status' => 'KO',
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        $this->bootstrap->setPayload($output);
    }

 }