<?php

namespace App\Config;

class UserAgent
{
    /**
     * A list of user agent strings to be whitelisted from this rule, these
     * are matched case insensitively as a partial match so things like
     * Googlebot will match a number of their variants (images, video,
     * news...) for example.
     */
    protected static $whitelistedUserAgents = [
        'Google' => [
            'Googlebot',
            'Mediapartners-Google',
            'AdsBot-Google',
        ],
        'Bing' => [
            'bingbot',
        ],
        'Yahoo' => [
            'Slurp',
        ],
        'DuckDuckGo' => [
            'DuckDuckBot',
        ],
        'Baiduspider' => [
            'Baiduspider',
        ],
        'Yandex' => [
            'YandexBot',
        ],
        'Facebook' => [
            'facebookexternalhit',
        ],
        'Alexa' => [
            'ia_archiver',
        ],
        'LinkedIn' => [
            'LinkedInBot',
        ],
    ];

    /**
     * @param string $userAgentStringToValidate
     *
     * @return string|false
     */
    public static function isTrusted($userAgentStringToValidate)
    {
        foreach (static::$whitelistedUserAgents as $userAgentDescription => $userAgentVersions) {
            foreach ($userAgentVersions as $agentVersion) {
                if (stristr($userAgentStringToValidate, $agentVersion)) {
                    return $userAgentDescription;
                }
            }
        }

        return false;
    }
}