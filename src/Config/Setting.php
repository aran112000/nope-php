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
                'settings.json',
            ]);

            if ($json = file_get_contents($settingsFilePath)) {
                if ($tmpSettings = json_decode($json, true)) {
                    $settings = $tmpSettings;
                }
            }
        }

        if (isset($settings[$block][$value])) {
            return $settings[$block][$value];
        }

        return null;
    }
}
