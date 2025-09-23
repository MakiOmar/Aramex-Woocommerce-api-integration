<?php
/**
 * MO Aramex Log Viewer Class
 * 
 * Admin interface for viewing and managing Aramex logs
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_Log_Viewer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_log_viewer_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_mo_aramex_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_mo_aramex_download_log', array($this, 'ajax_download_log'));
    }
    
    /**
     * Add log viewer menu
     */
    public function add_log_viewer_menu() {
        add_submenu_page(
            'woocommerce',
            __('MO Aramex Logs', 'mo-aramex-shipping'),
            __('MO Aramex Logs', 'mo-aramex-shipping'),
            'manage_options',
            'mo-aramex-logs',
            array($this, 'log_viewer_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_mo-aramex-logs') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-admin');
    }
    
    /**
     * Log viewer page
     */
    public function log_viewer_page() {
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-log-helper.php';
        
        $log_helper = new MO_Aramex_Log_Helper();
        $log_files = $log_helper->get_log_files();
        $statistics = $log_helper->get_log_statistics();
        
        // Handle log file viewing
        $selected_file = isset($_GET['log_file']) ? sanitize_text_field($_GET['log_file']) : '';
        $lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;
        $log_content = '';
        
        if ($selected_file && in_array($selected_file, wp_list_pluck($log_files, 'filename'))) {
            $log_content = $log_helper->read_log_file($selected_file, $lines);
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MO Aramex Logs', 'mo-aramex-shipping'); ?></h1>
            
            <!-- Statistics -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                <h2><?php echo esc_html__('Log Statistics', 'mo-aramex-shipping'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Total Log Files', 'mo-aramex-shipping'); ?></th>
                        <td><?php echo esc_html($statistics['total_files']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Total Size', 'mo-aramex-shipping'); ?></th>
                        <td><?php echo esc_html($statistics['total_size_formatted']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Oldest File', 'mo-aramex-shipping'); ?></th>
                        <td><?php echo esc_html($statistics['oldest_file'] ?: __('None', 'mo-aramex-shipping')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Newest File', 'mo-aramex-shipping'); ?></th>
                        <td><?php echo esc_html($statistics['newest_file'] ?: __('None', 'mo-aramex-shipping')); ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Actions -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                <h2><?php echo esc_html__('Actions', 'mo-aramex-shipping'); ?></h2>
                <p>
                    <button type="button" class="button button-secondary" id="clear-logs-btn">
                        <?php echo esc_html__('Clear All Logs', 'mo-aramex-shipping'); ?>
                    </button>
                    <span class="description">
                        <?php echo esc_html__('This will permanently delete all log files.', 'mo-aramex-shipping'); ?>
                    </span>
                </p>
            </div>
            
            <!-- Log Files List -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                <h2><?php echo esc_html__('Log Files', 'mo-aramex-shipping'); ?></h2>
                
                <?php if (empty($log_files)): ?>
                    <p><?php echo esc_html__('No log files found.', 'mo-aramex-shipping'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Filename', 'mo-aramex-shipping'); ?></th>
                                <th><?php echo esc_html__('Size', 'mo-aramex-shipping'); ?></th>
                                <th><?php echo esc_html__('Modified', 'mo-aramex-shipping'); ?></th>
                                <th><?php echo esc_html__('Actions', 'mo-aramex-shipping'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($log_files as $file): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(array('log_file' => $file['filename']), admin_url('admin.php?page=mo-aramex-logs'))); ?>">
                                            <?php echo esc_html($file['filename']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html(size_format($file['size'])); ?></td>
                                    <td><?php echo esc_html($file['date']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(array('log_file' => $file['filename']), admin_url('admin.php?page=mo-aramex-logs'))); ?>" class="button button-small">
                                            <?php echo esc_html__('View', 'mo-aramex-shipping'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'download_log', 'log_file' => $file['filename']), admin_url('admin.php?page=mo-aramex-logs')), 'download_log_' . $file['filename'])); ?>" class="button button-small">
                                            <?php echo esc_html__('Download', 'mo-aramex-shipping'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Log Content Viewer -->
            <?php if ($selected_file && $log_content): ?>
                <div class="card" style="max-width: 100%;">
                    <h2>
                        <?php echo esc_html__('Log Content', 'mo-aramex-shipping'); ?>: 
                        <?php echo esc_html($selected_file); ?>
                    </h2>
                    
                    <!-- Lines selector -->
                    <p>
                        <label for="lines-select"><?php echo esc_html__('Show last', 'mo-aramex-shipping'); ?>:</label>
                        <select id="lines-select" onchange="changeLines()">
                            <option value="50" <?php selected($lines, 50); ?>>50 <?php echo esc_html__('lines', 'mo-aramex-shipping'); ?></option>
                            <option value="100" <?php selected($lines, 100); ?>>100 <?php echo esc_html__('lines', 'mo-aramex-shipping'); ?></option>
                            <option value="200" <?php selected($lines, 200); ?>>200 <?php echo esc_html__('lines', 'mo-aramex-shipping'); ?></option>
                            <option value="500" <?php selected($lines, 500); ?>>500 <?php echo esc_html__('lines', 'mo-aramex-shipping'); ?></option>
                            <option value="0" <?php selected($lines, 0); ?>><?php echo esc_html__('All lines', 'mo-aramex-shipping'); ?></option>
                        </select>
                    </p>
                    
                    <!-- Log content -->
                    <div style="background: #f1f1f1; border: 1px solid #ddd; padding: 10px; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
                        <?php echo esc_html($log_content); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        function changeLines() {
            var lines = document.getElementById('lines-select').value;
            var url = new URL(window.location);
            url.searchParams.set('lines', lines);
            window.location.href = url.toString();
        }
        
        document.getElementById('clear-logs-btn').addEventListener('click', function() {
            if (confirm('<?php echo esc_js(__('Are you sure you want to clear all logs? This action cannot be undone.', 'mo-aramex-shipping')); ?>')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('<?php echo esc_js(__('All logs have been cleared.', 'mo-aramex-shipping')); ?>');
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(__('Error clearing logs: ', 'mo-aramex-shipping')); ?>' + response.data);
                        }
                    }
                };
                xhr.send('action=mo_aramex_clear_logs&nonce=<?php echo esc_js(wp_create_nonce('mo_aramex_clear_logs')); ?>');
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('mo_aramex_clear_logs', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mo-aramex-shipping'));
        }
        
        try {
            require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-log-helper.php';
            MO_Aramex_Log_Helper::clear_all_logs();
            
            wp_send_json_success(__('All logs have been cleared successfully.', 'mo-aramex-shipping'));
        } catch (Exception $e) {
            wp_send_json_error(__('Error clearing logs: ', 'mo-aramex-shipping') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for downloading logs
     */
    public function ajax_download_log() {
        if (!isset($_GET['log_file']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'mo-aramex-shipping'));
        }
        
        $log_file = sanitize_text_field($_GET['log_file']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);
        
        if (!wp_verify_nonce($nonce, 'download_log_' . $log_file)) {
            wp_die(__('Security check failed.', 'mo-aramex-shipping'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mo-aramex-shipping'));
        }
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-log-helper.php';
        
        $log_helper = new MO_Aramex_Log_Helper();
        $log_files = $log_helper->get_log_files();
        
        // Verify the file exists in our log files list
        $file_exists = false;
        foreach ($log_files as $file) {
            if ($file['filename'] === $log_file) {
                $file_exists = true;
                $file_path = $file['path'];
                break;
            }
        }
        
        if (!$file_exists || !file_exists($file_path)) {
            wp_die(__('Log file not found.', 'mo-aramex-shipping'));
        }
        
        // Set headers for download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $log_file . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file content
        readfile($file_path);
        exit;
    }
}

// Initialize the log viewer
new MO_Aramex_Log_Viewer();
