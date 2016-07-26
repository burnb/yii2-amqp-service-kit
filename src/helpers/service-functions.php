<?php

/**
 * Get environment variable
 *
 * @param      $varName
 * @param bool $optional
 * @return string
 * @throws Exception if there is no not optional variable
 */
function env($varName, $optional = false)
{
    $env = getenv($varName);
    if ($env !== false || $optional || stripos($varName, 'optional') !== false) {
        return $env;
    }

    throw new Exception("Unable to find necessary env variable $varName");
}