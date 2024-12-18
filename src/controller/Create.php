<?php

namespace pagopa\jirasnow\controller;

use pagopa\jirasnow\JiraTicket;
use pagopa\jirasnow\snow\ServiceNowAPI;
use pagopa\jirasnow\snow\ServiceNowApiException;

/**
 * Controller che si occupa di gestire le chiamate nel contesto /create
 * Parametri di input per le chiamate API<br>
 * Dati impliciti
 * <code>
 *     {
 *          "action" : "create" ,
 *          "jira_ticket" : <jira_ticket_id>
 *     }
 * </code>
 *
 * Dati espliciti
 * <code>
 *     {
 *          "action" : "createByTicket" ,
 *          "jira_ticket" : "PPIT-0216" ,
 *          "title" : "titolo", "priority" : 3,
 *          "description" : "descrizione" ,
 *          "request_area" : "NPG" ,
 *          "business_service" :
 *          "Codici 900 - Singolo pagamento" ,
 *          "type" : "incident"
 *     }
 * </code>
 *
 * Parametri di output per chiamate OK
 * <code>
 *     {
 *          "status": "OK",
 *          "code": 200,
 *          "ticket_id": <ticket_id>,
 *          "ticket_cs": <ticket_number>
 *     }
 * </code>
 *
 * Parametri di output per chiamate KO
 * <code>
 *     {
 *          "status": "KO",
 *          "code": <errorCode>,
 *          "msg": <errorMsg>
 *     }
 * </code>
 */
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


    /**
     * Mappatura action <-> method
     * E' possibile richiamare un metodo o un altro in base al valore di "action" nel json della chiamata
     * Per evitare chiamate a qualsiasi metodo, si mappano le action con i nomi dei metodi, rendendo possibile
     * chiamare solo determinati metodi
     * @var array|string[]
     */
    protected array $mappedAction = [ 'create' => 'create', 'createByTicket' => 'createByTicket'];


    /**
     * Inizio logica per chiamate con context /create
     * @return void
     * @throws ControllerException
     */
    public function init()
    {
        $data = json_decode($this->bootstrap->getPostData());
        $action = $data->action;
        if (array_key_exists($action, $this->mappedAction))
        {
            $method = $this->mappedAction[$action];
            $this->$method();
        }
        else
        {
            throw new ControllerException(sprintf('Method %s doesn\'t exist', $action));
        }
    }

    /**
     * Crea un ticket su Service Now ricavando le informazioni dal Ticket Jira in maniera automatica
     * Payload chiamata
     * <code>
     *     {
     *          "action" : "create" ,
     *          "jira_ticket" : "PPIT-216"
     *     }
     * </code>
     * Dove jira_ticket è l'id del ticket Jira
     * @return void
     */
    public function create() : void
    {
        $data = json_decode($this->bootstrap->getPostData());
        $ticket_id = $data->jira_ticket;

        $jiraticket         = new Jiraticket($ticket_id);
        $title              = $jiraticket->getTitle();
        $priority           = $jiraticket->getPriority();
        $description        = $jiraticket->getDescription();
        $request_area       = $jiraticket->getRequestArea();
        $business_service   = $jiraticket->getBusinessService();
        $utype              = $jiraticket->getRequestType();

        $this->createTicket($ticket_id, $title, $description, $utype, $business_service, $request_area, $priority);

    }

    /**
     * Crea un ticket su Service Now ricavando ricevendo le informazioni in input
     * Payload chiamata
     * Payload chiamata
     * <code>
     *     {
     *          "action" : "createByTicket" ,
     *          "jira_ticket" : "PPIT-0216",
     *          "title" : "titolo",
     *          "priority" : 3,
     *          "description" : "descrizione" ,
     *          "request_area" : "NPG" ,
     *          "business_service" :
     *          "Codici 900 - Singolo pagamento" ,
     *          "type" : "incident"
     *     }
     * </code>
     * Dove jira_ticket è l'id del ticket Jira
     * @return void
     */
    public function createByTicket() : void
    {
        $data = json_decode($this->bootstrap->getPostData());

        $ticket_id          = $data->jira_ticket;
        $title              = $data->title;
        $priority           = $data->priority;
        $description        = $data->description;
        $request_area       = $data->request_area;
        $business_service   = $data->business_service;
        $utype              = $data->type;

        $this->createTicket($ticket_id, $title, $description, $utype, $business_service, $request_area, $priority);
    }

    /**
     * Effettua la create vera e propria, popolando l'output in base all'esito
     * @param $ticket_id string id del ticket jira (serve come label a Service Now)
     * @param $title string titolo del ticket
     * @param $description string descrizione del ticket
     * @param $utype string tipologia di richiesta (incident, information)
     * @param $business_service string business server
     * @param $request_area string request area
     * @param $priority string priorità del ticket
     * @return void
     */
    private function createTicket(string $ticket_id, string $title, string $description, string $utype, string $business_service, string $request_area, string $priority) : void
    {
        $output = array();
        try {
            $servicenow_ticket = new ServiceNowAPI();
            $servicenow_ticket->createTicket($ticket_id, $title, $description, $utype, $business_service, $request_area, $priority);
            $output = [
                'status' => 'OK',
                'code' => 200,
                'ticket_id' => $servicenow_ticket->getTicketId(),
                'ticket_cs' => $servicenow_ticket->getTicketNumber()
            ];
        }
        catch (ServiceNowApiException $e)
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