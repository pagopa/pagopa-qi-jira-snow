<?php

namespace pagopa\jirasnow\snow;

use pagopa\jirasnow\Config;
use pagopa\jirasnow\HTTPClient;
use pagopa\jirasnow\JiraTicket;

class ServiceNowAPI
{

    /**
     * Contiene il client (request & response) per le chiamate API
     * @var HTTPClient
     */
    protected HTTPClient $client;


    /**
     * Contiene la URL API per la richiesta di un nuovo token
     * @var string|mixed
     */
    protected string $urlGetToken;


    /**
     * Contiene la url API per la creazione di un ticket
     * @var string|mixed
     */
    protected string $urlCreateTicket;


    /**
     * Contiene la url API per la chiusura di un ticket
     * @var string|mixed
     */
    protected string $urlCloseTicket;


    /**
     * Contiene la url API per l'assegnazione di un ticket
     * @var string|mixed
     */
    protected string $urlAssignTicket;


    /**
     * Contiene la url API per l'aggiunta di un commento
     * @var string|mixed
     */
    protected string $urlCommentTicket;


    /**
     * Contiene il token per effettuare le richieste
     * @var string
     */
    protected string $oauthToken;


    /**
     * Contiene il client id per l'autenticazione
     * @var string
     */
    protected string $clientId;

    /**
     * Contiene il client secret per l'autenticazione
     * @var string
     */
    protected string $clientSecret;

    /**
     * Contiene l'account CN
     * @var string
     */
    protected string $accountCn;


    /**
     * Restituisce il ticket id creato (o configurato) in formato HEX (es. c938c6cc87ea9a508deceb573cbb355a)
     * @var string
     */
    protected string $ticket_id;


    /**
     * Restituisce il ticket number creato (o configurato) in formato CS*
     * @var string
     */
    protected string $ticket_number;



    public function __construct(string $ticket_number = null, string $ticket_id = null)
    {
        $this->urlGetToken = Config::get('SERVICE_NOW_URL_OAUTH_TOKEN');
        $this->urlCreateTicket = Config::get('SERVICE_NOW_URL_CREATE');
        $this->urlCloseTicket = Config::get('SERVICE_NOW_URL_CLOSE');
        $this->urlAssignTicket = Config::get('SERVICE_NOW_URL_ASSIGN');
        $this->urlCommentTicket = Config::get('SERVICE_NOW_URL_COMMENT');
        $this->clientId = Config::get('SERVICE_NOW_CLIENT_ID');
        $this->clientSecret = Config::get('SERVICE_NOW_CLIENT_SECRET');
        $this->accountCn = Config::get('SERVICE_NOW_ACCOUNT_CN');
        $this->setTicketNumber($ticket_number);
        $this->setTicketId($ticket_id);
    }


    /**
     * Configura il ticket number (in formato CS*)
     * @param string|null $ticket_number
     * @return $this
     */
    public function setTicketNumber(string $ticket_number = null) : self
    {
        if (!is_null($ticket_number))
        {
            $this->ticket_number = $ticket_number;
        }
        return $this;
    }

    /**
     * Restituisce il ticket number in formato CS*
     * @return string
     */
    public function getTicketNumber() : string
    {
        return $this->ticket_number;
    }

    /**
     * Configura il ticket_id (in formato HEX, es. c938c6cc87ea9a508deceb573cbb355a)
     * @param string|null $ticket_id
     * @return $this
     */
    public function setTicketId(string $ticket_id = null) : self
    {
        if (!is_null($ticket_id))
        {
            $this->ticket_id = $ticket_id;
        }
        return $this;
    }

    /**
     * Restituisce il ticket id (in formato HEX , es. c938c6cc87ea9a508deceb573cbb355a)
     * @return string
     */
    public function getTicketId() : string
    {
        return $this->ticket_id;
    }

    /**
     * Effettua la chiamata alla URL di getToken per ricevere un nuovo token
     * @return void
     * @throws ServiceNowApiException
     */
    public function refreshToken() : void
    {

        $this->client = new HTTPClient($this->urlGetToken, 'POST');
        $this->client->getRequest()->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->client->getRequest()->setPostField('grant_type', 'client_credentials');
        $this->client->getRequest()->setPostField('client_id', $this->clientId);
        $this->client->getRequest()->setPostField('client_secret', $this->clientSecret);

        $this->client->exec();

        $code = $this->client->getResponse()->getCode();
        if ($code == 200)
        {
            $response = json_decode($this->client->getResponse()->getResponseBody());
            $this->oauthToken = $response->access_token;
        }
        else
        {
            $response = json_decode($this->client->getResponse()->getResponseBody());
            $msg = sprintf('error code:%s, msg: %s', $response->errcode, $response->errmsg);
            throw new ServiceNowApiException($msg);
        }
    }

    /**
     * Restituisce il token per le chiamate. Se il token non è stato ancora richiesto, viene effettuato il fetch
     * del token
     * @return string
     */
    public function getToken()
    {
        if (empty($this->oauthToken))
        {
            $this->refreshToken();
        }
        return $this->oauthToken;
    }


    /**
     * @param string $jira_ticket
     * @param string $title
     * @param string $description
     * @param string $utype
     * @param string $business_service
     * @param string $request_area
     * @param int $priority
     * @throws ServiceNowApiException
     */
    public function createTicket(string $jira_ticket,
                                 string $title,
                                 string $description,
                                 string $utype,
                                 string $business_service,
                                 string $request_area,
                                 int $priority) : void
    {
        /**
         * {
         * "account":"CN-000004213",
         * "correlation_id":"{{issue.key}}",
         * "correlation_display":"{{issue.key}}",
         * "u_type":"{{utype}}",
         * "u_contact": "819c13addb0f74507dae3885f39619fd",
         * "priority": "{{issue.priority}}",
         * "short_description":"{{issue.summary}}",
         * "business_service":"{{businessservice}}",
         * "request_area":"{{requestarea}}",
         * "description": "{{issue.description.jsonEncode}}"
         * }
         *
         * application/json
         * Authorization Bearer {{OauthToken}}
         */

        $token = $this->getToken();
        $this->client = new HTTPClient($this->urlCreateTicket, 'POST');
        $this->client->getRequest()->setHeader('Content-Type', 'application/json');
        $this->client->getRequest()->setHeader('Authorization', sprintf('Bearer %s',$token));
        $this->client->getRequest()->setPostField('account', $this->accountCn)
            ->setPostField('correlation_id', $jira_ticket)
            ->setPostField('correlation_display', $jira_ticket)
            ->setPostField('u_type', $utype)
            ->setPostField('u_contact', Config::get('U_CONTACT'))
            ->setPostField('priority', $priority)
            ->setPostField('short_description', $title)
            ->setPostField('description', $description)
            ->setPostField('business_service', $business_service)
            ->setPostField('request_area', $request_area);
        $this->client->exec();

        $code = $this->client->getResponse()->getCode();
        if ($code == 200)
        {
            $response = json_decode($this->client->getResponse()->getResponseBody());
            $codeAPI = $response->result->code;
            if ($codeAPI == 600)
            {
                // ticket creato, prelevare la risposta
                $this->setTicketNumber($response->result->number);
                $this->setTicketId($response->result->ticketid);
            }
            else
            {
                $details = $response->result->details;
                throw new ServiceNowApiException($details, $codeAPI);
            }
        }
        else
        {
            throw new ServiceNowApiException(sprintf('Error code:%s, msg: %s', $code, $this->client->getResponse()->getError()));
        }
    }

}