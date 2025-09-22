<?php
/**
 * MO Aramex Update Checker Debug Page
 * 
 * @package MO_Aramex_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_Update_Debug {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
    }
    
    /**
     * Add debug menu to admin
     */
    public function add_debug_menu() {
        add_submenu_page(
            'tools.php',
            'MO Aramex Update Debug',
            'MO Aramex Update Debug',
            'manage_options',
            'mo-aramex-update-debug',
            array($this, 'debug_page')
        );
    }
    
    /**
     * Debug page content
     */
    public function debug_page() {
        ?>
        <div class="wrap">
            <h1>MO Aramex Update Checker Debug</h1>
            
            <div class="notice notice-info">
                <p><strong>Debug Information for MO Aramex Shipping Integration Update Checker</strong></p>
            </div>
            
            <h2>Plugin Information</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Plugin Version:</strong></td>
                    <td><?php echo MO_ARAMEX_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin File:</strong></td>
                    <td><?php echo MO_ARAMEX_PLUGIN_FILE; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin Directory:</strong></td>
                    <td><?php echo MO_ARAMEX_PLUGIN_DIR; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin URL:</strong></td>
                    <td><?php echo MO_ARAMEX_PLUGIN_URL; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin Basename:</strong></td>
                    <td><?php echo MO_ARAMEX_PLUGIN_BASENAME; ?></td>
                </tr>
                <tr>
                    <td><strong>Update Checker File:</strong></td>
                    <td><?php echo file_exists(MO_ARAMEX_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php') ? 'Exists' : 'Missing'; ?></td>
                </tr>
                <tr>
                    <td><strong>PucFactory Class:</strong></td>
                    <td><?php echo class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory') ? 'Available' : 'Not Available'; ?></td>
                </tr>
            </table>
            
            <h2>Update Checker Configuration</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Repository URL:</strong></td>
                    <td>https://github.com/MakiOmar/Aramex-Woocommerce-api-integration.git</td>
                </tr>
                <tr>
                    <td><strong>Branch:</strong></td>
                    <td>master</td>
                </tr>
                <tr>
                    <td><strong>Plugin Slug:</strong></td>
                    <td>mo-aramex-shipping-integration</td>
                </tr>
            </table>
            
            <h2>GitHub API Test</h2>
            <?php $this->test_github_api(); ?>
            
            <h2>Update Checker Status</h2>
            <?php $this->check_update_status(); ?>
            
            <h2>WordPress Environment</h2>
            <table class="widefat">
                <tr>
                    <td><strong>WordPress Version:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>cURL Available:</strong></td>
                    <td><?php echo function_exists('curl_init') ? 'Yes' : 'No'; ?></td>
                </tr>
                <tr>
                    <td><strong>OpenSSL Available:</strong></td>
                    <td><?php echo extension_loaded('openssl') ? 'Yes' : 'No'; ?></td>
                </tr>
                <tr>
                    <td><strong>SSL Verify:</strong></td>
                    <td><?php echo $this->test_ssl_verify(); ?></td>
                </tr>
            </table>
            
            <h2>Network Test</h2>
            <?php $this->test_network_connectivity(); ?>
            
            <h2>Actions</h2>
            <p>
                <button type="button" class="button button-primary" onclick="location.reload();">
                    Refresh Debug Information
                </button>
                <button type="button" class="button" onclick="window.open('https://github.com/MakiOmar/Aramex-Woocommerce-api-integration', '_blank');">
                    Open GitHub Repository
                </button>
            </p>
        </div>
        
        <style>
        .widefat td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .widefat tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        </style>
        <?php
    }
    
    /**
     * Test GitHub API connectivity
     */
    private function test_github_api() {
        $api_url = 'https://api.github.com/repos/MakiOmar/Aramex-Woocommerce-api-integration';
        
        echo '<table class="widefat">';
        echo '<tr><td><strong>API URL:</strong></td><td>' . $api_url . '</td></tr>';
        
        // Test basic repository access
        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'MO-Aramex-Plugin/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            echo '<tr><td><strong>Repository Access:</strong></td><td class="error">Error: ' . $response->get_error_message() . '</td></tr>';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code === 200) {
                $data = json_decode($body, true);
                echo '<tr><td><strong>Repository Access:</strong></td><td class="success">Success</td></tr>';
                echo '<tr><td><strong>Repository Name:</strong></td><td>' . (isset($data['name']) ? $data['name'] : 'N/A') . '</td></tr>';
                echo '<tr><td><strong>Repository Full Name:</strong></td><td>' . (isset($data['full_name']) ? $data['full_name'] : 'N/A') . '</td></tr>';
                echo '<tr><td><strong>Default Branch:</strong></td><td>' . (isset($data['default_branch']) ? $data['default_branch'] : 'N/A') . '</td></tr>';
                echo '<tr><td><strong>Private:</strong></td><td>' . (isset($data['private']) ? ($data['private'] ? 'Yes' : 'No') : 'N/A') . '</td></tr>';
            } else {
                echo '<tr><td><strong>Repository Access:</strong></td><td class="error">HTTP ' . $code . '</td></tr>';
                echo '<tr><td><strong>Response:</strong></td><td>' . esc_html(substr($body, 0, 200)) . '...</td></tr>';
            }
        }
        
        // Test branch access
        $branch_url = $api_url . '/branches/master';
        $response = wp_remote_get($branch_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'MO-Aramex-Plugin/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            echo '<tr><td><strong>Master Branch:</strong></td><td class="error">Error: ' . $response->get_error_message() . '</td></tr>';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                echo '<tr><td><strong>Master Branch:</strong></td><td class="success">Accessible</td></tr>';
            } else {
                echo '<tr><td><strong>Master Branch:</strong></td><td class="error">HTTP ' . $code . '</td></tr>';
            }
        }
        
        echo '</table>';
    }
    
    /**
     * Check update checker status
     */
    private function check_update_status() {
        global $puc_plugin_update_checker;
        
        echo '<table class="widefat">';
        
        // Check both global variable and GLOBALS array
        $update_checker = null;
        if (isset($puc_plugin_update_checker)) {
            $update_checker = $puc_plugin_update_checker;
        } elseif (isset($GLOBALS['puc_plugin_update_checker'])) {
            $update_checker = $GLOBALS['puc_plugin_update_checker'];
        }
        
        if ($update_checker) {
            echo '<tr><td><strong>Update Checker:</strong></td><td class="success">Initialized</td></tr>';
            
            // Get update checker details
            try {
                $update_info = $update_checker->getUpdate();
                if ($update_info) {
                    echo '<tr><td><strong>Update Available:</strong></td><td class="warning">Yes</td></tr>';
                    echo '<tr><td><strong>New Version:</strong></td><td>' . $update_info->version . '</td></tr>';
                } else {
                    echo '<tr><td><strong>Update Available:</strong></td><td class="success">No (up to date)</td></tr>';
                }
                
                // Get VCS API info
                try {
                    $vcs_api = $update_checker->getVcsApi();
                    if ($vcs_api) {
                        echo '<tr><td><strong>VCS API:</strong></td><td class="success">Available</td></tr>';
                        
                        // Get repository URL safely
                        try {
                            $repo_url = $vcs_api->getRepositoryUrl();
                            echo '<tr><td><strong>Repository URL:</strong></td><td>' . $repo_url . '</td></tr>';
                        } catch (Exception $e) {
                            echo '<tr><td><strong>Repository URL:</strong></td><td class="error">Error: ' . $e->getMessage() . '</td></tr>';
                        }
                        
                        // Get branch information safely
                        try {
                            $branch = $vcs_api->getBranch('master');
                            echo '<tr><td><strong>Branch:</strong></td><td>' . $branch . '</td></tr>';
                        } catch (Exception $e) {
                            echo '<tr><td><strong>Branch:</strong></td><td class="error">Error: ' . $e->getMessage() . '</td></tr>';
                        }
                    } else {
                        echo '<tr><td><strong>VCS API:</strong></td><td class="error">Not Available</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td><strong>VCS API:</strong></td><td class="error">Error: ' . $e->getMessage() . '</td></tr>';
                }
                
            } catch (Exception $e) {
                echo '<tr><td><strong>Update Check Error:</strong></td><td class="error">' . $e->getMessage() . '</td></tr>';
            }
        } else {
            echo '<tr><td><strong>Update Checker:</strong></td><td class="error">Not Initialized</td></tr>';
            echo '<tr><td><strong>Global Variable:</strong></td><td>' . (isset($puc_plugin_update_checker) ? 'Set' : 'Not Set') . '</td></tr>';
            echo '<tr><td><strong>GLOBALS Array:</strong></td><td>' . (isset($GLOBALS['puc_plugin_update_checker']) ? 'Set' : 'Not Set') . '</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Test SSL verification
     */
    private function test_ssl_verify() {
        $response = wp_remote_get('https://api.github.com', array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return '<span class="error">Failed: ' . $response->get_error_message() . '</span>';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                return '<span class="success">Working</span>';
            } else {
                return '<span class="error">HTTP ' . $code . '</span>';
            }
        }
    }
    
    /**
     * Test network connectivity
     */
    private function test_network_connectivity() {
        echo '<table class="widefat">';
        
        $test_urls = array(
            'GitHub API' => 'https://api.github.com',
            'GitHub Repository' => 'https://github.com/MakiOmar/Aramex-Woocommerce-api-integration',
            'Google DNS' => 'https://8.8.8.8'
        );
        
        foreach ($test_urls as $name => $url) {
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'sslverify' => false
            ));
            
            if (is_wp_error($response)) {
                echo '<tr><td><strong>' . $name . ':</strong></td><td class="error">Error: ' . $response->get_error_message() . '</td></tr>';
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if ($code >= 200 && $code < 400) {
                    echo '<tr><td><strong>' . $name . ':</strong></td><td class="success">HTTP ' . $code . '</td></tr>';
                } else {
                    echo '<tr><td><strong>' . $name . ':</strong></td><td class="warning">HTTP ' . $code . '</td></tr>';
                }
            }
        }
        
        echo '</table>';
    }
}

// Initialize the debug class
new MO_Aramex_Update_Debug();
