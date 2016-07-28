<?php

/**
 * Get environment variable
 *
 * @param       $varName
 * @param mixed $default
 * @return string
 * @throws Exception if there is no not optional variable and no default value
 */
if (!function_exists('env')) {
    function env($varName, $default = NULL)
    {
        $env = getenv($varName);
        if ($env === false && !is_null($default)) {
            return $default;
        } elseif ($env === false && is_null($default) && stripos($varName, 'optional') === false) {
            throw new Exception("UNABLE TO FIND NECESSARY ENV VARIABLE: $varName");
        }
        switch (strtolower($env)) {
            case 'true':
            case '(true)':
            case 'on':
                return true;
            case 'false':
            case '(false)':
            case 'off':
                return false;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $env;
    }
}