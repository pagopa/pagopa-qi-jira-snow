<?php
/**
 * Da includere in tutti gli script tramite "include" e non "require"
 */


/**
 * Creo un exception handler che mi produca sempre un messaggio di errore in formato JSON
 * ed invii un errore HTTP 500
 */
set_exception_handler(function (Throwable $exception)
{
    $response = array(
        'message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTrace()
    );
    header('HTTP/1.1 500 Internal Server Error', true, 500);
    header('Content-Type: application/json');
    $response = array(
        'code' => 500,
        'message' => $exception->getMessage()
    );
    echo json_encode($response);

});


/**
 * Dato che il container è basato sulla versione php:8.3-apache , le variabili d'ambiente non sono disponibili
 * per l'interprete PHP, quindi vanno passate da apache a PHP tramite la direttiva PassEnv nel file .htaccess
 * e poi inserite nell'array associativo globale $_ENV per la compatibilità con la libreria dotenv
 *
 * @see https://httpd.apache.org/docs/2.4/mod/mod_env.html#passenv
 */
$env_var = [
    'ENVNAME',
    'PREFIX_JIRA',
    'JIRA_HOST',
    'JIRA_USER',
    'JIRA_PASS',
    'JIRA_BEARER_TOKEN_FOR_DOWNLOAD',
    'TOKEN_BASED_AUTH',
    'JIRA_ID_FIELD_TICKET_ID',
    'JIRA_ID_FIELD_TICKET_NUMBER',
    'JIRA_ID_FIELD_LAST_SENT',
    'JIRA_ACCOUNT_ID_IGNORE_ATTACH',
    'JIRA_EMAIL_ADDRESS_USER_NEXI',
    'SERVICE_NOW_CLIENT_ID',
    'SERVICE_NOW_CLIENT_SECRET',
    'SERVICE_NOW_URL_OAUTH_TOKEN',
    'SERVICE_NOW_URL_ATTACH',
    'SERVICE_NOW_URL_CREATE',
    'SERVICE_NOW_URL_CLOSE',
    'SERVICE_NOW_URL_ASSIGN',
    'SERVICE_NOW_URL_COMMENT',
    'USE_PAGOPA_FORWARDER',
    'FORWARDER_SUBSCRIPTION_KEY',
    'FORWARDER_URL',
    'USE_MTLS',
    'CERTIFICATE_KEY',
    'CERTIFICATE_VALUE',
    'JIRA_ID_TYPE_INCIDENT',
    'JIRA_ID_TYPE_REQUEST',
    'SERVICE_NOW_ACCOUNT_CN'
];

foreach($env_var as $var)
{
    putenv("{$var}={$_SERVER[$var]}");
    $_ENV[$var] = trim($_SERVER[$var],"\x22\x27\ \n\r\t\v\0");
}