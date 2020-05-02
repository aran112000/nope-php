<?php

namespace App\Rules;

use App\Config\IpAddress;
use App\ConsoleColour;
use App\Exceptions\AbuseException;

/**
 * Class BlockNonWordpress
 *
 * @package App\Rules
 */
class BlockNonWordpress extends Rule
{

    /**
     *
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
            $this->outputDebug($this->getHost() . ' is a Wordpress website, allowing', ConsoleColour::TEXT_GREEN);

            return;
        }

        throw new AbuseException($this->getHost() . ' is not a Wordpress website');
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

    protected function isWordpressRequest()
    {
        $uri = $this->getUri();

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