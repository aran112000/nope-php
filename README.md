# Nope!
The log processing framework for blocking those pesky bots!

Designed as a fully customisable framework to process your log files in realtime block any activity identified as malicious using `iptables` and `iplist`.

## Installation
If you're using composer, simply run:
```console 
composer require aran112000/phpnope
```

## Current expected Nginx access log format
```console
'[$time_local] $request_method $scheme://$host$request_uri "$request" "$status" "$http_x_forwarded_for" "$remote_addr" "$remote_user" "$bytes_sent" "$http_referer" "$http_user_agent" "$sent_http_content_type"'
```

## Requirements
 * PHP >= 5.6.*
 * PHP Redis extension installed _(if you want to track hits over time)_
 * Log files for us to process in realtime

### Getting pesky /wp-admin requests?
Check if your vhost supports Wordpress in realtime and if not, respond blocking the IP for a length of time you dictate.
