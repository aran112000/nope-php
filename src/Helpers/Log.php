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
            echo sprintf("[%s] %s\n", $time, $message);

            return;
        }

        echo sprintf("[%s] \033[%sm%s\033[0m\n", $time, $colour, $message);
    }
}
