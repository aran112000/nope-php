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
     * Any IPs declared here won't ever be blocked, please consult with AR/CR
     * before making ANY changes to the IPs contained here.
     *
     * These should cover all public and private IPs for our productions
     * servers to ensure crons / cURL requests all continue to run as
     * expected.
     *
     * No personal IPs should EVER be whitelisted here!
     *
     * Please ensure all IPs are maintained in their correctly named blocks,
     * and any new blocks, should be named appropriately  for future reference
     */
    protected static $whitelistedIps = [
        'Localhost' => [
            '127.0.0.1',
        ],
        'Evosite Office / VPN' => [
            '185.53.30.127',
        ],
        'AWS Web1' => [
            '52.30.93.143',
            '52.31.201.211',
            '52.19.162.254',
            '10.0.3.80',
            '10.0.3.90',
            '10.0.3.105',
            '10.0.3.228',
        ],
        'AWS Web4' => [
            '52.19.161.199',
            '52.18.8.208',
            '10.0.2.24',
            '10.0.2.21',
        ],
        'AWS Web6' => [
            '54.72.233.134',
            '10.0.2.126',
        ],
        'AWS Web8' => [
            '63.32.246.147',
            '54.72.180.132',
            '10.0.2.174',
            '10.0.2.89',
        ],
        'AWS Preview' => [
            '52.31.70.228',
            '10.0.1.128',
            '10.0.1.58',
        ],
    ];

    /**
     * @param string $ipToValidate
     *
     * @return string|false
     */
    public static function isTrusted($ipToValidate)
    {
        foreach (static::$whitelistedIps as $ipDescription => $ips) {
            if (in_array($ipToValidate, $ips)) {
                return $ipDescription;
            }
        }

        return false;
    }
}
