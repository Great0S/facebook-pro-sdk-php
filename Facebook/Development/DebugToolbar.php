<?php

namespace Facebook\Development;

use Facebook\Logging\FacebookLogger;
use Facebook\Performance\PerformanceMonitor;

class DebugToolbar
{
    private $logger;
    private $monitor;
    private $requests = [];
    private $enabled = false;

    public function __construct(FacebookLogger $logger = null, PerformanceMonitor $monitor = null)
    {
        $this->logger = $logger ?: new FacebookLogger();
        $this->monitor = $monitor ?: new PerformanceMonitor($this->logger);
        $this->enabled = $this->isDebugMode();
    }

    /**
     * Enable debug toolbar
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disable debug toolbar
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Log API request
     */
    public function logRequest($method, $endpoint, $params, $response, $duration)
    {
        if (!$this->enabled) {
            return;
        }

        $this->requests[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'params' => $params,
            'response' => $response,
            'duration' => $duration,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true)
        ];

        $this->logger->debug('API Request logged', [
            'method' => $method,
            'endpoint' => $endpoint,
            'duration' => round($duration * 1000, 2) . 'ms'
        ]);
    }

    /**
     * Get debug panel HTML
     */
    public function getDebugPanel()
    {
        if (!$this->enabled) {
            return '';
        }

        $stats = $this->getStats();

        $html = '
        <div id="facebook-debug-toolbar" style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #333;
            color: #fff;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            z-index: 9999;
            border-top: 3px solid #4267B2;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Facebook SDK Debug</strong> | 
                    Requests: ' . $stats['request_count'] . ' | 
                    Total Time: ' . round($stats['total_duration'] * 1000, 2) . 'ms | 
                    Memory: ' . $this->formatBytes($stats['peak_memory']) . '
                </div>
                <div>
                    <button onclick="document.getElementById(\'facebook-debug-details\').style.display = document.getElementById(\'facebook-debug-details\').style.display === \'block\' ? \'none\' : \'block\'">
                        Toggle Details
                    </button>
                </div>
            </div>
            
            <div id="facebook-debug-details" style="display: none; margin-top: 10px; max-height: 300px; overflow-y: auto;">
                ' . $this->getRequestsHtml() . '
            </div>
        </div>';

        return $html;
    }

    /**
     * Get performance statistics
     */
    public function getStats()
    {
        $totalDuration = 0;
        $peakMemory = 0;

        foreach ($this->requests as $request) {
            $totalDuration += $request['duration'];
            $peakMemory = max($peakMemory, $request['memory']);
        }

        return [
            'request_count' => count($this->requests),
            'total_duration' => $totalDuration,
            'peak_memory' => $peakMemory,
            'avg_duration' => count($this->requests) > 0 ? $totalDuration / count($this->requests) : 0
        ];
    }

    /**
     * Generate requests HTML
     */
    private function getRequestsHtml()
    {
        if (empty($this->requests)) {
            return '<p>No API requests made.</p>';
        }

        $html = '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr style="background: #444;">
            <th style="padding: 5px; text-align: left;">Method</th>
            <th style="padding: 5px; text-align: left;">Endpoint</th>
            <th style="padding: 5px; text-align: left;">Duration</th>
            <th style="padding: 5px; text-align: left;">Status</th>
            <th style="padding: 5px; text-align: left;">Memory</th>
        </tr>';

        foreach ($this->requests as $request) {
            $statusCode = $this->getStatusCode($request['response']);
            $statusColor = $statusCode >= 400 ? '#ff6b6b' : '#51cf66';

            $html .= '<tr style="border-bottom: 1px solid #555;">
                <td style="padding: 5px;">' . htmlspecialchars($request['method']) . '</td>
                <td style="padding: 5px;">' . htmlspecialchars($request['endpoint']) . '</td>
                <td style="padding: 5px;">' . round($request['duration'] * 1000, 2) . 'ms</td>
                <td style="padding: 5px; color: ' . $statusColor . ';">' . $statusCode . '</td>
                <td style="padding: 5px;">' . $this->formatBytes($request['memory']) . '</td>
            </tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Generate API inspector
     */
    public function getApiInspector()
    {
        if (!$this->enabled) {
            return '';
        }

        $html = '
        <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ddd; margin: 20px 0;">
            <h3>Facebook API Inspector</h3>
            
            <div style="margin-bottom: 20px;">
                <h4>Quick Test</h4>
                <input type="text" id="test-endpoint" placeholder="/me" style="padding: 5px; width: 200px;">
                <button onclick="testEndpoint()" style="padding: 5px 10px;">Test GET</button>
            </div>
            
            <div id="test-results" style="background: #fff; padding: 10px; border: 1px solid #ccc; min-height: 100px;">
                Results will appear here...
            </div>
        </div>
        
        <script>
            function testEndpoint() {
                const endpoint = document.getElementById("test-endpoint").value;
                const results = document.getElementById("test-results");
                
                results.innerHTML = "Testing " + endpoint + "...";
                
                // This would make an actual API call in a real implementation
                setTimeout(() => {
                    results.innerHTML = "Mock response for " + endpoint + "\\n\\n" + 
                        JSON.stringify({data: "test"}, null, 2);
                }, 1000);
            }
        </script>';

        return $html;
    }

    private function isDebugMode()
    {
        return isset($_GET['debug']) ||
            (defined('WP_DEBUG') && constant('WP_DEBUG')) ||
            getenv('APP_DEBUG') === 'true';
    }

    private function getStatusCode($response)
    {
        if (is_object($response) && method_exists($response, 'getHttpStatusCode')) {
            return $response->getHttpStatusCode();
        }

        return 200; // Default
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

class ApiProfiler
{
    private $monitor;
    private $logger;
    private $profiles = [];

    public function __construct(PerformanceMonitor $monitor = null, FacebookLogger $logger = null)
    {
        $this->monitor = $monitor ?: new PerformanceMonitor();
        $this->logger = $logger ?: new FacebookLogger();
    }

    /**
     * Profile an API call
     */
    public function profile($operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $callback();

            $profile = [
                'operation' => $operation,
                'duration' => microtime(true) - $startTime,
                'memory_delta' => memory_get_usage(true) - $startMemory,
                'success' => true,
                'timestamp' => time()
            ];
        } catch (\Exception $e) {
            $profile = [
                'operation' => $operation,
                'duration' => microtime(true) - $startTime,
                'memory_delta' => memory_get_usage(true) - $startMemory,
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];

            throw $e;
        } finally {
            $this->profiles[] = $profile;
            $this->logProfile($profile);
        }

        return $result;
    }

    /**
     * Get profiling results
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Clear profiles
     */
    public function clear()
    {
        $this->profiles = [];
    }

    private function logProfile($profile)
    {
        $this->logger->info('API call profiled', [
            'operation' => $profile['operation'],
            'duration' => round($profile['duration'] * 1000, 2) . 'ms',
            'memory_delta' => round($profile['memory_delta'] / 1024, 2) . 'KB',
            'success' => $profile['success']
        ]);
    }
}
