<?php

namespace Facebook\Cache;

use Facebook\FacebookClient;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\Logging\FacebookLogger;

class CachedFacebookClient extends FacebookClient
{
    private $cache;
    private $logger;
    private $defaultTtl = 3600; // 1 hour

    public function __construct($httpClientHandler = null, CacheInterface $cache = null, FacebookLogger $logger = null)
    {
        parent::__construct($httpClientHandler);
        $this->cache = $cache ?: new MemoryCache();
        $this->logger = $logger ?: new FacebookLogger();
    }

    public function sendRequest(FacebookRequest $request)
    {
        $cacheKey = $this->generateCacheKey($request);

        // Only cache GET requests
        if ($request->getMethod() === 'GET') {
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                $this->logger->debug('Cache hit', ['cache_key' => $cacheKey]);
                return $this->deserializeResponse($cached);
            }
        }

        // Send actual request
        $response = parent::sendRequest($request);

        // Cache successful GET responses
        if ($request->getMethod() === 'GET' && $response->getHttpStatusCode() === 200) {
            $ttl = $this->determineTtl($request);
            $this->cache->set($cacheKey, $this->serializeResponse($response), $ttl);
            $this->logger->debug('Response cached', [
                'cache_key' => $cacheKey,
                'ttl' => $ttl
            ]);
        }

        return $response;
    }

    public function clearCache()
    {
        $this->cache->clear();
        $this->logger->info('Cache cleared');
    }

    public function invalidateCache($pattern = null)
    {
        if ($pattern === null) {
            $this->clearCache();
            return;
        }

        // This would require a more sophisticated cache implementation
        // For now, just clear all
        $this->clearCache();
    }

    private function generateCacheKey(FacebookRequest $request)
    {
        $key = $request->getMethod() . ':' .
            $request->getUrl() . ':' .
            serialize($request->getParams());

        return md5($key);
    }

    private function serializeResponse(FacebookResponse $response)
    {
        return [
            'body' => $response->getBody(),
            'headers' => $response->getHeaders(),
            'http_status_code' => $response->getHttpStatusCode()
        ];
    }

    private function deserializeResponse($cached)
    {
        // Create a mock request for the response
        $mockRequest = new FacebookRequest(
            new \Facebook\FacebookApp('mock', 'mock'),
            'mock',
            'GET',
            '/'
        );

        return new FacebookResponse(
            $mockRequest,
            $cached['body'],
            $cached['http_status_code'],
            $cached['headers']
        );
    }

    private function determineTtl(FacebookRequest $request)
    {
        $endpoint = $request->getEndpoint();

        // Different cache durations for different endpoints
        $ttlMap = [
            '/me' => 1800,        // 30 minutes
            '/pages' => 3600,     // 1 hour
            '/posts' => 300,      // 5 minutes
            '/comments' => 300,   // 5 minutes
            '/likes' => 600,      // 10 minutes
        ];

        foreach ($ttlMap as $pattern => $ttl) {
            if (strpos($endpoint, $pattern) !== false) {
                return $ttl;
            }
        }

        return $this->defaultTtl;
    }
}
