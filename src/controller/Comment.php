<?php

namespace pagopa\jirasnow\controller;

use pagopa\jirasnow\JiraTicket;
use pagopa\jirasnow\snow\ServiceNowAPI;
use pagopa\jirasnow\snow\ServiceNowApiException;

/**
 * Controller che si occupa di gestire le chiamate nel contesto /comment
 * Parametri di input per le chiamate API<br>
 * Dati input
 * <code>
 *     {
 *          "ticket_id": <jira_ticket>,
 *          "comment": <comment>
 *     }
 * </code>
 *
 *
 * Dati di output per chiamate OK
 * <code>
 *     {
 *          "status": "OK",
 *          "code": 200
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
class Comment extends AbstractController
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
    protected array $json_keys = ['ticket_id', 'comment'];


    /**
     * Inizio logica per chiamate con context /comment
     * @return void
     */
    public function init() : void
    {
        $data = json_decode($this->bootstrap->getPostData());
        $jira_ticket_id = $data->ticket_id;
        $comment = $data->comment;
        $this->comment($jira_ticket_id, $comment);
    }


    /**
     * Effettua l'assegnazione del ticket al team ServiceNow
     * @param string $jira_ticket_id
     * @param string $comment
     * @return void
     */
    public function comment(string $jira_ticket_id, string $comment) : void
    {

        try {
            $jira_ticket = new JiraTicket($jira_ticket_id);
            $ticket_snow = $jira_ticket->getServiceNowId();

            $serviceNowAPI = new ServiceNowAPI();
            $serviceNowAPI->commentTicket($ticket_snow, $comment);
            $output = [
                'status' => 'OK',
                'code' => 200
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