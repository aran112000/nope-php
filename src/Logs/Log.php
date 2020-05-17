<?php

namespace App\Logs;

use App\Config\IpAddress;
use App\Config\UserAgent;
use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;
use App\Helpers\Log as Logger;
use App\Notifications\NotificationInterface;
use App\Rules\Rule;
use Closure;
use InvalidArgumentException;

/**
 * Class Nope
 *
 * @package App\Logs
 */
class Log
{

    /**
     * @var string
     */
    public $logFile;
    /**
     * @var resource|false
     */
    private $logHandle;
    /**
     * @var NotificationInterface[]
     */
    private $notificationChannels;
    /**
     * @var Rule[]
     */
    private $rules;
    /**
     * @var \Closure[]
     */
    private $whitelistRules;
    /**
     * @var bool
     */
    private $applyBlocks = false;

    /**
     * Log constructor.
     *
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;

        static $init;
        if ($init === null) {
            // First time ran, bootstrap anything here that only needs to be ran once

            error_reporting(E_ALL);
            ini_set('display_errors', 'true');
        }
    }

    /**
     * @param Rule[] $rules
     *
     * @return self
     */
    public function rules(array $rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Any channels you wish to notify when a ban occurs
     *
     * @param NotificationInterface[] $notificationChannels
     *
     * @return self
     */
    public function notify(array $notificationChannels)
    {
        $this->notificationChannels = $notificationChannels;

        return $this;
    }

    /**
     * @param Closure $closure
     *
     * @return self
     */
    public function whitelist(Closure $closure)
    {
        $this->whitelistRules[] = $closure;

        return $this;
    }

    /**
     * When called directly will simply monitor your log and report back on the result of your rules.
     *
     * If you wish to apply blocks, call monitorAndBlock() instead of this method.
     *
     * @return void
     */
    public function monitor()
    {
        if (!$this->logFile) {
            throw new InvalidArgumentException('You must specify a valid log file');
        }

        if (!$this->rules) {
            throw new InvalidArgumentException('You must provide some rules you\'d like to assert before running');
        }

        $this->logHandle = popen('sudo tail -F ' . $this->logFile . ' -n0', 'r');

        while ($this->logHandle) {
            $logLine = new LogLine();
            $logLine->setLogLine((string) fgets($this->logHandle, 10000));

            if ($this->isWhitelistedRequest($logLine)) {
                // Whitelisted so don't try and process
                continue;
            }

            foreach ($this->rules as $rule) {
                if (!$this->passesRule($rule, $logLine)) {
                    break;
                }
            }
        }

        if ($this->logHandle) {
            pclose($this->logHandle);
        }
    }

    /**
     * Will monitor your log file, and apply blocks as dictated by your rules
     *
     * @return void
     */
    public function monitorAndBlock()
    {
        $initialBlockState = $this->applyBlocks;

        $this->applyBlocks = true;

        $this->monitor();

        $this->applyBlocks = $initialBlockState;
    }

    /**
     * Invoke the specified rule, returns false if AbuseException thrown to block the request
     *
     * @param Rule    $rule
     * @param LogLine $logLine
     *
     * @return bool
     */
    protected function passesRule(Rule $rule, LogLine $logLine)
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
     *
     * @return void
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
     * @param LogLine $logLine
     *
     * @return void
     */
    protected function addToIpTables(LogLine $logLine)
    {
        if (IpAddress::isTrusted($logLine->getIp())) {
            // Extra check to ensure we don't accidentally block a trusted IP
            return;
        }

        if (UserAgent::isTrusted($logLine->getUserAgent())) {
            // Extra check to ensure we don't accidentally block a trusted User Agent
            return;
        }

        $this->initIpTablesSetup();

        // Add this IP to our block list for 5 minutes, if the IP already exists, the
        // block time will be renewed with this call:
        exec('ipset -exist add five_minute_ip_block_list ' . escapeshellarg($logLine->getIp()));

        $this->sendNotifications($logLine);
    }

    /**
     * @param LogLine $logLine
     *
     * @return void
     */
    protected function sendNotifications(LogLine $logLine)
    {
        foreach ($this->notificationChannels as $notificationChannel) {
            $notificationChannel->send('IP address blocked', [
                'IP address' => $logLine->getIp(),
                'User agent' => $logLine->getUserAgent(),
                'Domain' => $logLine->getDomain(),
                'URI' => $logLine->getUri(),
                'Method' => $logLine->getMethod(),
                'Server Hostname' => (string) gethostname(),
            ], $logLine->getUrl());
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->logHandle) {
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
        // Run any log specific whitelist rules
        foreach ($this->whitelistRules as $whitelisted) {
            if ($whitelistReason = $whitelisted($logLine)) {
                Logger::write('Skipping due to log-specific whitelist: ' . $whitelistReason, ConsoleColour::TEXT_GREEN);

                return true;
            }
        }

        if ($ipDescription = IpAddress::isTrusted($logLine->getIp())) {
            // This is a whitelisted IP, skip any rules
            Logger::write('Skipping due to whitelisted IP: ' . $ipDescription, ConsoleColour::TEXT_GREEN);

            return true;
        }

        if ($botName = UserAgent::isTrusted($logLine->getUserAgent())) {
            // This is a whitelisted user agent, skip any rules
            Logger::write('Skipping due to whitelisted user agent: ' . $botName, ConsoleColour::TEXT_GREEN);

            return true;
        }

        return false;
    }
}
