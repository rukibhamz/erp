<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Query Profiler Library
 * 
 * Profiles database queries to identify slow queries and optimization opportunities
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Performance
 */
class Query_profiler {
    
    private $CI;
    private $queries = [];
    private $enabled = false;
    private $slow_query_threshold = 100; // ms
    
    public function __construct() {
        $this->CI =& get_instance();
        
        // Enable only in development
        $this->enabled = ENVIRONMENT === 'development';
    }
    
    /**
     * Start profiling
     */
    public function start() {
        if (!$this->enabled) {
            return;
        }
        
        // Enable query profiling in CodeIgniter
        $this->CI->db->save_queries = true;
    }
    
    /**
     * Stop profiling and analyze queries
     * 
     * @return array Query analysis
     */
    public function stop() {
        if (!$this->enabled) {
            return [];
        }
        
        $queries = $this->CI->db->queries;
        $times = $this->CI->db->query_times;
        
        $analysis = [];
        $total_time = 0;
        
        foreach ($queries as $index => $query) {
            $time_ms = ($times[$index] ?? 0) * 1000;
            $total_time += $time_ms;
            
            $analysis[] = [
                'query' => $query,
                'time_ms' => round($time_ms, 2),
                'is_slow' => $time_ms > $this->slow_query_threshold
            ];
        }
        
        return [
            'total_queries' => count($queries),
            'total_time_ms' => round($total_time, 2),
            'slow_queries' => count(array_filter($analysis, function($q) {
                return $q['is_slow'];
            })),
            'queries' => $analysis
        ];
    }
    
    /**
     * Profile a specific query
     * 
     * @param callable $callback Function that executes queries
     * @return array Query results and profiling data
     */
    public function profile($callback) {
        if (!$this->enabled) {
            return ['result' => $callback(), 'profile' => []];
        }
        
        $this->start();
        
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        
        $profile = $this->stop();
        $profile['total_execution_ms'] = round(($end - $start) * 1000, 2);
        
        return [
            'result' => $result,
            'profile' => $profile
        ];
    }
    
    /**
     * Log slow queries to database
     * 
     * @param array $analysis Query analysis from stop()
     */
    public function logSlowQueries($analysis) {
        if (!$this->enabled) {
            return;
        }
        
        $this->CI->load->library('error_logger');
        
        foreach ($analysis['queries'] as $query_data) {
            if ($query_data['is_slow']) {
                $this->CI->error_logger->warning('Slow query detected', [
                    'query' => $query_data['query'],
                    'time_ms' => $query_data['time_ms'],
                    'threshold_ms' => $this->slow_query_threshold
                ]);
            }
        }
    }
    
    /**
     * Get query statistics
     * 
     * @return array Statistics
     */
    public function getStats() {
        if (!$this->enabled) {
            return [];
        }
        
        $queries = $this->CI->db->queries;
        $times = $this->CI->db->query_times;
        
        $total_time = array_sum($times);
        $avg_time = count($times) > 0 ? $total_time / count($times) : 0;
        
        return [
            'total_queries' => count($queries),
            'total_time_ms' => round($total_time * 1000, 2),
            'avg_time_ms' => round($avg_time * 1000, 2),
            'slowest_query_ms' => round(max($times) * 1000, 2)
        ];
    }
    
    /**
     * Set slow query threshold
     * 
     * @param int $ms Threshold in milliseconds
     */
    public function setSlowQueryThreshold($ms) {
        $this->slow_query_threshold = $ms;
    }
    
    /**
     * Enable/disable profiler
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }
}
