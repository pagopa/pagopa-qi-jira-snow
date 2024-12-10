<?php

namespace pagopa\jirasnow\snow;

use pagopa\jirasnow\Config;
use pagopa\jirasnow\HTTPClient;

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



    public function __construct()
    {
        $this->urlGetToken = Config::get('SERVICE_NOW_URL_OAUTH_TOKEN');
        $this->urlCreateTicket = Config::get('SERVICE_NOW_URL_CREATE');
        $this->urlCloseTicket = Config::get('SERVICE_NOW_URL_CLOSE');
        $this->urlAssignTicket = Config::get('SERVICE_NOW_URL_ASSIGN');
        $this->urlCommentTicket = Config::get('SERVICE_NOW_URL_COMMENT');
        $this->clientId = Config::get('SERVICE_NOW_CLIENT_ID');
        $this->clientSecret = Config::get('SERVICE_NOW_CLIENT_SECRET');
        $this->accountCn = Config::get('SERVICE_NOW_ACCOUNT_CN');
    }


    /**
     * Effettua la chiamata alla URL di getToken per ricevere un nuovo token
     * @return void
     */
    public function refreshToken() : void
    {
        /*
         *
         * grant_type=client_credentials&client_id={{clientid.urlEncode}}&client_secret={{clientsecret.urlEncode}}
         * Content-Type application/x-www-form-urlencoded
         * */

        $this->client = new HTTPClient($this->urlGetToken, 'POST');
        $this->client->getRequest()->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->client->getRequest()->setPostField('grant_type', 'client_credentials');
        $this->client->getRequest()->setPostField('client_id', $this->clientId);
        $this->client->getRequest()->setPostField('client_secret', $this->clientSecret);

        $this->client->exec();
        $response = json_decode($this->client->getResponse()->getResponseBody());
        $this->oauthToken = $response->access_token;

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

}