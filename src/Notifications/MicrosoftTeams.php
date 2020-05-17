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
     * @param string      $message
     * @param string[]    $keyValueDetails
     * @param string|null $url
     *
     * @return void
     */
    public function send($message, array $keyValueDetails = [], $url = null)
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

            if ($url) {
                $messageBody['potentialAction'] = [
                    [
                        '@type' => 'OpenUri',
                        'name' => 'Open in browser',
                        'targets' => [
                            [
                                'os' => 'default',
                                'uri' => $url,
                            ],
                        ],
                    ],
                ];
            }
        } else {
            $messageBody = [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'title' => $message,
                'summary' => $message,
                'themeColor' => 'FF0000',
            ];
        }

        if ($ch = curl_init(Setting::get('MicrosoftTeams', 'WebhookURL'))) {
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
}
