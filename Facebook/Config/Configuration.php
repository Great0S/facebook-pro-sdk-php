<?php

namespace Facebook\Config;

use Facebook\Logging\FacebookLogger;

class Configuration
{
    private $config = [];
    private $defaults = [];
    private $validators = [];
    private $logger;

    public function __construct(array $config = [], FacebookLogger $logger = null)
    {
        $this->logger = $logger ?: new FacebookLogger();
        $this->setDefaults();
        $this->setValidators();
        $this->config = array_merge($this->defaults, $config);
        $this->validate();
    }

    /**
     * Get configuration value
     */
    public function get($key, $default = null)
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function set($key, $value)
    {
        $this->setNestedValue($this->config, $key, $value);
        $this->validate();

        $this->logger->debug('Configuration updated', [
            'key' => $key,
            'value' => is_string($value) ? $value : gettype($value)
        ]);
    }

    /**
     * Check if configuration key exists
     */
    public function has($key)
    {
        return $this->getNestedValue($this->config, $key) !== null;
    }

    /**
     * Load configuration from file
     */
    public function loadFromFile($file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Configuration file not found: {$file}");
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'json':
                $config = json_decode(file_get_contents($file), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException("Invalid JSON in configuration file: " . json_last_error_msg());
                }
                break;

            case 'php':
                $config = include $file;
                if (!is_array($config)) {
                    throw new \InvalidArgumentException("PHP configuration file must return an array");
                }
                break;

            default:
                throw new \InvalidArgumentException("Unsupported configuration file format: {$extension}");
        }

        $this->config = array_merge($this->config, $config);
        $this->validate();

        $this->logger->info('Configuration loaded from file', ['file' => $file]);
    }

    /**
     * Load configuration from environment variables
     */
    public function loadFromEnvironment($prefix = 'FB_')
    {
        $envConfig = [];

        foreach ($_ENV as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $configKey = strtolower(substr($key, strlen($prefix)));
                $configKey = str_replace('_', '.', $configKey);
                $this->setNestedValue($envConfig, $configKey, $value);
            }
        }

        $this->config = array_merge($this->config, $envConfig);
        $this->validate();

        $this->logger->debug('Configuration loaded from environment', [
            'prefix' => $prefix,
            'keys_loaded' => count($envConfig)
        ]);
    }

    /**
     * Get all configuration
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Validate configuration
     */
    private function validate()
    {
        foreach ($this->validators as $key => $validator) {
            $value = $this->get($key);

            if ($value !== null && !$validator($value)) {
                throw new \InvalidArgumentException("Invalid configuration value for key: {$key}");
            }
        }
    }

    private function setDefaults()
    {
        $this->defaults = [
            'app.id' => '',
            'app.secret' => '',
            'app.version' => 'v18.0',

            'http.timeout' => 30,
            'http.connect_timeout' => 10,
            'http.verify_ssl' => true,

            'cache.enabled' => true,
            'cache.default_ttl' => 3600,
            'cache.driver' => 'memory',

            'logging.enabled' => true,
            'logging.level' => 'info',
            'logging.file' => null,

            'rate_limit.enabled' => true,
            'rate_limit.max_retries' => 3,
            'rate_limit.base_delay' => 1000,

            'upload.chunk_size' => 1024 * 1024,
            'upload.max_file_size' => 100 * 1024 * 1024,
            'upload.parallel_chunks' => 3,

            'webhook.verify_token' => '',
            'webhook.secret' => ''
        ];
    }

    private function setValidators()
    {
        $this->validators = [
            'app.id' => function ($value) {
                return is_string($value) && !empty($value);
            },
            'app.secret' => function ($value) {
                return is_string($value) && !empty($value);
            },
            'http.timeout' => function ($value) {
                return is_numeric($value) && $value > 0;
            },
            'cache.default_ttl' => function ($value) {
                return is_numeric($value) && $value >= 0;
            },
            'upload.chunk_size' => function ($value) {
                return is_numeric($value) && $value > 0;
            }
        ];
    }

    private function getNestedValue($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    private function setNestedValue(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }
}
