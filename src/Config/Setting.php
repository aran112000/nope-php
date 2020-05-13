<?php

namespace App\Config;

/**
 * Class Setting
 *
 * @package App\Config
 */
class Setting
{

    const SETTING_FILE = 'settings.ini';

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
            if ($tmpSettings = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . static::SETTING_FILE, true)) {
                $settings = $tmpSettings;
            }
        }

        if (isset($settings[$block][$value])) {
            return $settings[$block][$value];
        }

        return null;
    }
}