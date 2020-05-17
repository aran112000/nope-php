<?php

require __DIR__ . '/vendor/autoload.php';

// Specify a log file to monitor
(new App\Logs\Log('/var/log/nginx/access.log'))
    ->rules([
        // Our rules to apply to this log
        new App\Rules\RateLimitRequests(),
        new App\Rules\BlockBadUserAgents(),
        new App\Rules\BlockMaliciousFileRequests(),
    ])
    ->notify([
        // Notify our Microsoft Teams channel of any blocks (define your Incoming Webhook URL in settings.ini)
        new App\Notifications\MicrosoftTeams(),
    ])
    ->monitorAndBlock();
