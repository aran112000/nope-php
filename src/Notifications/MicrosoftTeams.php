<?php

namespace App\Notifications;

use App\Config\Setting;

/**
 * Class MicrosoftTeams
 *
 * @package App\Notifications
 */
class MicrosoftTeams implements NotificationInterface
{

    /**
     * @param string $message
     * @param array  $keyValueDetails
     */
    public function send($message, array $keyValueDetails = [])
    {
        if ($keyValueDetails) {
            $details = [];
            foreach ($keyValueDetails as $key => $value) {
                $details[] = [
                    'name' => $key,
                    'value' => $value,
                ];
            }

            $messageBody = [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'title' => $message,
                'summary' => $message,
                'themeColor' => 'FF0000',
                'sections' => [
                    [
                        'title' => '**' . $message . '**',
                        'facts' => $details,
                    ]
                ],
            ];
        } else {
            $messageBody = [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'title' => $message,
                'summary' => $message,
                'themeColor' => 'FF0000',
            ];
        }

        $ch = curl_init(Setting::get('MicrosoftTeams', 'WebhookURL'));
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($messageBody),
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}