<?php

namespace App\Rules;

use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;

/**
 * Class BlockBadUserAgents
 *
 * @package App
 */
class BlockBadUserAgents extends Rule
{

    /**
     * @var string[]
     *
     * A list of bots to be blocked by this rule, these are matched case sensitively as a partial match so things like
     * Googlebot will match a number of their variants (images, video, news...) for example.
     */
    private $blacklistedBots = [
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
            $message = 'Blocking blacklisted bot (' . $this->logLine->getUserAgent() . ') for matching rule: "' . $botName . '" - ' . $this->logLine->getIp();

            $this->log($message, ConsoleColour::TEXT_RED);

            throw new AbuseException($message);
        }
    }

    /**
     * @return string|false
     */
    protected function isBadBot()
    {
        $userAgent = $this->logLine->getUserAgent();

        foreach ($this->blacklistedBots as $bot) {
            if (strstr($userAgent, $bot)) {
                return $bot;
            }
        }

        return false;
    }
}
