<?php



require './vendor/autoload.php';
include './bootstrap.php';

$bootstrap = new \pagopa\jirasnow\Bootstrap();
$bootstrap->run();
$bootstrap->send();