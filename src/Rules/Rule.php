<?php

namespace App\Rules;

use App\Config\IpAddress;
use App\ConsoleColour;
use App\RequestDetails;

/**
 * Class Rule
 *
 * @package App
 */
abstract class Rule extends RequestDetails
{

    /**
     * @var string
     */
    private $logLine;

    /**
     * @throws \App\Exceptions\AbuseException
     */
    abstract public function run();

    /**
     * Default entry point for running all rules
     *
     * Don't extend this, this is internally a parent method to
     * prevent blocking any whitelisted IPs accitentally
     *
     * @throws \App\Exceptions\AbuseException
     */
    public function exec()
    {
        if ($ipDescription = IpAddress::isTrusted($this->getIp())) {
            $this->outputDebug('Skipping due to whitelisted IP: ' . $ipDescription, ConsoleColour::TEXT_GREEN);

            return;
        }

        $this->run();
    }

    /**
     * @return string
     */
    public function getLogLine()
    {
        return $this->logLine;
    }

    /**
     * @param string $logLine
     */
    public function setLogLine($logLine)
    {
        $this->logLine = $logLine;
    }

    /**
     * @return string|false
     */
    protected function isAssetPath()
    {
        if (preg_match('#image/png|image/jpeg|image/gif|image/webp|image/svg|text/css|application/javascript|image/x-icon|application/octet-stream|application/pdf|font/opentype|font/otf|font/woff2|font/woff|font/ttf#', $this->getLogLine(), $matches)) {
            return $matches[0];
        }

        return false;
    }

    /**
     * @return string|false
     */
    protected function getWebDirectoryPath()
    {
        $host = $this->getHost();

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
