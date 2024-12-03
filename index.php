<?php



require './vendor/autoload.php';

set_exception_handler(function (Throwable $exception)
{
    $response = array(
        'message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTrace()
    );
    header('HTTP/1.1 500 Internal Server Error', true, 503);
    header('Content-Type: application/json');
    echo json_encode($response);
});

$uri = $_SERVER['REQUEST_URI'];

$bootstrap = new \pagopa\jirasnow\Bootstrap();
$bootstrap->run();