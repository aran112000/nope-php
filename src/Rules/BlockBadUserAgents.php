<?php

namespace App\Rules;

use App\Config\IpAddress;
use App\ConsoleColour;
use App\Exceptions\AbuseException;

/**
 * Class BlockBadUserAgents
 *
 * @package App
 */
class BlockBadUserAgents extends Rule
{

    /**
     * A list of bots to be blocked by this rule, these are
     * matched case sensitively as a partial match so things like
     * Googlebot will match a number of their variants (images, video,
     * news...) for example.
     */
    const BLACKLISTED_BOTS = [
        // Common spam bots & scrapers
        'Java/',
        'python-requests/',
        'Vagabondo/',
        'Re-re Studio',
        '2re.site',
        'curl/',
    ];

    /**
     * @throws \App\Exceptions\AbuseException
     */
    public function run()
    {
        if ($botName = $this->isBadBot()) {
            $message = 'Blocking blacklisted bot (' . $this->getUserAgent() . ') for matching rule: "' . $botName . '" - ' . $this->getIp();

            $this->outputDebug($message, ConsoleColour::TEXT_RED);

            throw new AbuseException($message);
        }
    }

    /**
     * @return string|false
     */
    protected function isBadBot()
    {
        $userAgent = $this->getUserAgent();

        foreach (static::BLACKLISTED_BOTS as $bot) {
            if (strstr($userAgent, $bot)) {
                return $bot;
            }
        }

        return false;
    }
}