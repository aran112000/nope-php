<?php
require __DIR__ . '/vendor/autoload.php';

$nginxAccessLog = new App\Logs\Log('/var/log/nginx/access.log');
$nginxAccessLog
    ->rules([
        new App\Rules\RateLimitRequests(),
        new App\Rules\BlockBadUserAgents(),
        new App\Rules\BlockNonWordpress(),
        new App\Rules\BlockMaliciousFileRequests(),
    ])
    ->whitelist(function (App\Logs\LogLine $logLine) {
        if (strstr($logLine->getUrl(), 'johnpacker.co.uk/products/json/')) {
            return 'John Packer Prisync feed fetch';
        }

        return false;
    })
    ->notify([
        new App\Notifications\MicrosoftTeams(),
    ])
    ->monitorAndBlock();
