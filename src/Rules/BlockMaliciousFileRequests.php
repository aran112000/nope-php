<?php

namespace App\Rules;

use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;

/**
 * Class BlockMaliciousFileRequests
 *
 * @package App\Rules
 */
class BlockMaliciousFileRequests extends Rule
{

    /**
     * List if URIs to block if they don't exist within your vhost. These URIs require an exact match (minus any query
     * string!), but are compared case insensitively.
     */
    const BLOCK_URIS_IF_NOT_EXISTING = [
        '/login.php',
        '/admin.php',
        '/admin/',
        '/admin',
        '/e/admin',
        '/.git',
        '/.git/',
        '/.git/HEAD',
        '/phpmyadmin',
        '/phpmyadmin/scripts/setup.php',
        '/myadmin/scripts/setup.php',
        '/phpmyadmin/index.php',
        '/sheep.php',
    ];

    /**
     * @throws \App\Exceptions\AbuseException
     */
    public function run()
    {
        foreach (static::BLOCK_URIS_IF_NOT_EXISTING as $relativePath) {
            if ($this->logLine->getUriNoQueryString() === $relativePath) {
                // This is a flagged path!
                $this->log(sprintf(
                    "Attempt to access a malicious URI: %s - %s",
                    $this->logLine->getUrl(),
                    $this->logLine->getIp()
                ), ConsoleColour::TEXT_BLUE);

                if ($this->webPathExists($relativePath)) {
                    // As this is actually a valid path within the vhost, we'll allow it
                    $this->log(sprintf(
                        "Allowing attempt to access a malicious URI (%s) because it exists in the vhost from %s",
                        $this->logLine->getUrl(),
                        $this->logLine->getIp()
                    ), ConsoleColour::TEXT_BLUE);

                    return;
                }

                if ($this->logLine->getResponseCode() <= 400) {
                    // As the web server responded with a seemingly valid response code, we'll allow it!
                    $this->log(sprintf(
                        "Allowing attempt to access a malicious URI (%s) because it got a %d response from %s",
                        $this->logLine->getUrl(),
                        $this->logLine->getResponseCode(),
                        $this->logLine->getIp()
                    ), ConsoleColour::TEXT_BLUE);

                    return;
                }

                $message = sprintf(
                    "Blocking attempt to access a non-existing malicious URI: %s - %s",
                    $this->logLine->getUrl(),
                    $this->logLine->getIp()
                );

                $this->log($message, ConsoleColour::TEXT_RED);

                throw new AbuseException($message);
            }
        }
    }

    /**
     * @param string $relativePath
     *
     * @return bool
     */
    protected function webPathExists($relativePath)
    {
        $relativePath = ltrim($relativePath, '/ ');
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        $rootPath = $this->getWebDirectoryPath() . DIRECTORY_SEPARATOR . $relativePath;

        if (file_exists($rootPath) || is_dir($rootPath)) {
            return true;
        }

        return false;
    }
}
