<?php
/**
 * MO Aramex Logger Class
 * 
 * Custom logging system for Aramex API calls and responses
 * Separate from WordPress debug logs with date-organized files
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_Logger {
    
    /**
     * Log directory path
     *
     * @var string
     */
    private $log_dir;
    
    /**
     * Log file prefix
     *
     * @var string
     */
    private $log_prefix = 'aramex-log-';
    
    /**
     * Maximum log file size in bytes (5MB)
     *
     * @var int
     */
    private $max_file_size = 5242880;
    
    /**
     * Maximum number of log files to keep
     *
     * @var int
     */
    private $max_log_files = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log_dir = WP_CONTENT_DIR . '/uploads/aramex-logs/';
        $this->init_log_directory();
        $this->cleanup_old_logs();
    }
    
    /**
     * Initialize log directory
     */
    private function init_log_directory() {
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
            
            // Create .htaccess to protect log files
            $htaccess_content = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($this->log_dir . '.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($this->log_dir . 'index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Get current log file path
     *
     * @return string
     */
    private function get_log_file_path() {
        $date = date('j-n-Y'); // Format: day-month-year (e.g., 22-9-2025)
        return $this->log_dir . $this->log_prefix . $date . '.log';
    }
    
    /**
     * Log API call
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Request data
     * @param string $method HTTP method
     * @param array $headers Request headers
     */
    public function log_api_call($endpoint, $request_data = array(), $method = 'POST', $headers = array()) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'type' => 'API_CALL',
            'endpoint' => $endpoint,
            'method' => $method,
            'headers' => $this->sanitize_headers($headers),
            'request_data' => $this->sanitize_data($request_data),
            'memory_usage' => $this->get_memory_usage(),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip()
        );
        
        $this->write_log($log_entry);
    }
    
    /**
     * Log API response
     *
     * @param string $endpoint API endpoint
     * @param mixed $response_data Response data
     * @param int $http_code HTTP response code
     * @param array $response_headers Response headers
     * @param float $execution_time Execution time in seconds
     */
    public function log_api_response($endpoint, $response_data, $http_code = 200, $response_headers = array(), $execution_time = 0) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'type' => 'API_RESPONSE',
            'endpoint' => $endpoint,
            'http_code' => $http_code,
            'response_headers' => $this->sanitize_headers($response_headers),
            'response_data' => $this->sanitize_data($response_data),
            'execution_time' => round($execution_time, 4),
            'memory_usage' => $this->get_memory_usage(),
            'success' => ($http_code >= 200 && $http_code < 300)
        );
        
        $this->write_log($log_entry);
    }
    
    /**
     * Log API error
     *
     * @param string $endpoint API endpoint
     * @param string $error_message Error message
     * @param int $error_code Error code
     * @param array $context Additional context
     */
    public function log_api_error($endpoint, $error_message, $error_code = 0, $context = array()) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'type' => 'API_ERROR',
            'endpoint' => $endpoint,
            'error_message' => $error_message,
            'error_code' => $error_code,
            'context' => $this->sanitize_data($context),
            'memory_usage' => $this->get_memory_usage(),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'backtrace' => $this->get_safe_backtrace()
        );
        
        $this->write_log($log_entry);
    }
    
    /**
     * Log general plugin activity
     *
     * @param string $action Action performed
     * @param string $message Log message
     * @param array $data Additional data
     * @param string $level Log level (INFO, WARNING, ERROR)
     */
    public function log_activity($action, $message, $data = array(), $level = 'INFO') {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'type' => 'ACTIVITY',
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'data' => $this->sanitize_data($data),
            'memory_usage' => $this->get_memory_usage(),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip()
        );
        
        $this->write_log($log_entry);
    }
    
    /**
     * Write log entry to file
     *
     * @param array $log_entry Log entry data
     */
    private function write_log($log_entry) {
        $log_file = $this->get_log_file_path();
        
        // Check file size and rotate if necessary
        if (file_exists($log_file) && filesize($log_file) > $this->max_file_size) {
            $this->rotate_log_file($log_file);
        }
        
        // Format log entry
        $formatted_entry = $this->format_log_entry($log_entry);
        
        // Write to file
        file_put_contents($log_file, $formatted_entry . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format log entry for writing
     *
     * @param array $log_entry Log entry data
     * @return string Formatted log entry
     */
    private function format_log_entry($log_entry) {
        $formatted = "[" . $log_entry['timestamp'] . "] ";
        $formatted .= "[" . $log_entry['type'] . "] ";
        
        if (isset($log_entry['level'])) {
            $formatted .= "[" . $log_entry['level'] . "] ";
        }
        
        if (isset($log_entry['endpoint'])) {
            $formatted .= "Endpoint: " . $log_entry['endpoint'] . " ";
        }
        
        if (isset($log_entry['action'])) {
            $formatted .= "Action: " . $log_entry['action'] . " ";
        }
        
        if (isset($log_entry['message'])) {
            $formatted .= "Message: " . $log_entry['message'] . " ";
        }
        
        if (isset($log_entry['http_code'])) {
            $formatted .= "HTTP Code: " . $log_entry['http_code'] . " ";
        }
        
        if (isset($log_entry['error_message'])) {
            $formatted .= "Error: " . $log_entry['error_message'] . " ";
        }
        
        if (isset($log_entry['execution_time'])) {
            $formatted .= "Execution Time: " . $log_entry['execution_time'] . "s ";
        }
        
        if (isset($log_entry['memory_usage'])) {
            $formatted .= "Memory: " . $log_entry['memory_usage'] . " ";
        }
        
        if (isset($log_entry['user_id'])) {
            $formatted .= "User ID: " . $log_entry['user_id'] . " ";
        }
        
        if (isset($log_entry['ip_address'])) {
            $formatted .= "IP: " . $log_entry['ip_address'] . " ";
        }
        
        // Add data section
        $data_fields = array('request_data', 'response_data', 'headers', 'response_headers', 'context', 'data');
        foreach ($data_fields as $field) {
            if (isset($log_entry[$field]) && !empty($log_entry[$field])) {
                $formatted .= "\n" . strtoupper($field) . ": " . $this->format_data($log_entry[$field]);
            }
        }
        
        if (isset($log_entry['backtrace'])) {
            $formatted .= "\nBACKTRACE: " . $log_entry['backtrace'];
        }
        
        return $formatted;
    }
    
    /**
     * Format data for logging
     *
     * @param mixed $data Data to format
     * @return string Formatted data
     */
    private function format_data($data) {
        if (is_array($data) || is_object($data)) {
            return print_r($data, true);
        }
        return (string) $data;
    }
    
    /**
     * Sanitize sensitive data
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitize_data($data) {
        if (is_array($data)) {
            $sanitized = array();
            foreach ($data as $key => $value) {
                $key_lower = strtolower($key);
                if (in_array($key_lower, array('password', 'pass', 'pwd', 'secret', 'key', 'token', 'auth'))) {
                    $sanitized[$key] = '[REDACTED]';
                } else {
                    $sanitized[$key] = $this->sanitize_data($value);
                }
            }
            return $sanitized;
        }
        return $data;
    }
    
    /**
     * Sanitize headers
     *
     * @param array $headers Headers to sanitize
     * @return array Sanitized headers
     */
    private function sanitize_headers($headers) {
        $sanitized = array();
        foreach ($headers as $key => $value) {
            $key_lower = strtolower($key);
            if (in_array($key_lower, array('authorization', 'x-api-key', 'x-auth-token'))) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    /**
     * Get memory usage
     *
     * @return string Memory usage
     */
    private function get_memory_usage() {
        return size_format(memory_get_usage(true));
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Get safe backtrace
     *
     * @return string Safe backtrace
     */
    private function get_safe_backtrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $safe_trace = array();
        
        foreach ($backtrace as $trace) {
            $safe_trace[] = isset($trace['file']) ? basename($trace['file']) . ':' . $trace['line'] : 'unknown';
        }
        
        return implode(' -> ', $safe_trace);
    }
    
    /**
     * Rotate log file
     *
     * @param string $log_file Current log file path
     */
    private function rotate_log_file($log_file) {
        $timestamp = date('H-i-s');
        $rotated_file = str_replace('.log', '-' . $timestamp . '.log', $log_file);
        rename($log_file, $rotated_file);
    }
    
    /**
     * Cleanup old log files
     */
    private function cleanup_old_logs() {
        if (!is_dir($this->log_dir)) {
            return;
        }
        
        $files = glob($this->log_dir . $this->log_prefix . '*.log');
        if (count($files) > $this->max_log_files) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($files, 0, count($files) - $this->max_log_files);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log files list
     *
     * @return array List of log files
     */
    public function get_log_files() {
        if (!is_dir($this->log_dir)) {
            return array();
        }
        
        $files = glob($this->log_dir . $this->log_prefix . '*.log');
        $log_files = array();
        
        foreach ($files as $file) {
            $log_files[] = array(
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            );
        }
        
        // Sort by modification time (newest first)
        usort($log_files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $log_files;
    }
    
    /**
     * Read log file content
     *
     * @param string $filename Log filename
     * @param int $lines Number of lines to read (0 = all)
     * @return string Log content
     */
    public function read_log_file($filename, $lines = 0) {
        $file_path = $this->log_dir . $filename;
        
        if (!file_exists($file_path)) {
            return 'Log file not found.';
        }
        
        if ($lines > 0) {
            $content = file_get_contents($file_path);
            $content_lines = explode("\n", $content);
            $content_lines = array_slice($content_lines, -$lines);
            return implode("\n", $content_lines);
        }
        
        return file_get_contents($file_path);
    }
    
    /**
     * Clear all log files
     */
    public function clear_all_logs() {
        $files = glob($this->log_dir . $this->log_prefix . '*.log');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Get log statistics
     *
     * @return array Log statistics
     */
    public function get_log_statistics() {
        $files = $this->get_log_files();
        $total_size = 0;
        $total_files = count($files);
        
        foreach ($files as $file) {
            $total_size += $file['size'];
        }
        
        return array(
            'total_files' => $total_files,
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'oldest_file' => !empty($files) ? end($files)['date'] : null,
            'newest_file' => !empty($files) ? $files[0]['date'] : null
        );
    }
}

// Initialize the logger
global $mo_aramex_logger;
$mo_aramex_logger = new MO_Aramex_Logger();
