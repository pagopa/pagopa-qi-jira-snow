<?php

namespace pagopa\jirasnow;

use \DateTime;
use \Exception;
use JiraRestApi\Issue\Attachment;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;


/**
 * Classe che si occupa di integrarsi con Jira Service Management
 * Offre la possibilità di leggere tutte le proprietà di un ticket, oltre a poterle modificare
 */
class JiraTicket
{

    /**
     * Prefisso del ticket Jira, serve ad identificare la board in base all'ambiente.
     * @var string|mixed
     */
    protected string $prefix = "";

    /**
     * Contiene il nome completo del ticket Jira, prefisso + id
     * @var string|mixed
     */
    protected string $id;


    /**
     * Contiene valore dell'ìd univoco relativo al custom field TICKET_ID delle Issue Jira
     * @var string|mixed
     */
    protected string $custom_field_x_ticket_id;


    /**
     * Contiene valore dell'ìd univoco relativo al custom field TICKET_NUMBER delle Issue Jira
     * @var string|mixed
     */
    protected string $custom_field_x_ticket_number;

    /**
     * Contiene valore dell'ìd univoco relativo al custom field LAST_SENT delle Issue Jira
     * @var string|mixed
     */
    protected string $custom_field_x_last_sent;


    /**
     * Contiene il valore dell'id univoco relativo all'utente che viene usato da Service Now per l'upload di attachment
     * @var string|mixed
     */
    protected string $ignore_upload_file_by_id;


    /**
     * Contiene il valore del token associato all'utente che viene utilizzato per effettuate il download degli attachment da Jira
     * pronti per essere caricati su Service Now
     * @var string|mixed
     */
    protected string $token_x_download_jira;


    /**
     * Contiene il riferimento alla Issue su Jira
     * @var Issue
     */
    protected Issue $issueService;

    public function __construct(mixed $id)
    {
        $this->prefix = Config::get("PREFIX_JIRA");
        $ticket_id = $id;
        if (is_numeric($id))
        {
            $ticket_id = sprintf('%s-%s', $this->prefix, $id);
        }
        $this->id = $ticket_id;

        $this->custom_field_x_ticket_id = Config::get("JIRA_ID_FIELD_TICKET_ID");
        $this->custom_field_x_ticket_number = Config::get("JIRA_ID_FIELD_TICKET_NUMBER");
        $this->custom_field_x_last_sent = Config::get("JIRA_ID_FIELD_LAST_SENT");
        $this->ignore_upload_file_by_id = Config::get("JIRA_ACCOUNT_ID_IGNORE_ATTACH");
        $this->token_x_download_jira = Config::get("JIRA_BEARER_TOKEN_FOR_DOWNLOAD");

        $jira_ticket = new IssueService();
        $this->issueService = $jira_ticket->get($this->id);

    }

    /**
     * Restituisce il valore del custom field <b>TICKET_ID</b> del ticket, che corrisponde al <b>TICKET_ID</b> su <u>ServiceNow</u>
     * Il campo <b>TICKET_ID</b> è in formato <b>HEX</b>
     * @return string
     */
    public function getServiceNowId() : string
    {
        return $this->issueService->fields->customFields[$this->custom_field_x_ticket_id];
    }

    /**
     * Restituisce il valore del custom field <b>TICKET_NUMBER</b> del ticket, che corrisponde al <b>TICKET_NUMBER</b> su <u>ServiceNow</u>
     * Il campo <b>TICKET_NUMBER</b> è in formato <b>CS*</b>
     * @return string
     */
    public function getServiceNowNumber() : string
    {
        return $this->issueService->fields->customFields[$this->custom_field_x_ticket_number];
    }

    /**
     * Restituisce la data del campo LAST_SENT del ticket
     * @return DateTime
     * @throws Exception
     */
    public function getLastSent() : DateTime
    {
        $date = $this->issueService->fields->customFields[$this->custom_field_x_last_sent];
        return new DateTime($date);
    }


    /**
     * Restituisce la lista degli allegati del ticket
     * @return array
     */
    public function getAttachments() : array
    {
        return $this->issueService->fields->attachment;
    }

    /**
     * Restituisce il numero di allegati al ticket
     * @return int
     */
    public function getAttachmentsCount() : int
    {
        return count($this->getAttachments());
    }


    /**
     * Restituisce l'allegato alla posizione $index del ticket
     * @param int $index
     * @return Attachment|null
     */
    public function getAttachment(int $index = 0) : Attachment|null
    {
        $attachments = $this->getAttachments();
        if (array_key_exists($index, $attachments))
        {
            return $attachments[$index];
        }
        return null;
    }

    /**
     * Restituisce il displayName dell'utente che ha caricato il file sul ticket Jira
     * @param int $index
     * @return string|null
     */
    public function getAuthorNameAttach(int $index = 0) : string|null
    {
        $attach = $this->getAttachment($index);
        return $attach->author->displayName;
    }

    /**
     * Restituisce l'id dell'utente che ha caricato il file
     * @param int $index
     * @return string|null
     */
    public function getAuthorIdAttach(int $index = 0) : string|null
    {
        $attach = $this->getAttachment($index);
        return $attach->author->accountId;
    }


    /**
     * Imposta un valore per il campo LAST_SENT del ticket
     * @param DateTime $last_sent
     * @return void
     * @throws JiraException
     */
    public function setLastSent(\DateTime $last_sent) : void
    {
        // use c
        // ISO 8601 date
        // example: 2004-02-12T15:19:21+00:00
        $issueField = new IssueField(true);
        $issueField->addCustomField($this->custom_field_x_last_sent, $last_sent->format('c'));
        $issueService = new IssueService();
        $issueService->update($this->id, $issueField);
    }


    /**
     * Sincronizza gli allegati
     * @return void
     */
    public function syncAttach()
    {

    }


    /**
     * Restituisce il titolo di un ticket Jira
     * @return string
     */
    public function getTitle() : string
    {
        return $this->issueService->fields->summary;
    }

    /**
     * Restituisce la priority di un ticket
     * Critico => 1 (id 10000 Jira , Critical)
     * Alto => 2 (id 1 Jira, High)
     * Medio => 3 (id 2 Jira, Medium)
     * Basso => 4 (id 3 Jira, Low)
     * Basso => 4 (id 10001 Jira, Icebox)
     * @return mixed
     */
    public function getPriority() : mixed
    {
        $id =  $this->issueService->fields->priority->id;
        if ($id == 10001)
        {
            // icebox
            return 4;
        }
        if ($id == 10000)
        {
            // critical
            return 1;
        }
        return $id;
    }

    /**
     * Restituisce la description di un ticket Jira
     * @return string
     */
    public function getDescription() : string
    {
        return $this->issueService->fields->description;
    }


    /**
     * Restituisce il tipo di richiesta jira
     * @return string
     */
    public function getRequestType() : string
    {
        $id = $this->issueService->fields->issuetype->id;
        if ($id == Config::get('JIRA_ID_TYPE_INCIDENT'))
        {
            return 'incident';
        }
        if ($id == Config::get('JIRA_ID_TYPE_REQUEST'))
        {
            return 'information';
        }
        return $id;
    }

    /**
     * Restituisce il business Service della Richiesta Jira
     * @return mixed
     */
    public function getBusinessService() : mixed
    {
        $var = "";
        if ($this->getRequestType() == 'incident')
        {
            $var = Config::get('JIRA_ID_FIELD_BUSINESS_AREA_INCIDENT');
        }
        if ($this->getRequestType() == 'information')
        {
            $var = Config::get('JIRA_ID_FIELD_BUSINESS_AREA_REQUEST');
        }
        return $this->issueService->fields->customFields[$var]->value;
    }

    /**
     * Restituisce la request area della Richiesta Jira
     * @return mixed
     */
    public function getRequestArea() : mixed
    {
        $var = "";
        if ($this->getRequestType() == 'incident')
        {
            $var = Config::get('JIRA_ID_FIELD_BUSINESS_AREA_INCIDENT');
        }
        if ($this->getRequestType() == 'information')
        {
            $var = Config::get('JIRA_ID_FIELD_BUSINESS_AREA_REQUEST');
        }
        return $this->issueService->fields->customFields[$var]->child->value;
    }

}