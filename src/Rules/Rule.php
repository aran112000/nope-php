<?php

namespace App\Rules;

use App\Helpers\Log;
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
     * @return void
     * @throws \App\Exceptions\AbuseException
     */
    abstract public function run();

    /**
     * @param LogLine $logLine
     *
     * @return void
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
            $this->log('Unable to determine the host from the logs');

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
            $this->log('Unable to locate vhost directory for ' . str_replace('www.', '', $host));

            return false;
        }

        return $vhostDirectory;
    }

    /**
     * @param string      $message
     * @param null|string $colour  Constant from App\ConsoleColour
     *
     * @return void
     */
    protected function log($message, $colour = null)
    {
        // Get a friendly rule name from camelCase class names
        $classNameNoNamespace = (new \ReflectionClass($this))->getShortName();
        $ruleName = (string) preg_replace('#([A-Z])#', ' $1', $classNameNoNamespace);
        $friendlyRuleName = ucfirst(strtolower(trim($ruleName)));

        $message = $friendlyRuleName . ': ' . $message;

        Log::write($message, $colour);
    }
}
