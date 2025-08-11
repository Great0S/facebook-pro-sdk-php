<?php

namespace Facebook;

use Facebook\Logging\FacebookLogger;
use Facebook\Cache\CachedFacebookClient;
use Facebook\Cache\MemoryCache;
use Facebook\Http\RetryHandler;
use Facebook\RateLimit\RateLimiter;
use Facebook\Performance\PerformanceMonitor;
use Facebook\Config\Configuration;
use Facebook\Development\DebugToolbar;
use Facebook\Async\AsyncFacebookClient;
use Facebook\Webhooks\WebhookReceiver;

class FacebookSDK extends Facebook
{
    private $logger;
    private $cache;
    private $retryHandler;
    private $rateLimiter;
    private $monitor;
    private $config;
    private $debugToolbar;
    private $asyncClient;
    private $webhookReceiver;

    public function __construct(array $config = [])
    {
        // Initialize configuration
        $this->config = new Configuration($config);

        // Initialize logger
        $this->logger = new FacebookLogger(
            $this->config->get('logging.file'),
            $this->config->get('logging.level')
        );

        // Initialize performance monitor
        $this->monitor = new PerformanceMonitor($this->logger);

        // Initialize cache
        $this->cache = new MemoryCache();

        // Initialize advanced client with caching
        $cachedClient = new CachedFacebookClient(null, $this->cache, $this->logger);

        // Initialize retry handler
        $this->retryHandler = new RetryHandler(
            $this->config->get('rate_limit.max_retries'),
            $this->config->get('rate_limit.base_delay'),
            30000,
            $this->logger
        );

        // Initialize rate limiter
        $this->rateLimiter = new RateLimiter(null, $this->logger);

        // Initialize debug toolbar
        $this->debugToolbar = new DebugToolbar($this->logger, $this->monitor);

        // Initialize async client (if available)
        if (class_exists('Facebook\\Async\\AsyncFacebookClient')) {
            $this->asyncClient = new AsyncFacebookClient($cachedClient, $this->logger);
        }

        // Initialize webhook receiver if configured
        if ($this->config->get('webhook.secret') && $this->config->get('webhook.verify_token')) {
            $this->webhookReceiver = new WebhookReceiver(
                $this->config->get('webhook.secret'),
                $this->config->get('webhook.verify_token'),
                $this->logger
            );
        }

        // Call parent constructor
        parent::__construct([
            'app_id' => $this->config->get('app.id'),
            'app_secret' => $this->config->get('app.secret'),
            'default_graph_version' => $this->config->get('app.version')
        ]);

        $this->logger->info('Advanced Facebook SDK initialized', [
            'version' => $this->config->get('app.version'),
            'cache_enabled' => $this->config->get('cache.enabled'),
            'debug_enabled' => $this->debugToolbar !== null
        ]);
    }

    /**
     * Advanced GET request with retry and rate limiting
     */
    public function get($endpoint, $accessToken = null, $eTag = null, $graphVersion = null)
    {
        return $this->advancedRequest('GET', $endpoint, [], $accessToken, $eTag, $graphVersion);
    }

    /**
     * Advanced POST request with retry and rate limiting
     */
    public function post($endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        return $this->advancedRequest('POST', $endpoint, $params, $accessToken, $eTag, $graphVersion);
    }

    /**
     * Async GET request
     */
    public function getAsync($endpoint, array $params = [])
    {
        if (!$this->asyncClient) {
            throw new \Exception('Async client not available');
        }
        return $this->asyncClient->getAsync($endpoint, $params);
    }

    /**
     * Async POST request
     */
    public function postAsync($endpoint, array $params = [])
    {
        if (!$this->asyncClient) {
            throw new \Exception('Async client not available');
        }
        return $this->asyncClient->postAsync($endpoint, $params);
    }

    /**
     * Batch async requests
     */
    public function batchAsync(array $requests)
    {
        if (!$this->asyncClient) {
            throw new \Exception('Async client not available');
        }
        return $this->asyncClient->batch($requests);
    }

    /**
     * Get webhook receiver
     */
    public function webhooks()
    {
        if (!$this->webhookReceiver) {
            throw new \Exception('Webhook receiver not configured. Set webhook.secret and webhook.verify_token in config.');
        }

        return $this->webhookReceiver;
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats()
    {
        return $this->monitor->generateReport();
    }

    /**
     * Get debug toolbar HTML
     */
    public function getDebugToolbar()
    {
        return $this->debugToolbar->getDebugPanel();
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->cache->clear();
        $this->logger->info('Cache cleared');
    }

    /**
     * Get configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Advanced request with all features
     */
    private function advancedRequest($method, $endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        $operationName = "{$method} {$endpoint}";

        return $this->monitor->measure($operationName, function () use ($method, $endpoint, $params, $accessToken, $eTag, $graphVersion) {
            return $this->retryHandler->execute(function () use ($method, $endpoint, $params, $accessToken, $eTag, $graphVersion) {

                // Check rate limits
                $rateLimitKey = $accessToken ? "user:{$accessToken}" : "app";
                $this->rateLimiter->waitIfNeeded($rateLimitKey);

                // Make the actual request
                $startTime = microtime(true);

                if ($method === 'GET') {
                    $response = parent::get($endpoint, $accessToken, $eTag, $graphVersion);
                } else {
                    $response = parent::post($endpoint, $params, $accessToken, $eTag, $graphVersion);
                }

                $duration = microtime(true) - $startTime;

                // Record rate limit usage
                $this->rateLimiter->recordRequest($rateLimitKey);

                // Log to debug toolbar
                $this->debugToolbar->logRequest($method, $endpoint, $params, $response, $duration);

                return $response;
            });
        });
    }
}

// Factory function for easy initialization
function createFacebookSDK(array $config = [])
{
    return new FacebookSDK($config);
}
