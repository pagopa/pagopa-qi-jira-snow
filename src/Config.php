<?php

namespace pagopa\jirasnow;

/**
 * Classe che gestisce l'accesso alle variabili d'ambiente e ne effettua il trim.
 */
class Config
{

    /**
     * Restituisce il valore di una variabile d'ambiente
     * Se la variabile non esiste, restituisce null
     * @param string $config
     * @return mixed
     */
    public static function get(string $config) : mixed
    {
        if (array_key_exists($config, $_SERVER))
        {
            return trim($_ENV[$config], "\x22\x27\ \n\r\t\v\0");
        }
        return null;
    }
}