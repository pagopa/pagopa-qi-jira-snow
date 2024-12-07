<?php

namespace pagopa\jirasnow;

use DateTime;
use Exception;
use JiraRestApi\Issue\Attachment;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;

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
     * Restituisce il valore del custom field TICKET_ID del ticket, che corrisponde al TICKET_ID su Service Now
     * @return string
     */
    public function getServiceNowId() : string
    {
        return $this->issueService->fields->customFields[$this->custom_field_x_ticket_id];
    }

    /**
     * Restituisce il valore del custom field TICKET_NUMBER del ticket, che corrisponde al TICKET_NUMBER su Service Now
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


    public function getTitle() : string
    {

    }


}