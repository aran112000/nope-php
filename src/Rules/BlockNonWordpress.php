<?php

namespace App\Rules;

use App\Exceptions\AbuseException;
use App\Helpers\ConsoleColour;

/**
 * Class BlockNonWordpress
 *
 * @package App\Rules
 */
class BlockNonWordpress extends Rule
{

    /**
     * @return void
     *
     * @throws \App\Exceptions\AbuseException
     */
    public function run()
    {
        if (!$this->isWordpressRequest()) {
            // This isn't a Wordpress request, so it should be completely ignored

            return;
        }

        if (!$this->getWebDirectoryPath()) {
            // Can't get the vhost directory, so we can't check
            return;
        }

        if ($this->isWordpressWebsite()) {
            // This is actually a Wordpress website on this server, so allow
            $this->log($this->logLine->getHost() . ' is a Wordpress website, allowing', ConsoleColour::TEXT_GREEN);

            return;
        }

        $this->log(
            $this->logLine->getHost() . ' is not a Wordpress website, blocking: ' . $this->logLine->getIp(),
            ConsoleColour::TEXT_RED
        );

        throw new AbuseException($this->logLine->getHost() . ' is not a Wordpress website');
    }

    /**
     * @return bool
     */
    protected function isWordpressWebsite()
    {
        if (
            file_exists($this->getWebDirectoryPath() . DIRECTORY_SEPARATOR . '/wp-config.php') ||
            file_exists($this->getWebDirectoryPath() . DIRECTORY_SEPARATOR . '/../wp-config.php')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isWordpressRequest()
    {
        $uri = $this->logLine->getUri();

        if (stristr($uri, '/wp-admin')) {
            return true;
        }

        if (stristr($uri, '/wp-login.php')) {
            return true;
        }

        if (stristr($uri, '/wp-content')) {
            return true;
        }

        return false;
    }
}
