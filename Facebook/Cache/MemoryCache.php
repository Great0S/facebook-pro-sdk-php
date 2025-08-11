<?php

namespace Facebook\Cache;

class MemoryCache implements CacheInterface
{
    private $cache = [];
    private $expires = [];

    public function get($key)
    {
        if (!$this->has($key)) {
            return null;
        }

        return $this->cache[$key];
    }

    public function set($key, $value, $ttl = 3600)
    {
        $this->cache[$key] = $value;
        $this->expires[$key] = time() + $ttl;
    }

    public function delete($key)
    {
        unset($this->cache[$key], $this->expires[$key]);
    }

    public function clear()
    {
        $this->cache = [];
        $this->expires = [];
    }

    public function has($key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        // Check if expired
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function getStats()
    {
        return [
            'hits' => count($this->cache),
            'memory_usage' => memory_get_usage(),
            'active_keys' => array_keys($this->cache)
        ];
    }
}
