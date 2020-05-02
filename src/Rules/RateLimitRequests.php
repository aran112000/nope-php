<?php

namespace App\Rules;

use App\ConsoleColour;
use App\Exceptions\AbuseException;
use App\Redis;

/**
 * Class RateLimitRequests
 *
 * @package App
 */
class RateLimitRequests extends Rule
{

    /**
     * A list of bots to be whitelisted from this rule, these are
     * matched case insensitively as a partial match so things like
     * Googlebot will match a number of their variants (images, video,
     * news...) for example.
     */
    const WHITELISTED_BOTS = [
        // Google's user agents
        'Googlebot',
        'Mediapartners-Google',
        'AdsBot-Google',

        // Bing
        'bingbot',

        // Yahoo
        'Slurp',

        // DuckDuckGo
        'DuckDuckBot',

        // Baidu
        'Baiduspider',

        // Yandex
        'YandexBot',

        // Facebook
        'facebookexternalhit',

        // Alexa
        'ia_archiver',

        // LinkedIn
        'LinkedInBot',
    ];

    /**
     * @var int
     */
    private $maxRequestsInPeriod;

    /**
     * @var int
     */
    private $periodDurationSeconds;

    /**
     * @var bool
     */
    private $ignoreAssetRequests;

    /**
     * RateLimitRequests constructor.
     *
     * @param int  $maxRequestsInPeriod   - How many requests to allow within your time period
     * @param int  $periodDurationSeconds - Defaults to 1 minute (60 seconds)
     * @param bool $ignoreAssetRequests   - Should static files be ignored in the request counts? Defaults to true
     */
    public function __construct($maxRequestsInPeriod = 40, $periodDurationSeconds = 60, $ignoreAssetRequests = true)
    {
        $this->maxRequestsInPeriod = $maxRequestsInPeriod;
        $this->periodDurationSeconds = $periodDurationSeconds;
        $this->ignoreAssetRequests = $ignoreAssetRequests;
    }

    /**
     * @return int
     */
    public function getMaxRequestsInPeriod()
    {
        return $this->maxRequestsInPeriod;
    }

    /**
     * @return int
     */
    public function getPeriodDurationSeconds()
    {
        return $this->periodDurationSeconds;
    }

    /**
     * @throws \App\Exceptions\AbuseException
     */
    public function run()
    {
        if (!$this->getIp()) {
            // Unable to find an IP in this log row, skip

            return;
        }

        if ($this->ignoreAssetRequests && $assetType = $this->isAssetPath()) {
            // Ignoring IP as it's a request for an asset
            $this->outputDebug('Ignoring request for ' . $assetType . ' from ' . $this->getIp(), ConsoleColour::TEXT_GREEN);

            return;
        }

        if ($botName = $this->isAllowedBot()) {
            // The user agent matches that of a whitelisted Bot
            $this->outputDebug('Ignoring request from a whitelisted bot (' . $botName . ')', ConsoleColour::TEXT_GREEN);

            return;
        }

        $this->logRequestAndBlockIfAbuseDetected();
    }

    /**
     * @throws \App\Exceptions\AbuseException
     */
    protected function logRequestAndBlockIfAbuseDetected()
    {
        // We allow a look back at the previous minute's traffic as well as the current
        // If either exceed the specified threshold, then a block is performed

        $currentTimeKey = floor(time() / $this->getPeriodDurationSeconds()); // Valid for X seconds

        $currentCacheKey = 'RateLimitGetRequests:' . $currentTimeKey . ':' . $this->getIp();
        $previousCacheKey = 'RateLimitGetRequests:' . ($currentTimeKey - 1) . ':' . $this->getIp();

        $redisResponses = Redis::connection()
            // Start a batch request to Redis
            ->multi()

            // Fetch the request count during the lookup window
            ->get($previousCacheKey)

            // Increment the current request count, this also returns the current count
            ->incr($currentCacheKey)

            // Set the current count key to expire (double the request time limit to allow for our look back window)
            ->expire($currentCacheKey, $this->getPeriodDurationSeconds() * 2)

            // Execute the batch requests against Redis for performance
            ->exec();

        list($previousPeriodRequestCount, $currentPeriodRequestCount) = $redisResponses;

        // Blocks are based on exceeding an average of the current and look back periods
        $highestRequestCountLastTwoPeriods = max($previousPeriodRequestCount, $currentPeriodRequestCount);

        $this->outputDebug($this->getIp() . ' request peak: ' . $highestRequestCountLastTwoPeriods . ' - ' . $this->getHost());

        if ($highestRequestCountLastTwoPeriods > $this->getMaxRequestsInPeriod()) {
            $message = $this->getIp() . ' exceeded the threshold of ' . $this->getMaxRequestsInPeriod() . ' requests within ' . $this->getPeriodDurationSeconds() . ' seconds - ' . $this->getHost();

            $this->outputDebug($message, ConsoleColour::TEXT_RED);

            throw new AbuseException($message);
        }
    }

    /**
     * @return string|false
     */
    protected function isAllowedBot()
    {
        $userAgent = $this->getUserAgent();

        foreach (static::WHITELISTED_BOTS as $bot) {
            if (stristr($userAgent, $bot)) {
                return $bot;
            }
        }

        return false;
    }
}