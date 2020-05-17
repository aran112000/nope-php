<?php

namespace App\Config;

class UserAgent
{

    /**
     * User agent strings to be whitelisted are matched case insensitively as a partial match so things like Googlebot
     * will match a number of their variants (images, video, news...) for example.
     *
     * @param string $userAgentStringToValidate
     *
     * @return string|false
     */
    public static function isTrusted($userAgentStringToValidate)
    {
        foreach (Setting::get('Whitelist', 'Bots') as $userAgentDescription => $userAgentVersions) {
            foreach ($userAgentVersions as $agentVersion) {
                if (stristr($userAgentStringToValidate, $agentVersion)) {
                    return $userAgentDescription;
                }
            }
        }

        return false;
    }
}
