<?php

namespace App;

use App\Config\Setting;

/**
 * Class Redis
 *
 * @package App
 */
class Redis
{

    /**
     * @return \Redis
     * @throws \Exception
     */
    public static function connection()
    {
        static $client;

        if ($client === null) {
            $client = new \Redis();

            if (!$client->connect(Setting::get('Redis', 'Host'), Setting::get('Redis', 'Port'))) {
                $client = false;

                throw new \Exception('Failed to connect to Redis');
            }

            // We use a prefix to prevent a collision existing Redis keys
            $client->setOption(\Redis::OPT_PREFIX, 'aran112000/nope-php');
        }

        return $client;
    }
}
