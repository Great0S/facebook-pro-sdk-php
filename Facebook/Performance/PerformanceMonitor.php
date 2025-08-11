<?php

namespace Facebook\Performance;

use Facebook\Logging\FacebookLogger;

class PerformanceMonitor
{
    private $metrics = [];
    private $logger;
    private $startTimes = [];
    private $memoryUsage = [];

    public function __construct(FacebookLogger $logger = null)
    {
        $this->logger = $logger ?: new FacebookLogger();
    }

    /**
     * Start timing an operation
     */
    public function startTimer($operation)
    {
        $this->startTimes[$operation] = microtime(true);
        $this->memoryUsage[$operation] = memory_get_usage(true);

        $this->logger->debug('Timer started', ['operation' => $operation]);
    }

    /**
     * Stop timing an operation
     */
    public function stopTimer($operation)
    {
        if (!isset($this->startTimes[$operation])) {
            $this->logger->warning('Timer not found', ['operation' => $operation]);
            return null;
        }

        $duration = microtime(true) - $this->startTimes[$operation];
        $memoryDelta = memory_get_usage(true) - $this->memoryUsage[$operation];

        $this->recordMetric($operation, [
            'duration' => $duration,
            'memory_delta' => $memoryDelta,
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => time()
        ]);

        unset($this->startTimes[$operation], $this->memoryUsage[$operation]);

        $this->logger->debug('Timer stopped', [
            'operation' => $operation,
            'duration' => round($duration * 1000, 2) . 'ms',
            'memory_delta' => $this->formatBytes($memoryDelta)
        ]);

        return $duration;
    }

    /**
     * Record a custom metric
     */
    public function recordMetric($name, $value)
    {
        if (!isset($this->metrics[$name])) {
            $this->metrics[$name] = [];
        }

        $this->metrics[$name][] = $value;

        // Keep only last 100 entries to prevent memory bloat
        if (count($this->metrics[$name]) > 100) {
            array_shift($this->metrics[$name]);
        }
    }

    /**
     * Get metrics for an operation
     */
    public function getMetrics($operation = null)
    {
        if ($operation) {
            return $this->metrics[$operation] ?? [];
        }

        return $this->metrics;
    }

    /**
     * Get performance statistics
     */
    public function getStats($operation)
    {
        $metrics = $this->getMetrics($operation);

        if (empty($metrics)) {
            return null;
        }

        $durations = array_column($metrics, 'duration');
        $memoryDeltas = array_column($metrics, 'memory_delta');

        return [
            'count' => count($metrics),
            'duration' => [
                'avg' => array_sum($durations) / count($durations),
                'min' => min($durations),
                'max' => max($durations),
                'total' => array_sum($durations)
            ],
            'memory' => [
                'avg_delta' => array_sum($memoryDeltas) / count($memoryDeltas),
                'min_delta' => min($memoryDeltas),
                'max_delta' => max($memoryDeltas),
                'total_delta' => array_sum($memoryDeltas)
            ]
        ];
    }

    /**
     * Generate performance report
     */
    public function generateReport()
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operations' => []
        ];

        foreach (array_keys($this->metrics) as $operation) {
            $stats = $this->getStats($operation);
            if ($stats) {
                $report['operations'][$operation] = $stats;
            }
        }

        return $report;
    }

    /**
     * Log performance report
     */
    public function logReport()
    {
        $report = $this->generateReport();

        $this->logger->info('Performance Report', $report);

        // Log warnings for slow operations
        foreach ($report['operations'] as $operation => $stats) {
            if ($stats['duration']['avg'] > 5.0) { // More than 5 seconds average
                $this->logger->warning('Slow operation detected', [
                    'operation' => $operation,
                    'avg_duration' => round($stats['duration']['avg'], 3) . 's'
                ]);
            }
        }
    }

    /**
     * Clear all metrics
     */
    public function clear()
    {
        $this->metrics = [];
        $this->startTimes = [];
        $this->memoryUsage = [];

        $this->logger->debug('Performance metrics cleared');
    }

    /**
     * Measure execution time of a callable
     */
    public function measure($operation, callable $callback)
    {
        $this->startTimer($operation);

        try {
            $result = call_user_func($callback);
            $this->stopTimer($operation);
            return $result;
        } catch (\Exception $e) {
            $this->stopTimer($operation);
            $this->logger->error('Measured operation failed', [
                'operation' => $operation,
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
