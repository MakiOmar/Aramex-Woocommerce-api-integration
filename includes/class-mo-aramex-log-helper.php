<?php
/**
 * MO Aramex Log Helper Class
 * 
 * Convenient helper functions for logging Aramex API calls and responses
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_Log_Helper {
    
    /**
     * Logger instance
     *
     * @var MO_Aramex_Logger
     */
    private static $logger;
    
    /**
     * Get logger instance
     *
     * @return MO_Aramex_Logger
     */
    private static function get_logger() {
        if (!self::$logger) {
            global $mo_aramex_logger;
            if (!$mo_aramex_logger) {
                require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-logger.php';
                $mo_aramex_logger = new MO_Aramex_Logger();
            }
            self::$logger = $mo_aramex_logger;
        }
        return self::$logger;
    }
    
    /**
     * Log API call
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Request data
     * @param string $method HTTP method
     * @param array $headers Request headers
     */
    public static function log_api_call($endpoint, $request_data = array(), $method = 'POST', $headers = array()) {
        self::get_logger()->log_api_call($endpoint, $request_data, $method, $headers);
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
    public static function log_api_response($endpoint, $response_data, $http_code = 200, $response_headers = array(), $execution_time = 0) {
        self::get_logger()->log_api_response($endpoint, $response_data, $http_code, $response_headers, $execution_time);
    }
    
    /**
     * Log API error
     *
     * @param string $endpoint API endpoint
     * @param string $error_message Error message
     * @param int $error_code Error code
     * @param array $context Additional context
     */
    public static function log_api_error($endpoint, $error_message, $error_code = 0, $context = array()) {
        self::get_logger()->log_api_error($endpoint, $error_message, $error_code, $context);
    }
    
    /**
     * Log plugin activity
     *
     * @param string $action Action performed
     * @param string $message Log message
     * @param array $data Additional data
     * @param string $level Log level (INFO, WARNING, ERROR)
     */
    public static function log_activity($action, $message, $data = array(), $level = 'INFO') {
        self::get_logger()->log_activity($action, $message, $data, $level);
    }
    
    /**
     * Log shipment creation
     *
     * @param int $order_id Order ID
     * @param array $shipment_data Shipment data
     * @param string $status Status (success, error)
     * @param string $message Additional message
     */
    public static function log_shipment_creation($order_id, $shipment_data, $status = 'success', $message = '') {
        $action = 'SHIPMENT_CREATION';
        $log_message = "Shipment creation for Order #{$order_id} - Status: {$status}";
        if ($message) {
            $log_message .= " - {$message}";
        }
        
        $data = array(
            'order_id' => $order_id,
            'shipment_data' => $shipment_data,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log label printing
     *
     * @param int $order_id Order ID
     * @param string $label_type Label type
     * @param string $status Status (success, error)
     * @param string $message Additional message
     */
    public static function log_label_printing($order_id, $label_type, $status = 'success', $message = '') {
        $action = 'LABEL_PRINTING';
        $log_message = "Label printing for Order #{$order_id} - Type: {$label_type} - Status: {$status}";
        if ($message) {
            $log_message .= " - {$message}";
        }
        
        $data = array(
            'order_id' => $order_id,
            'label_type' => $label_type,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log bulk operations
     *
     * @param string $operation Operation type
     * @param array $order_ids Order IDs
     * @param string $status Status (success, error)
     * @param string $message Additional message
     */
    public static function log_bulk_operation($operation, $order_ids, $status = 'success', $message = '') {
        $action = 'BULK_OPERATION';
        $count = count($order_ids);
        $log_message = "Bulk {$operation} for {$count} orders - Status: {$status}";
        if ($message) {
            $log_message .= " - {$message}";
        }
        
        $data = array(
            'operation' => $operation,
            'order_ids' => $order_ids,
            'count' => $count,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log rate calculation
     *
     * @param array $rate_data Rate calculation data
     * @param mixed $result Rate calculation result
     * @param string $status Status (success, error)
     */
    public static function log_rate_calculation($rate_data, $result, $status = 'success') {
        $action = 'RATE_CALCULATION';
        $log_message = "Rate calculation - Status: {$status}";
        
        $data = array(
            'rate_data' => $rate_data,
            'result' => $result,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log tracking request
     *
     * @param string $tracking_number Tracking number
     * @param mixed $result Tracking result
     * @param string $status Status (success, error)
     */
    public static function log_tracking_request($tracking_number, $result, $status = 'success') {
        $action = 'TRACKING_REQUEST';
        $log_message = "Tracking request for #{$tracking_number} - Status: {$status}";
        
        $data = array(
            'tracking_number' => $tracking_number,
            'result' => $result,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log pickup scheduling
     *
     * @param array $pickup_data Pickup data
     * @param mixed $result Pickup result
     * @param string $status Status (success, error)
     */
    public static function log_pickup_scheduling($pickup_data, $result, $status = 'success') {
        $action = 'PICKUP_SCHEDULING';
        $log_message = "Pickup scheduling - Status: {$status}";
        
        $data = array(
            'pickup_data' => $pickup_data,
            'result' => $result,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity($action, $log_message, $data, $level);
    }
    
    /**
     * Log configuration changes
     *
     * @param string $setting Setting name
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     * @param int $user_id User ID who made the change
     */
    public static function log_configuration_change($setting, $old_value, $new_value, $user_id = null) {
        $action = 'CONFIGURATION_CHANGE';
        $log_message = "Configuration change - Setting: {$setting}";
        
        $data = array(
            'setting' => $setting,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'user_id' => $user_id ?: get_current_user_id()
        );
        
        self::log_activity($action, $log_message, $data, 'INFO');
    }
    
    /**
     * Log plugin activation/deactivation
     *
     * @param string $action Action (activation, deactivation)
     * @param string $version Plugin version
     */
    public static function log_plugin_lifecycle($action, $version) {
        $log_message = "Plugin {$action} - Version: {$version}";
        
        $data = array(
            'action' => $action,
            'version' => $version,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
        
        self::log_activity('PLUGIN_LIFECYCLE', $log_message, $data, 'INFO');
    }
    
    /**
     * Log AJAX requests
     *
     * @param string $action AJAX action
     * @param array $data Request data
     * @param mixed $response Response data
     * @param string $status Status (success, error)
     */
    public static function log_ajax_request($action, $data, $response, $status = 'success') {
        $log_message = "AJAX request - Action: {$action} - Status: {$status}";
        
        $log_data = array(
            'ajax_action' => $action,
            'request_data' => $data,
            'response' => $response,
            'status' => $status
        );
        
        $level = ($status === 'error') ? 'ERROR' : 'INFO';
        self::log_activity('AJAX_REQUEST', $log_message, $log_data, $level);
    }
    
    /**
     * Get log files list
     *
     * @return array List of log files
     */
    public static function get_log_files() {
        return self::get_logger()->get_log_files();
    }
    
    /**
     * Read log file content
     *
     * @param string $filename Log filename
     * @param int $lines Number of lines to read (0 = all)
     * @return string Log content
     */
    public static function read_log_file($filename, $lines = 0) {
        return self::get_logger()->read_log_file($filename, $lines);
    }
    
    /**
     * Clear all log files
     */
    public static function clear_all_logs() {
        self::get_logger()->clear_all_logs();
    }
    
    /**
     * Get log statistics
     *
     * @return array Log statistics
     */
    public static function get_log_statistics() {
        return self::get_logger()->get_log_statistics();
    }
}

// Convenient global functions for easy logging
if (!function_exists('mo_aramex_log_api_call')) {
    /**
     * Log API call
     */
    function mo_aramex_log_api_call($endpoint, $request_data = array(), $method = 'POST', $headers = array()) {
        MO_Aramex_Log_Helper::log_api_call($endpoint, $request_data, $method, $headers);
    }
}

if (!function_exists('mo_aramex_log_api_response')) {
    /**
     * Log API response
     */
    function mo_aramex_log_api_response($endpoint, $response_data, $http_code = 200, $response_headers = array(), $execution_time = 0) {
        MO_Aramex_Log_Helper::log_api_response($endpoint, $response_data, $http_code, $response_headers, $execution_time);
    }
}

if (!function_exists('mo_aramex_log_api_error')) {
    /**
     * Log API error
     */
    function mo_aramex_log_api_error($endpoint, $error_message, $error_code = 0, $context = array()) {
        MO_Aramex_Log_Helper::log_api_error($endpoint, $error_message, $error_code, $context);
    }
}

if (!function_exists('mo_aramex_log_activity')) {
    /**
     * Log plugin activity
     */
    function mo_aramex_log_activity($action, $message, $data = array(), $level = 'INFO') {
        MO_Aramex_Log_Helper::log_activity($action, $message, $data, $level);
    }
}
