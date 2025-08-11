<?php

namespace Facebook\Http;

use Facebook\Logging\FacebookLogger;
use Facebook\Exceptions\FacebookThrottleException;
use Facebook\Exceptions\FacebookServerException;

class RetryHandler
{
    private $maxRetries;
    private $baseDelay;
    private $maxDelay;
    private $logger;

    public function __construct($maxRetries = 3, $baseDelay = 1000, $maxDelay = 30000, FacebookLogger $logger = null)
    {
        $this->maxRetries = $maxRetries;
        $this->baseDelay = $baseDelay; // milliseconds
        $this->maxDelay = $maxDelay; // milliseconds
        $this->logger = $logger ?: new FacebookLogger();
    }

    /**
     * Execute a callable with retry logic
     */
    public function execute(callable $callback, array $retryableExceptions = [])
    {
        $defaultRetryable = [
            'Facebook\Exceptions\FacebookThrottleException',
            'Facebook\Exceptions\FacebookServerException'
        ];

        $retryableExceptions = array_merge($defaultRetryable, $retryableExceptions);
        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            try {
                $result = call_user_func($callback);

                if ($attempt > 0) {
                    $this->logger->info("Request succeeded after {$attempt} retries");
                }

                return $result;
            } catch (\Exception $e) {
                $attempt++;

                if (!$this->shouldRetry($e, $retryableExceptions) || $attempt > $this->maxRetries) {
                    $this->logger->error("Request failed permanently", [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'attempts' => $attempt
                    ]);
                    throw $e;
                }

                $delay = $this->calculateDelay($attempt);
                $this->logger->warning("Request failed, retrying in {$delay}ms", [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxRetries + 1
                ]);

                usleep($delay * 1000); // Convert to microseconds
            }
        }
    }

    private function shouldRetry(\Exception $e, array $retryableExceptions)
    {
        foreach ($retryableExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }
        return false;
    }

    private function calculateDelay($attempt)
    {
        // Exponential backoff with jitter
        $exponentialDelay = $this->baseDelay * pow(2, $attempt - 1);
        $jitter = mt_rand(0, $exponentialDelay * 0.1); // 10% jitter
        $delay = min($exponentialDelay + $jitter, $this->maxDelay);

        return $delay;
    }
}
