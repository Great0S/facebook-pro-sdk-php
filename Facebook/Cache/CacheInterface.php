<?php

namespace Facebook\Cache;

interface CacheInterface
{
    public function get($key);
    public function set($key, $value, $ttl = 3600);
    public function delete($key);
    public function clear();
    public function has($key);
}

class FileCache implements CacheInterface
{
    private $cacheDir;

    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: sys_get_temp_dir() . '/facebook-cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get($key)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function set($key, $value, $ttl = 3600)
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        file_put_contents($file, serialize($data), LOCK_EX);
    }

    public function delete($key)
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function has($key)
    {
        return $this->get($key) !== null;
    }

    private function getFilePath($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
