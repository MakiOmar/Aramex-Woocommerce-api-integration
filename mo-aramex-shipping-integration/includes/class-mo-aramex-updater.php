<?php
/**
 * MO Aramex Update Checker
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MO Aramex Update Checker Class
 */
class MO_Aramex_Updater {

    /**
     * Update checker instance
     *
     * @var object
     */
    private $update_checker;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_update_checker();
    }

    /**
     * Initialize the update checker
     */
    private function init_update_checker() {
        // Include the update checker
        require_once MO_ARAMEX_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
        
        // Initialize GitHub updater
        $this->init_github_updater();
    }

    /**
     * Initialize GitHub updater
     */
    private function init_github_updater() {
        $this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/MakiOmar/MO-Aramex-Shipping', // GitHub repository
            MO_ARAMEX_PLUGIN_FILE,
            'mo-aramex-shipping'
        );
        
        // Set branch (optional)
        $this->update_checker->setBranch('master');
        
        // Add license validation (optional)
        $this->update_checker->addQueryArgFilter(array($this, 'add_license_to_request'));
        
        // Add custom headers (optional) - Note: method name changed in v5p6
        if (method_exists($this->update_checker, 'addHttpRequestArgFilter')) {
            $this->update_checker->addHttpRequestArgFilter(array($this, 'add_custom_headers'));
        }
    }

    /**
     * Add license key to update requests
     *
     * @param array $queryArgs
     * @return array
     */
    public function add_license_to_request($queryArgs) {
        $license_key = get_option('mo_aramex_license_key', '');
        if (!empty($license_key)) {
            $queryArgs['license_key'] = $license_key;
        }
        
        // Add site URL for validation
        $queryArgs['site_url'] = home_url();
        
        return $queryArgs;
    }

    /**
     * Add custom headers to requests
     *
     * @param array $options
     * @return array
     */
    public function add_custom_headers($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
        $options['headers']['X-MO-Aramex-Plugin'] = 'MO Aramex Shipping Integration';
        $options['headers']['X-Plugin-Version'] = MO_ARAMEX_VERSION;
        
        return $options;
    }

    /**
     * Get update checker instance
     *
     * @return object
     */
    public function get_update_checker() {
        return $this->update_checker;
    }
}
