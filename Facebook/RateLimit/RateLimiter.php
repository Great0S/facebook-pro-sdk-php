<?php

namespace Facebook\RateLimit;

use Facebook\Logging\FacebookLogger;
use Facebook\Exceptions\FacebookThrottleException;

class RateLimiter
{
    private $storage;
    private $logger;
    private $defaultLimits;

    public function __construct(RateLimitStorageInterface $storage = null, FacebookLogger $logger = null)
    {
        $this->storage = $storage ?: new MemoryRateLimitStorage();
        $this->logger = $logger ?: new FacebookLogger();

        // Default Facebook API limits
        $this->defaultLimits = [
            'app' => ['calls' => 200, 'window' => 3600], // 200 calls per hour
            'user' => ['calls' => 600, 'window' => 3600], // 600 calls per hour
            'page' => ['calls' => 4800, 'window' => 3600] // 4800 calls per hour
        ];
    }

    /**
     * Check if request is allowed
     */
    public function isAllowed($key, $type = 'app')
    {
        $limit = $this->defaultLimits[$type] ?? $this->defaultLimits['app'];
        $current = $this->storage->get($key);

        if ($current === null) {
            return true;
        }

        // Check if window has expired
        if (time() - $current['timestamp'] > $limit['window']) {
            $this->storage->delete($key);
            return true;
        }

        return $current['count'] < $limit['calls'];
    }

    /**
     * Record a request
     */
    public function recordRequest($key, $type = 'app')
    {
        $limit = $this->defaultLimits[$type] ?? $this->defaultLimits['app'];
        $current = $this->storage->get($key);
        $now = time();

        if ($current === null || ($now - $current['timestamp']) > $limit['window']) {
            // Start new window
            $this->storage->set($key, [
                'count' => 1,
                'timestamp' => $now,
                'type' => $type
            ]);
        } else {
            // Increment counter
            $current['count']++;
            $this->storage->set($key, $current);
        }

        $this->logger->debug('Rate limit recorded', [
            'key' => $key,
            'type' => $type,
            'count' => $current['count'] ?? 1
        ]);
    }

    /**
     * Get remaining calls
     */
    public function getRemainingCalls($key, $type = 'app')
    {
        $limit = $this->defaultLimits[$type] ?? $this->defaultLimits['app'];
        $current = $this->storage->get($key);

        if ($current === null) {
            return $limit['calls'];
        }

        // Check if window has expired
        if (time() - $current['timestamp'] > $limit['window']) {
            return $limit['calls'];
        }

        return max(0, $limit['calls'] - $current['count']);
    }

    /**
     * Wait if rate limited
     */
    public function waitIfNeeded($key, $type = 'app')
    {
        if (!$this->isAllowed($key, $type)) {
            $limit = $this->defaultLimits[$type] ?? $this->defaultLimits['app'];
            $current = $this->storage->get($key);
            $waitTime = $limit['window'] - (time() - $current['timestamp']);

            $this->logger->warning('Rate limit exceeded, waiting', [
                'key' => $key,
                'type' => $type,
                'wait_time' => $waitTime
            ]);

            if ($waitTime > 0) {
                sleep($waitTime);
            }
        }
    }

    /**
     * Parse rate limit headers from Facebook response
     */
    public function parseHeaders($headers)
    {
        $rateLimitInfo = [];

        foreach ($headers as $header) {
            if (strpos($header, 'X-App-Usage:') === 0) {
                $usage = json_decode(substr($header, 13), true);
                $rateLimitInfo['app_usage'] = $usage;
            } elseif (strpos($header, 'X-Page-Usage:') === 0) {
                $usage = json_decode(substr($header, 14), true);
                $rateLimitInfo['page_usage'] = $usage;
            } elseif (strpos($header, 'X-Ad-Account-Usage:') === 0) {
                $usage = json_decode(substr($header, 20), true);
                $rateLimitInfo['ad_account_usage'] = $usage;
            }
        }

        return $rateLimitInfo;
    }
}

interface RateLimitStorageInterface
{
    public function get($key);
    public function set($key, $value);
    public function delete($key);
}

class MemoryRateLimitStorage implements RateLimitStorageInterface
{
    private $data = [];

    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }
}
