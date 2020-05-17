<?php

namespace App\Config;

/**
 * Class Setting
 *
 * @package App\Config
 */
class Setting
{

    /**
     * @param string $block
     * @param string $value
     *
     * @return mixed|null
     */
    public static function get($block, $value)
    {
        static $settings;

        if ($settings === null) {
            $settings = [];

            $settingsFilePath = implode(DIRECTORY_SEPARATOR, [
                __DIR__,
                '..',
                '..',
                'settings.ini',
            ]);

            if ($tmpSettings = parse_ini_file($settingsFilePath, true)) {
                $settings = $tmpSettings;
            }
        }

        if (isset($settings[$block][$value])) {
            return $settings[$block][$value];
        }

        return null;
    }
}
