<?php

namespace App\Rules;

use App\Config\IpAddress;
use App\Config\UserAgent;
use App\ConsoleColour;
use App\LogLine;

/**
 * Class Rule
 *
 * @package App
 */
abstract class Rule
{

    /**
     * @var LogLine
     */
    protected $logLine;

    /**
     * @throws \App\Exceptions\AbuseException
     */
    abstract public function run();

    /**
     * Default entry point for running all rules
     *
     * Don't extend this, this is internally a parent method to
     * prevent blocking any whitelisted IPs accidentally
     *
     * @throws \App\Exceptions\AbuseException
     */
    public function exec()
    {
        if ($ipDescription = IpAddress::isTrusted($this->logLine->getIp())) {
            // This is a whitelisted IP, skip any rules
            $this->outputDebug('Skipping due to whitelisted IP: ' . $ipDescription, ConsoleColour::TEXT_GREEN);

            return;
        }

        if ($botName = UserAgent::isTrusted($this->logLine->getUserAgent())) {
            // This is a whitelisted user agent, skip any rules
            $this->outputDebug('Skipping due to whitelisted user agent: ' . $botName, ConsoleColour::TEXT_GREEN);

            return;
        }

        $this->run();
    }

    /**
     * @param LogLine $logLine
     */
    public function setLogLine(LogLine $logLine)
    {
        $this->logLine = $logLine;
    }

    /**
     * @return string|false
     */
    protected function getWebDirectoryPath()
    {
        $host = $this->logLine->getHost();

        if (!$host) {
            $this->outputDebug('Unable to determine the host from the logs');

            return false;
        }

        $vhostDirectory = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
            'var',
            'www',
            'vhosts',
            str_replace('www.', '', $host),
            'web'
        ]);

        if (!is_dir($vhostDirectory)) {
            $this->outputDebug('Unable to locate vhost directory for ' . str_replace('www.', '', $host));

            return false;
        }

        return $vhostDirectory;
    }

    /**
     * @param string       $message
     * @param null|string  $colour   - Constant from App\ConsoleColour
     */
    protected function outputDebug($message, $colour = null)
    {
        if (!DEBUG_MODE) {
            return;
        }

        $time = date('d/m/Y H:i:s');

        // Get a friendly rule name from camelCase class names
        $classParts = explode('\\', get_called_class());
        $friendlyRuleName = ucfirst(strtolower(trim(preg_replace('#([A-Z])#', ' $1', end($classParts)))));

        if ($colour === null) {
            echo "[$time] $friendlyRuleName: $message\n";

            return;
        }

        echo "[$time] $friendlyRuleName: \033[" . $colour . "m$message\033[0m\n";
    }
}
