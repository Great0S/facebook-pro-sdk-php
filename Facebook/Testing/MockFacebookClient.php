<?php

namespace Facebook\Testing;

use Facebook\FacebookResponse;
use Facebook\FacebookRequest;
use Facebook\FacebookClient;
use Facebook\Logging\FacebookLogger;

class MockFacebookClient extends FacebookClient
{
    private $mockResponses = [];
    private $requestHistory = [];
    private $logger;

    public function __construct(FacebookLogger $logger = null)
    {
        $this->logger = $logger ?: new FacebookLogger();
    }

    /**
     * Mock a response for a specific request pattern
     */
    public function mockResponse($method, $endpoint, $response, $statusCode = 200, $headers = [])
    {
        $pattern = $this->createPattern($method, $endpoint);

        $this->mockResponses[$pattern] = [
            'body' => is_array($response) ? json_encode($response) : $response,
            'status_code' => $statusCode,
            'headers' => $headers
        ];

        $this->logger->debug('Mock response registered', [
            'pattern' => $pattern,
            'status_code' => $statusCode
        ]);
    }

    /**
     * Send request with mocked responses
     */
    public function sendRequest(FacebookRequest $request)
    {
        $this->requestHistory[] = [
            'method' => $request->getMethod(),
            'endpoint' => $request->getEndpoint(),
            'params' => $request->getParams(),
            'timestamp' => microtime(true)
        ];

        $pattern = $this->createPattern($request->getMethod(), $request->getEndpoint());

        if (isset($this->mockResponses[$pattern])) {
            $mock = $this->mockResponses[$pattern];

            $this->logger->debug('Mock response returned', [
                'pattern' => $pattern,
                'status_code' => $mock['status_code']
            ]);

            return new FacebookResponse(
                $request,
                $mock['body'],
                $mock['status_code'],
                $mock['headers']
            );
        }

        // Return default error response if no mock found
        $this->logger->warning('No mock response found', ['pattern' => $pattern]);

        return new FacebookResponse(
            $request,
            json_encode(['error' => ['message' => 'No mock response configured']]),
            404,
            []
        );
    }

    /**
     * Get request history
     */
    public function getRequestHistory()
    {
        return $this->requestHistory;
    }

    /**
     * Get last request
     */
    public function getLastRequest()
    {
        return end($this->requestHistory) ?: null;
    }

    /**
     * Clear request history
     */
    public function clearHistory()
    {
        $this->requestHistory = [];
    }

    /**
     * Assert that a request was made
     */
    public function assertRequestMade($method, $endpoint, $params = null)
    {
        foreach ($this->requestHistory as $request) {
            if (
                $request['method'] === $method &&
                strpos($request['endpoint'], $endpoint) !== false
            ) {

                if ($params === null || $this->paramsMatch($request['params'], $params)) {
                    return true;
                }
            }
        }

        throw new \Exception("Request not found: {$method} {$endpoint}");
    }

    /**
     * Assert request count
     */
    public function assertRequestCount($expected)
    {
        $actual = count($this->requestHistory);

        if ($actual !== $expected) {
            throw new \Exception("Expected {$expected} requests, got {$actual}");
        }

        return true;
    }

    private function createPattern($method, $endpoint)
    {
        return strtoupper($method) . ':' . $endpoint;
    }

    private function paramsMatch($actual, $expected)
    {
        foreach ($expected as $key => $value) {
            if (!isset($actual[$key]) || $actual[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}

class FacebookTestCase
{
    protected $mockClient;
    protected $facebook;
    protected $logger;

    public function setUp()
    {
        $this->logger = new FacebookLogger();
        $this->mockClient = new MockFacebookClient($this->logger);

        // Create Facebook instance with mock client
        $this->facebook = new \Facebook\Facebook([
            'app_id' => 'test_app_id',
            'app_secret' => 'test_app_secret',
            'http_client_handler' => $this->mockClient
        ]);
    }

    public function tearDown()
    {
        $this->mockClient->clearHistory();
    }

    /**
     * Mock a successful API response
     */
    protected function mockApiResponse($method, $endpoint, $data = [])
    {
        $this->mockClient->mockResponse($method, $endpoint, $data, 200);
    }

    /**
     * Mock an API error response
     */
    protected function mockApiError($method, $endpoint, $error = 'Test error', $code = 400)
    {
        $this->mockClient->mockResponse($method, $endpoint, [
            'error' => [
                'message' => $error,
                'code' => $code
            ]
        ], $code);
    }

    /**
     * Assert that an API call was made
     */
    protected function assertApiCallMade($method, $endpoint, $params = null)
    {
        return $this->mockClient->assertRequestMade($method, $endpoint, $params);
    }
}

class FixtureManager
{
    private $fixturePath;

    public function __construct($fixturePath = null)
    {
        $this->fixturePath = $fixturePath ?: __DIR__ . '/fixtures';

        if (!is_dir($this->fixturePath)) {
            mkdir($this->fixturePath, 0755, true);
        }
    }

    /**
     * Load fixture data
     */
    public function load($name)
    {
        $file = $this->fixturePath . '/' . $name . '.json';

        if (!file_exists($file)) {
            throw new \Exception("Fixture not found: {$name}");
        }

        $data = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in fixture: {$name}");
        }

        return $data;
    }

    /**
     * Save fixture data
     */
    public function save($name, $data)
    {
        $file = $this->fixturePath . '/' . $name . '.json';

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Generate mock user data
     */
    public function generateUser($overrides = [])
    {
        $defaults = [
            'id' => '123456789',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Generate mock page data
     */
    public function generatePage($overrides = [])
    {
        $defaults = [
            'id' => '987654321',
            'name' => 'Test Page',
            'category' => 'Business',
            'access_token' => 'test_page_token'
        ];

        return array_merge($defaults, $overrides);
    }
}
