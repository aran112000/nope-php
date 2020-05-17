<?php

namespace App\Helpers;

use App\Config\Setting;

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
     *
     * @return void
     */
    public static function write($message, $colour = null)
    {
        if (Setting::get('General', 'MuteConsoleOutput')) {
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
