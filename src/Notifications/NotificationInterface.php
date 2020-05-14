<?php

namespace App\Notifications;

/**
 * Interface NotificationInterface
 *
 * @package App\Notifications
 */
interface NotificationInterface
{

    /**
     * @param string                         $message
     * @param array                          $keyValueDetails
     * @param string|null $url
     */
    public function send($message, array $keyValueDetails = [], $url = null);

}
