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

        while (true) {
            $logLine = new LogLine();
            $logLine->setLogLine(fgets($this->logHandle, 10000));

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
            $this->addToIpTables($logLine);

            return false;
        }

        return true;
    }

    /**
     * Creates the ipset and iptable rules required for blocking
     *
     * Will only be ran once each time this script is initiated, if they
     * already exist, no changes will be performed
     */
    protected function initIpTablesSetup()
    {
        static $processed;

        if ($processed) {
            // Only allow this to try and setup the structure the once
            return;
        }

        $processed = true;

        $commands = [
            // Create an IP list which will block listed IPs for 300 seconds (5 minutes)
            'ipset create five_minute_ip_block_list hash:ip timeout 300',

            // Setup this new IP list to block the IPs added using iptables
            'iptables -I INPUT 1 -m set -j DROP  --match-set five_minute_ip_block_list src',
            'iptables -I FORWARD 1 -m set -j DROP  --match-set five_minute_ip_block_list src',
        ];

        // Run each of these commands in order and only if the prior was successful
        // If the ipset already exists, it'll exit and prevent us re-adding the iptables rules
        exec(implode(' && ', $commands));
    }

    /**
     * @param \App\LogLine $logLine
     */
    protected function addToIpTables(LogLine $logLine)
    {
        if (DEBUG_MODE) {
            return;
        }

        $this->initIpTablesSetup();

        // Add this IP to our block list for 5 minutes, if the IP already exists, the
        // block time will be renewed with this call:
        exec('ipset -exist add five_minute_ip_block_list ' . escapeshellarg($logLine->getIp()));
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