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
     * @param string      $message
     * @param string[]    $keyValueDetails
     * @param string|null $url
     *
     * @return void
     */
    public function send($message, array $keyValueDetails = [], $url = null);
}
