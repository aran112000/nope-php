<?php
error_reporting(E_ALL);
ini_set('display_errors', 'true');

/**
 * When in DEBUG_MODE no abuse exceptions will be thrown and instead
 * constant output will be printed to STD_OUT detailing requests
 * processed.
 */
define('DEBUG_MODE', false);
define('PRINT_OUTPUT', true);

require __DIR__ . '/vendor/autoload.php';

use App\Nope;
use App\Notifications\MicrosoftTeams;
use App\Rules\RateLimitRequests;
use App\Rules\BlockBadUserAgents;
use App\Rules\BlockNonWordpress;
use App\Rules\BlockMaliciousFileRequests;

(new Nope())->monitorLog('/var/log/nginx/access.log', function () {
    yield new RateLimitRequests();
    yield new BlockBadUserAgents();
    yield new BlockNonWordpress();
    yield new BlockMaliciousFileRequests();
}, [
    new MicrosoftTeams()
]);
