<?php

namespace Facebook\Webhooks;

use Facebook\Logging\FacebookLogger;

class WebhookReceiver
{
    private $appSecret;
    private $verifyToken;
    private $logger;
    private $eventHandlers;

    public function __construct($appSecret, $verifyToken, FacebookLogger $logger = null)
    {
        $this->appSecret = $appSecret;
        $this->verifyToken = $verifyToken;
        $this->logger = $logger ?: new FacebookLogger();
        $this->eventHandlers = [];
    }

    /**
     * Handle webhook verification challenge
     */
    public function handleVerification()
    {
        $mode = $_GET['hub_mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            $this->logger->info('Webhook verification successful');
            return $challenge;
        }

        $this->logger->error('Webhook verification failed', [
            'mode' => $mode,
            'token' => $token
        ]);

        http_response_code(403);
        return false;
    }

    /**
     * Process incoming webhook events
     */
    public function processEvents($body = null)
    {
        if ($body === null) {
            $body = file_get_contents('php://input');
        }

        if (!$this->verifySignature($body)) {
            $this->logger->error('Invalid webhook signature');
            http_response_code(403);
            return false;
        }

        $data = json_decode($body, true);

        if (!$data) {
            $this->logger->error('Invalid JSON payload');
            http_response_code(400);
            return false;
        }

        $this->logger->info('Processing webhook events', ['event_count' => count($data['entry'] ?? [])]);

        foreach ($data['entry'] as $entry) {
            $this->processEntry($entry);
        }

        return true;
    }

    /**
     * Register event handler
     */
    public function onEvent($eventType, callable $handler)
    {
        if (!isset($this->eventHandlers[$eventType])) {
            $this->eventHandlers[$eventType] = [];
        }

        $this->eventHandlers[$eventType][] = $handler;
    }

    private function verifySignature($body)
    {
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

        if (empty($signature)) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    private function processEntry($entry)
    {
        foreach ($entry['changes'] ?? [] as $change) {
            $this->processChange($change);
        }

        foreach ($entry['messaging'] ?? [] as $message) {
            $this->processMessage($message);
        }
    }

    private function processChange($change)
    {
        $field = $change['field'];
        $value = $change['value'];

        $this->logger->debug('Processing change event', [
            'field' => $field,
            'value' => $value
        ]);

        $this->triggerEvent($field, $value);
    }

    private function processMessage($message)
    {
        $this->logger->debug('Processing message event', $message);
        $this->triggerEvent('message', $message);
    }

    private function triggerEvent($eventType, $data)
    {
        if (!isset($this->eventHandlers[$eventType])) {
            return;
        }

        foreach ($this->eventHandlers[$eventType] as $handler) {
            try {
                call_user_func($handler, $data);
            } catch (\Exception $e) {
                $this->logger->error('Event handler failed', [
                    'event_type' => $eventType,
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ]);
            }
        }
    }
}
