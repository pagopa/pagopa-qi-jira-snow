<?php

namespace pagopa\jirasnow\snow;

use CURLFile;
use pagopa\jirasnow\Config;
use pagopa\jirasnow\HTTPClient;
use stdClass;

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
     * Contiene la url API per la cancellazione di un ticket
     * @var string
     */
    protected string $urlCancelTicket;

    /**
     * Contiene la url API per l'upload di allegati
     * @var string
     */
    protected string $urlUploadAttach;

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
        $this->urlCancelTicket = Config::get('SERVICE_NOW_URL_CANCEL');
        $this->urlUploadAttach = Config::get('SERVICE_NOW_URL_ATTACH');
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
     * @throws ServiceNowApiException
     */
    public function getToken() : string
    {
        if (empty($this->oauthToken))
        {
            $this->refreshToken();
        }
        return $this->oauthToken;
    }


    /**
     * Crea un ticket con i parametri indicati
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

        $this->prepareHttpClient($this->urlCreateTicket);
        $this->client->getRequest()->setPostField('correlation_id', $jira_ticket)
            ->setPostField('correlation_display', $jira_ticket)
            ->setPostField('u_type', $utype)
            ->setPostField('u_contact', Config::get('U_CONTACT'))
            ->setPostField('priority', $priority)
            ->setPostField('short_description', $title)
            ->setPostField('description', $description)
            ->setPostField('business_service', $business_service)
            ->setPostField('request_area', $request_area);
        $this->client->exec();

        $response = $this->fetchResponse();
        $this->setTicketNumber($response->number);
        $this->setTicketId($response->ticketid);
    }


    /**
     * Assegna un ticket al team di ServiceNow
     * @param string $ticket_id
     * @return void
     * @throws ServiceNowApiException
     */
    public function assignTicket(string $ticket_id) : void
    {
        $this->prepareHttpClient($this->urlAssignTicket);
        $this->client->getRequest()->setPostField('ticket_id', $ticket_id)
            ->setPostField('comments', 'Ticket assegnato automaticamente');

        $this->client->exec();
        $this->fetchResponse();
    }


    /**
     * @param string $ticket_id
     * @param string $comment
     * @return void
     * @throws ServiceNowApiException
     */
    public function commentTicket(string $ticket_id, string $comment) : void
    {
        $this->prepareHttpClient($this->urlCommentTicket);
        $this->client->getRequest()->setPostField('ticket_id', $ticket_id)
            ->setPostField('comments', $comment);

        $this->client->exec();
        $this->fetchResponse();

    }


    /**
     * Chiude un ticket ServiceNow
     * @param string $ticket_id
     * @return void
     * @throws ServiceNowApiException
     */
    public function closeTicket(string $ticket_id) : void
    {
        $this->prepareHttpClient($this->urlCloseTicket);
        $this->client->getRequest()->setPostField('ticket_id', $ticket_id)
            ->setPostField('comments', 'Ticket chiuso');
        $this->client->exec();
        $this->fetchResponse();
    }


    /**
     * Cancella un ticket precedentemente aperto
     * @param string $ticket_id
     * @return void
     * @throws ServiceNowApiException
     */
    public function cancelTicket(string $ticket_id) : void
    {
        $this->prepareHttpClient($this->urlCancelTicket);
        $this->client->getRequest()->setPostField('ticket_id', $ticket_id)
            ->setPostField('comments', 'Ticket cancellato');
        $this->client->exec();
        $this->fetchResponse();
    }

    /**
     * Effettua l'upload di un singolo allegato su Service Now Nexi
     * @param $filename
     * @param $ticket_id
     * @return mixed
     * @throws ServiceNowApiException
     */
    public function uploadAttach($filename, $ticket_id) : mixed
    {
        $curlFile = new CURLFile($filename);
        $mimetype_Replace = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'application/msword',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'application/vnd.ms-excel'
        ];

        $mimeType = mime_content_type($filename);
        if (array_key_exists($mimeType, $mimetype_Replace))
        {
            $mimeType = $mimetype_Replace[$mimeType];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => $mimeType
        ];

        $qs = [
            'table_name' => 'sn_customerservice_case',
            'table_sys_id' => $ticket_id,
            'file_name' => basename($filename)
        ];


        $queryString = (count($qs) == 0) ? "" : sprintf('?%s', http_build_query($qs, "", null, PHP_QUERY_RFC3986));
        $url_attach = sprintf('%s%s', $this->urlUploadAttach, $queryString);


        $token = $this->getToken();
        $client = new HTTPClient($url_attach, 'POST');
        $client->getRequest()->setHeader('Content-Type', $mimeType);
        $client->getRequest()->setHeader('Authorization', sprintf('Bearer %s',$token));
        $client->getRequest()->setPostField('', $filename);
        $client->exec();
        $response = $client->getResponse()->getResponseBody();
        $code = $client->getResponse()->getCode();
        $json_response = json_decode($response);
        unlink($filename);
        if (($code == 200) || ($code == 201))
        {
            $size_bytes = $json_response->result->size_bytes;
            return [
                'size_bytes' => $size_bytes,
                'code' => $code,
                'status' => 'OK'
            ];
        }
        else
        {
            return [
                'code' => (isset($json_response->errcode)) ? $json_response->errcode : $code,
                'details' => (isset($json_response->errmsg)) ? $json_response->errmsg : 'Malformed response body',
                'status' => 'KO'
            ];
        }
    }


    /**
     * Preleva la risposta dalla HTTPClient e fornisce la risposta in formato stdClass in caso di esito Positivo
     * In caso di esito negativo, sia per KO delle API che per errori sul layer HTTP, lancia una ServiceNowApiException
     * In caso di OK, l'output contiene il ramo "result" delle chiamate API a ServiceNow (ramo presente in tutte le risposte)
     * In caso di KO delle API, la ServiceNowApiException contiene il messaggio di errore e il codice di errore dell'API
     * In caso di KO del layer HTTP, la ServiceNowApiException contiene il messaggio di errore e il codice HTTP
     * @return stdClass
     * @throws ServiceNowApiException
     */
    private function fetchResponse() : stdClass
    {
        $code = $this->client->getResponse()->getCode();
        if ($code == 200)
        {
            $response = json_decode($this->client->getResponse()->getResponseBody());
            $codeAPI = $response->result->code;
            if ($codeAPI == 600)
            {
                return $response->result;
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


    /**
     * Prepara la configurazione della Request con i valori comuni a tutte le api (token, content-type, account-cn)
     * @param string $url
     * @return void
     * @throws ServiceNowApiException
     */
    private function prepareHttpClient(string $url) : void
    {
        $token = $this->getToken();
        $this->client = new HTTPClient($url, 'POST');
        $this->client->getRequest()->setHeader('Content-Type', 'application/json');
        $this->client->getRequest()->setHeader('Authorization', sprintf('Bearer %s',$token));
        $this->client->getRequest()->setPostField('account', $this->accountCn);
    }

}