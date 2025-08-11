<?php

namespace Facebook\Async;

use Facebook\Logging\FacebookLogger;

interface PromiseInterface
{
    public function then(callable $onFulfilled = null, callable $onRejected = null);
    public function catch(callable $onRejected);
    public function finally(callable $onFinally);
}

class Promise implements PromiseInterface
{
    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    private $state = self::PENDING;
    private $value;
    private $reason;
    private $onFulfilled = [];
    private $onRejected = [];
    private $onFinally = [];

    public function __construct(callable $executor = null)
    {
        if ($executor) {
            try {
                $executor(
                    function ($value) {
                        $this->resolve($value);
                    },
                    function ($reason) {
                        $this->reject($reason);
                    }
                );
            } catch (\Exception $e) {
                $this->reject($e);
            }
        }
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        $promise = new Promise();

        $this->onFulfilled[] = function ($value) use ($promise, $onFulfilled) {
            if ($onFulfilled) {
                try {
                    $result = $onFulfilled($value);
                    $promise->resolve($result);
                } catch (\Exception $e) {
                    $promise->reject($e);
                }
            } else {
                $promise->resolve($value);
            }
        };

        $this->onRejected[] = function ($reason) use ($promise, $onRejected) {
            if ($onRejected) {
                try {
                    $result = $onRejected($reason);
                    $promise->resolve($result);
                } catch (\Exception $e) {
                    $promise->reject($e);
                }
            } else {
                $promise->reject($reason);
            }
        };

        $this->executeHandlers();

        return $promise;
    }

    public function catch(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    public function finally(callable $onFinally)
    {
        $this->onFinally[] = $onFinally;
        $this->executeHandlers();
        return $this;
    }

    public function resolve($value)
    {
        if ($this->state === self::PENDING) {
            $this->state = self::FULFILLED;
            $this->value = $value;
            $this->executeHandlers();
        }
    }

    public function reject($reason)
    {
        if ($this->state === self::PENDING) {
            $this->state = self::REJECTED;
            $this->reason = $reason;
            $this->executeHandlers();
        }
    }

    private function executeHandlers()
    {
        if ($this->state === self::FULFILLED) {
            foreach ($this->onFulfilled as $handler) {
                $handler($this->value);
            }
            $this->onFulfilled = [];
        } elseif ($this->state === self::REJECTED) {
            foreach ($this->onRejected as $handler) {
                $handler($this->reason);
            }
            $this->onRejected = [];
        }

        if ($this->state !== self::PENDING) {
            foreach ($this->onFinally as $handler) {
                $handler();
            }
            $this->onFinally = [];
        }
    }

    public static function resolved($value)
    {
        $promise = new Promise();
        $promise->resolve($value);
        return $promise;
    }

    public static function rejected($reason)
    {
        $promise = new Promise();
        $promise->reject($reason);
        return $promise;
    }

    public static function all(array $promises)
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            $results = [];
            $remaining = count($promises);

            if ($remaining === 0) {
                $resolve([]);
                return;
            }

            foreach ($promises as $index => $promise) {
                $promise->then(
                    function ($value) use (&$results, &$remaining, $index, $resolve) {
                        $results[$index] = $value;
                        $remaining--;

                        if ($remaining === 0) {
                            $resolve($results);
                        }
                    },
                    function ($reason) use ($reject) {
                        $reject($reason);
                    }
                );
            }
        });
    }
}

class AsyncFacebookClient
{
    private $client;
    private $logger;

    public function __construct($client, FacebookLogger $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger ?: new FacebookLogger();
    }

    public function getAsync($endpoint, array $params = [])
    {
        return $this->requestAsync('GET', $endpoint, $params);
    }

    public function postAsync($endpoint, array $params = [])
    {
        return $this->requestAsync('POST', $endpoint, $params);
    }

    public function requestAsync($method, $endpoint, array $params = [])
    {
        return new Promise(function ($resolve, $reject) use ($method, $endpoint, $params) {
            try {
                // Simulate async behavior - in real implementation this would use
                // curl_multi_* functions or similar async HTTP library
                $this->logger->debug('Async request started', [
                    'method' => $method,
                    'endpoint' => $endpoint
                ]);

                // For demo purposes, execute synchronously
                // In production, this would be truly asynchronous
                $request = new \Facebook\FacebookRequest(
                    new \Facebook\FacebookApp('demo', 'demo'),
                    'demo',
                    $method,
                    $endpoint,
                    $params
                );

                $response = $this->client->sendRequest($request);

                $this->logger->debug('Async request completed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->getHttpStatusCode()
                ]);

                $resolve($response);
            } catch (\Exception $e) {
                $this->logger->error('Async request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ]);

                $reject($e);
            }
        });
    }

    public function batch(array $requests)
    {
        $promises = [];

        foreach ($requests as $key => $request) {
            $promises[$key] = $this->requestAsync(
                $request['method'] ?? 'GET',
                $request['endpoint'],
                $request['params'] ?? []
            );
        }

        return Promise::all($promises);
    }
}
