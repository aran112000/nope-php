<?php

namespace App;

class Redis
{

    // Your connection details
    const HOST = '127.0.0.1';
    const PORT = '6379';

    // Add a prefix to all our Redis keys to help prevent collisions
    const PREFIX = 'Nope';

    /**
     * @return \Redis|false
     */
    public static function connection()
    {
        static $client;

        if ($client === null) {
            $client = new \Redis();

            if (!$client->connect(static::HOST, self::PORT)) {
                $client = false;

                trigger_error('Failed to connect to Redis');

                return false;
            }

            $client->setOption(\Redis::OPT_PREFIX, static::PREFIX);
        }

        return $client;
    }
}
