<?php

namespace App;

/**
 * Class LogLine
 *
 * @package App
 */
class LogLine
{

    /**
     * @var string
     */
    private $logLine;
    /**
     * @var array
     */
    protected $requestDetails;
    /**
     * @var string|false
     * */
    protected $requestTime;
    /**
     * @var string|false
     * */
    protected $url;
    /**
     * @var string|false
     * */
    protected $method;
    /**
     * @var string|false
     * */
    protected $protocol;
    /**
     * @var string|false
     * */
    protected $host;
    /**
     * @var string|false
     * */
    protected $domain;
    /**
     * @var string|false
     * */
    protected $uri;
    /**
     * @var string|false
     * */
    protected $uriNoQueryString;
    /**
     * @var string|false
     * */
    protected $queryString;
    /**
     * @var array|false
     * */
    protected $queryStringArray;
    /**
     * @var string|false
     * */
    protected $contentType;
    /**
     * @var int|false
     * */
    protected $responseCode;
    /**
     * @var string|false
     * */
    protected $ipRaw;
    /**
     * @var string|false
     * */
    protected $xForwardFor;
    /**
     * @var string|false
     * */
    protected $ip;
    /**
     * @var string|false
     * */
    protected $ipHost;
    /**
     * @var int|false
     * */
    protected $bytesSent;
    /**
     * @var string|false
     * */
    protected $referrer;
    /**
     * @var string|false
     * */
    protected $userAgent;

    /**
     * @return array|bool
     */
    protected function setRequestDetails()
    {
        if ($this->requestDetails === null) {
            $this->requestDetails = false;

            $this->requestTime = false;
            $this->url = false;
            $this->method = false;
            $this->protocol = false;
            $this->host = false;
            $this->domain = false;
            $this->uri = false;
            $this->uriNoQueryString = false;
            $this->queryString = false;
            $this->queryStringArray = false;
            $this->contentType = false;
            $this->responseCode = false;
            $this->ipRaw = false;
            $this->xForwardFor = false;
            $this->ip = false;
            $this->bytesSent = false;
            $this->referrer = false;
            $this->userAgent = false;

            if (preg_match('#\[(?<time>.*?)]\s+(?<method>GET|HEAD|POST|PATCH|PUT|DELETE)\s+(?<protocol>https?)://(?<host>.*?)(?<uri>/.*?)\s+"(?<protocol_uri>.*?)"\s+"(?<response_code>.*?)"\s+"(?<x_forward_for>.*?)"\s+"(?<remote_addr>.*?)"\s+"(?<remote_user>.*?)"\s+"(?<bytes_sent>.*?)"\s+"(?<referrer>.*?)"\s+"(?<user_agent>.*?)"\s+"(?<content_type>.*?)"#i', $this->getLogLine(), $matches)) {

                // Set query string parts
                $uriParts = explode('?', $matches['uri'], 2);
                $uriNoQueryString = $uriParts[0];
                $queryString = '';
                if (!empty($uriParts[1])) {
                    $queryString = $uriParts[1];
                }
                parse_str($queryString, $queryStringArray);

                // Get mimeType (column typically contains encoding too, so we need to split them)
                list($contentType) = explode(';', trim(strtolower($matches['content_type'])));

                $this->requestDetails = [
                    'request_time' => trim($matches['time']),
                    'url' => trim($matches['protocol']) . '://' . trim($matches['host']) . (isset($matches['uri']) ? trim($matches['uri']) : ''),
                    'method' => trim(strtoupper($matches['method'])),
                    'protocol' => trim(strtoupper($matches['protocol'])),
                    'host' => trim($matches['host']),
                    'domain' => trim(str_replace('www.', '', $matches['host'])),
                    'uri' => trim($matches['uri']),
                    'uri_no_query_string' => $uriNoQueryString,
                    'query_string' => $queryString,
                    'query_string_array' => $queryStringArray,
                    'content_type' => $contentType,
                    'response_code' => (int) $matches['response_code'],
                    'ip_raw' => $matches['remote_addr'],
                    'x_forward_for' => (!empty($matches['x_forward_for']) && $matches['x_forward_for'] !== '-' ? $matches['x_forward_for'] : ''),
                    'ip' => (!empty($matches['x_forward_for'] && $matches['x_forward_for'] !== '-') ? $matches['x_forward_for'] : $matches['remote_addr']),
                    'bytes_sent' => (int) $matches['bytes_sent'],
                    'referrer' => (!empty($matches['referrer']) && $matches['referrer'] !== '-' ? $matches['referrer'] : ''),
                    'user_agent' => (!empty($matches['user_agent']) && $matches['user_agent'] !== '-' ? $matches['user_agent'] : ''),
                ];

                $this->requestTime = $this->requestDetails['request_time'];
                $this->url = $this->requestDetails['url'];
                $this->method = $this->requestDetails['method'];
                $this->protocol = $this->requestDetails['protocol'];
                $this->host = $this->requestDetails['host'];
                $this->domain = $this->requestDetails['domain'];
                $this->uri = $this->requestDetails['uri'];
                $this->uriNoQueryString = $this->requestDetails['uri_no_query_string'];
                $this->queryString = $this->requestDetails['query_string'];
                $this->queryStringArray = $this->requestDetails['query_string_array'];
                $this->contentType = $this->requestDetails['content_type'];
                $this->responseCode = $this->requestDetails['response_code'];
                $this->ipRaw = $this->requestDetails['ip_raw'];
                $this->xForwardFor = $this->requestDetails['x_forward_for'];
                $this->ip = $this->requestDetails['ip'];
                $this->bytesSent = $this->requestDetails['bytes_sent'];
                $this->referrer = $this->requestDetails['referrer'];
                $this->userAgent = $this->requestDetails['user_agent'];
            }
        }

        return $this->requestDetails;
    }

    /**
     * @return string|false
     */
    public function getRequestTime()
    {
        if ($this->requestTime === null) {
            $this->setRequestDetails();
        }

        return $this->requestTime;
    }

    /**
     * @return string|false
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->setRequestDetails();
        }

        return $this->url;
    }

    /**
     * @return string|false
     */
    public function getMethod()
    {
        if ($this->method === null) {
            $this->setRequestDetails();
        }

        return $this->method;
    }

    /**
     * @return string|false
     */
    public function getProtocol()
    {
        if ($this->protocol === null) {
            $this->setRequestDetails();
        }

        return $this->protocol;
    }

    /**
     * @return string|false
     */
    public function getHost()
    {
        if ($this->host === null) {
            $this->setRequestDetails();
        }

        return $this->host;
    }

    /**
     * @return string|false
     */
    public function getDomain()
    {
        if ($this->domain === null) {
            $this->setRequestDetails();
        }

        return $this->domain;
    }

    /**
     * @return string|false
     */
    public function getUri()
    {
        if ($this->uri === null) {
            $this->setRequestDetails();
        }

        return $this->uri;
    }

    /**
     * @return string|false
     */
    public function getUriNoQueryString()
    {
        if ($this->uriNoQueryString === null) {
            $this->setRequestDetails();
        }

        return $this->uriNoQueryString;
    }

    /**
     * @return string|false
     */
    public function getQueryString()
    {
        if ($this->queryString === null) {
            $this->setRequestDetails();
        }

        return $this->queryString;
    }

    /**
     * @return string|false
     */
    public function getQueryStringArray()
    {
        if ($this->queryStringArray === null) {
            $this->setRequestDetails();
        }

        return $this->queryStringArray;
    }

    /**
     * @return string|false
     */
    public function getContentType()
    {
        if ($this->contentType === null) {
            $this->setRequestDetails();
        }

        return $this->contentType;
    }

    /**
     * Alias of getContentType
     *
     * @return string|false
     */
    public function getMimeType()
    {
        return $this->getContentType();
    }

    /**
     * @return string|false
     */
    public function getResponseCode()
    {
        if ($this->responseCode === null) {
            $this->setRequestDetails();
        }

        return $this->responseCode;
    }

    /**
     * @return string|false
     */
    public function getIpRaw()
    {
        if ($this->ipRaw === null) {
            $this->setRequestDetails();
        }

        return $this->ipRaw;
    }

    /**
     * @return string|false
     */
    public function getXForwardFor()
    {
        if ($this->xForwardFor === null) {
            $this->setRequestDetails();
        }

        return $this->xForwardFor;
    }

    /**
     * @return string|false
     */
    public function getIp()
    {
        if ($this->ip === null) {
            $this->setRequestDetails();
        }

        return $this->ip;
    }

    /**
     * Set lazy unlike other properties to avoid the overhead of the reverse DNS
     * lookup unless it's actually needed for a rule
     *
     * @return string|false
     */
    public function getIpHost()
    {
        if ($this->ipHost === null) {
            $this->ipHost = gethostbyaddr($this->getIp());
        }

        return $this->ipHost;
    }

    /**
     * @return string|false
     */
    public function getBytesSent()
    {
        if ($this->bytesSent === null) {
            $this->setRequestDetails();
        }

        return $this->bytesSent;
    }

    /**
     * @return string|false
     */
    public function getReferrer()
    {
        if ($this->referrer === null) {
            $this->setRequestDetails();
        }

        return $this->referrer;
    }

    /**
     * @return string|false
     */
    public function getUserAgent()
    {
        if ($this->userAgent === null) {
            $this->setRequestDetails();
        }

        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function getLogLine()
    {
        return $this->logLine;
    }

    /**
     * @param string $logLine
     */
    public function setLogLine($logLine)
    {
        $this->logLine = $logLine;
    }
}
