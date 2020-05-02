<?php

namespace App\Rules;

use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;
use App\Redis;
use App\Traits\StaticFileDetection;

/**
 * Class RateLimitRequests
 *
 * @package App
 */
class RateLimitRequests extends Rule
{

    use StaticFileDetection;

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
    public function __construct($maxRequestsInPeriod = 50, $periodDurationSeconds = 60, $ignoreAssetRequests = true)
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
        if (!$this->logLine->getIp()) {
            // Unable to find an IP in this log row, skip

            return;
        }

        if ($this->ignoreAssetRequests && $assetType = $this->isStaticFile()) {
            // Ignoring IP as it's a request for an asset
            $this->log('Ignoring request for ' . $assetType . ' (' . $this->logLine->getMimeType() . ') from ' . $this->logLine->getIp(), ConsoleColour::TEXT_GREEN);

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

        $currentCacheKey = 'RateLimitGetRequests:' . $currentTimeKey . ':' . $this->logLine->getIp();
        $previousCacheKey = 'RateLimitGetRequests:' . ($currentTimeKey - 1) . ':' . $this->logLine->getIp();

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

        $this->log($this->logLine->getIp() . ' request peak: ' . $highestRequestCountLastTwoPeriods . ' - ' . $this->logLine->getDomain());

        if ($highestRequestCountLastTwoPeriods > $this->getMaxRequestsInPeriod()) {
            $message = $this->logLine->getIp() . ' exceeded the threshold of ' . $this->getMaxRequestsInPeriod() . ' requests within ' . $this->getPeriodDurationSeconds() . ' seconds - ' . $this->logLine->getHost();

            $this->log($message, ConsoleColour::TEXT_RED);

            throw new AbuseException($message);
        }
    }
}