<?php

namespace App\Helpers;

/**
 * Class Log
 *
 * @package App\Helpers
 */
class Log
{

    /**
     * @param string       $message
     * @param null|string  $colour   - Constant from \App\Helpers\ConsoleColour
     */
    public static function write($message, $colour = null)
    {
        if (!DEBUG_MODE) {
            return;
        }

        $time = date('d/m/Y H:i:s');

        if ($colour === null) {
            echo "[$time] $message\n";

            return;
        }

        echo "[$time] \033[" . $colour . "m$message\033[0m\n";
    }
}