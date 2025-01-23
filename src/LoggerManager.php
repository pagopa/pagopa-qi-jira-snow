<?php

namespace pagopa\jirasnow;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\IntrospectionProcessor;
class LoggerManager
{
    private static ?Logger $logger = null;

    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            $logger = new Logger('logger');
            $lineFormatter = new LineFormatter('[%datetime%][%extra.memory_usage%][%level_name%][%extra.file%:%extra.line%][class:%extra.class%][fn:%extra.function%] %message% \n');
            $streamHandler = new StreamHandler('php://stdout', Logger::INFO);
            $streamHandler->setFormatter($lineFormatter);

            $logger->pushHandler($streamHandler);
            $logger->pushProcessor(new MemoryUsageProcessor());
            $logger->pushProcessor(new IntrospectionProcessor());

            self::$logger = $logger;
        }
        return self::$logger;
    }

    public static function info($message) : void
    {
        self::getLogger()->info($message);
    }

    public static function error($message) : void
    {
        self::getLogger()->error($message);
    }

    public static function debug($message) : void
    {
        self::getLogger()->debug($message);
    }

    public static function warning($message) : void
    {
        self::getLogger()->warning($message);
    }

    public static function critical($message) : void
    {
        self::getLogger()->critical($message);
    }

    public static function notice($message) : void
    {
        self::getLogger()->notice($message);
    }

    public static function emergency($message) : void
    {
        self::getLogger()->emergency($message);
    }

    public static function alert($message) : void
    {
        self::getLogger()->alert($message);
    }
    
}