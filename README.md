# pagopa-qi-jira-snow
pagoPA service integration Jira vs SeriviceNow


Questo progetto serve a far si che l'apertura di un ticket su Jira Work Management si rifletta su Service Now di Nexi


<h1>Local Deploy</h1>

for build:
```console
docker build -t pagopa-qi-jira-snow .
```
for run:
```console
docker run -d --name=env_jirasnow --env-file=./.env -p 9000:80 -v .:/var/www/html pagopa-qi-jira-snow
```

<h1>API</h1>
<h2>/create</h2>

With this payload, the app retrieves the information needed to open the ticket on Service Now through JIRA APIs
```console
curl http://localhost:9000/create -X POST -d '{ "action" : "create" , "jira_ticket" : "PPIT-216" }'
```


With this payload, the app receives as input all the parameters needed to open a ticket on Service Now 
and associate it with the Jira ticket indicated in the jira_ticket field
```console
curl http://localhost:9000/create -X POST -d \
'{
"action" : "createByTicket" ,
"jira_ticket" : "PPIT-0216",
"title" : "titolo",
"priority" : 3,
"description" : "descrizione" ,
"request_area" : "NPG" ,
"business_service" : "Codici 900 - Singolo pagamento" ,
"type" : "incident"
}'
```


<h1>Environment Variable</h1>

### ENVNAME
### PREFIX_JIRA
### JIRA_HOST
### JIRA_USER
### JIRA_PASS
### JIRA_BEARER_TOKEN_FOR_DOWNLOAD
### TOKEN_BASED_AUTH
### JIRA_ID_FIELD_TICKET_ID
### JIRA_ID_FIELD_TICKET_NUMBER
### JIRA_ID_FIELD_LAST_SENT
### JIRA_ACCOUNT_ID_IGNORE_ATTACH
### JIRA_EMAIL_ADDRESS_USER_NEXI
### SERVICE_NOW_CLIENT_ID
### SERVICE_NOW_CLIENT_SECRET
### U_CONTACT
### SERVICE_NOW_URL_OAUTH_TOKEN
### SERVICE_NOW_URL_ATTACH
### SERVICE_NOW_URL_CREATE
### SERVICE_NOW_URL_CLOSE
### SERVICE_NOW_URL_ASSIGN
### SERVICE_NOW_URL_COMMENT
### USE_PAGOPA_FORWARDER
### FORWARDER_SUBSCRIPTION_KEY
### FORWARDER_URL
### USE_MTLS
### CERTIFICATE_KEY
### CERTIFICATE_PUBLIC
### JIRA_ID_TYPE_INCIDENT
### JIRA_ID_TYPE_REQUEST
### SERVICE_NOW_ACCOUNT_CN
### CERTIFICATE_FORMAT
### VERIFY_CHAIN
### JIRA_ID_FIELD_BUSINESS_AREA_INCIDENT
### JIRA_ID_FIELD_BUSINESS_AREA_REQUEST