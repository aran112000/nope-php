<?php

namespace App\Config;

/**
 * Class IpAddress
 *
 * @package App\Config
 */
class IpAddress
{

    /**
     * @param string $ipToValidate
     *
     * @return string|false
     */
    public static function isTrusted($ipToValidate)
    {
        foreach (Setting::get('Whitelist', 'IPs') as $ipDescription => $ips) {
            if (in_array($ipToValidate, $ips)) {
                return $ipDescription;
            }
        }

        return false;
    }
}
