<?php

namespace App;

use App\Config\IpAddress;
use App\Config\UserAgent;
use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;
use App\Helpers\Log;
use App\Rules\Rule;

/**
 * Class Nope
 *
 * @package App
 */
class Nope
{

    /**
     * @var resource|false|null
     */
    private $logHandle;

    /**
     * @param string $logFile  - The log file you wish to monitor
     * @param        $rules    - Should yield the rules you want to apply in the order you desire
     *                           them to be evaluated in. Any rules that don't pass and throw the
     *                           AbuseException will result in an iptables ban being issued
     *                           for that IP address. After a ban, no other rules will be ran for
     *                           that log line.
     */
    public function monitorLog($logFile, \Closure $rules)
    {
        $this->logHandle = popen('sudo tail -f ' . $logFile, 'r');

        $baseLogLine = new LogLine();

        while (true) {
            $logLine = clone $baseLogLine;
            $logLine->setLogLine(fgets($this->logHandle, 5000));

            if ($this->isWhitelistedRequest($logLine)) {
                // Whitelisted so don't try and process
                continue;
            }

            foreach ($rules() as $rule) {
                if (!$this->triggerRule($rule, $logLine)) {
                    break;
                }
            }
        }

        if ($this->logHandle !== null) {
            pclose($this->logHandle);
        }
    }

    /**
     * @param Rule    $rule
     * @param LogLine $logLine
     *
     * @return bool
     */
    protected function triggerRule(Rule $rule, LogLine $logLine)
    {
        $rule->setLogLine($logLine);

        try {
            $rule->run();
        } catch (AbuseException $e) {
            $this->addToIpTables();

            return false;
        }

        return true;
    }

    /**
     *
     */
    protected function addToIpTables()
    {
        if (!DEBUG_MODE) {
            // TODO; Add to IPTABLES via IPSET
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->logHandle !== null) {
            pclose($this->logHandle);
        }
    }

    /**
     * @param LogLine $logLine
     *
     * @return bool
     */
    protected function isWhitelistedRequest(LogLine $logLine)
    {
        if ($ipDescription = IpAddress::isTrusted($logLine->getIp())) {
            // This is a whitelisted IP, skip any rules
            Log::write('Skipping due to whitelisted IP: ' . $ipDescription, ConsoleColour::TEXT_GREEN);

            return true;
        }

        if ($botName = UserAgent::isTrusted($logLine->getUserAgent())) {
            // This is a whitelisted user agent, skip any rules
            Log::write('Skipping due to whitelisted user agent: ' . $botName, ConsoleColour::TEXT_GREEN);

            return true;
        }

        return false;
    }
}