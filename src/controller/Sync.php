<?php

namespace pagopa\jirasnow\controller;

use DateMalformedStringException;
use DateTime;
use JiraRestApi\JiraException;
use pagopa\jirasnow\JiraTicket;
use pagopa\jirasnow\snow\ServiceNowApiException;

/**
 * Controller che si occupa di gestire le chiamate nel contesto /sync
 * Parametri di input per le chiamate API<br>
 * Dati input
 * <code>
 *     {
 *          "ticket_id": <jira_ticket>,
 *          "since" : <datetime>
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
 *          "ticket_cs": <snow_ticket_cs>,
 *          "since" : <datetime>,
 *          "attached_count_file" : <integer>,
 *          "upload_status" : [
 *                {
 *                  "local_file": <local-file>,
 *                  "jira_file": <jira-file>,
 *                  "jira_size_bytes": <jira-bytes-file>,
 *                  "upload_status": {
 *                      "size_bytes": <upload-bytes>,
 *                      "code": <status-code>,
 *                      "status": "OK"
 *                }
 *              }
 *          ]
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
class Sync extends AbstractController
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
     * @throws JiraException
     * @throws DateMalformedStringException
     */
    public function init() : void
    {
        $data = json_decode($this->bootstrap->getPostData());
        $ticket_id = $data->ticket_id;
        $since = null;
        if (isset($data->since))
        {
            $since = new DateTime($data->since);
            $this->sync($ticket_id, $since);
        }
        else
        {
            $this->sync($ticket_id);
        }
    }

    /**
     * Effettua l'assegnazione del ticket al team ServiceNow
     * @param string $ticket_id
     * @param DateTime|null $since
     * @return void
     * @throws JiraException
     */
    public function sync(string $ticket_id, DateTime $since = null) : void
    {
        try {
            $jira_ticket = new JiraTicket($ticket_id);
            $ticket_snow = $jira_ticket->getServiceNowId();
            $ticket_snow_cs = $jira_ticket->getServiceNowNumber();
            $download_info = $jira_ticket->syncAttach($since);
            $attached_count_file = (is_array($download_info)) ? count($download_info) : 0;

            $output = [
                'status' => 'OK',
                'code' => 200,
                'jira_ticket' => $ticket_id,
                'ticket_id' => $ticket_snow,
                'ticket_cs' => $ticket_snow_cs,
                'since' => (is_null($since)) ? $jira_ticket->getLastSent()->format('Y-m-d H:i:s') : $since->format('Y-m-d H:i:s'),
                'attached_count_file' => $attached_count_file,
                'upload_status' => $download_info,
            ];

            if ($attached_count_file > 0)
            {
                $jira_ticket->setLastSent(new DateTime());
            }
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