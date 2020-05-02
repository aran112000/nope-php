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
     * List if URIs to block if they don't exist within your vhost.
     * These URIs require an exact match (minus any query string!),
     * but are compared case insensitively.
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
    ];

    /**
     * @throws \App\Exceptions\AbuseException
     */
    public function run()
    {
        foreach (static::BLOCK_URIS_IF_NOT_EXISTING as $relativePath) {
            if ($this->logLine->getUriNoQueryString() === $relativePath) {
                // This is a flagged path!
                $this->log('Attempt to access a malicious URI: ' . $this->logLine->getUrl() . ' - ' . $this->logLine->getIp(), ConsoleColour::TEXT_BLUE);

                if ($this->webPathExists($relativePath)) {
                    // As this is actually a valid path within the vhost, we'll allow it
                    $this->log('Allowing attempt to access a malicious URI because it exists in the vhost: ' . $this->logLine->getUrl() . ' - ' . $this->logLine->getIp());

                    return;
                }

                $message = 'Blocking attempt to access a non-existing malicious URI: ' . $this->logLine->getUrl() . ' - ' . $this->logLine->getIp();

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

        $rootPath = $this->getWebDirectoryPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (file_exists($rootPath) || is_dir($rootPath)) {
            return true;
        }

        return false;
    }
}