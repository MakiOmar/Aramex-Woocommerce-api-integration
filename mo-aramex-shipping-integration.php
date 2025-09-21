<?php
/**
 * Plugin Name: MO Aramex Shipping Integration
 * Plugin URI: https://github.com/maki3omar
 * Description: Professional Aramex shipping integration for WooCommerce with advanced features
 * Author: Mohammad Omar
 * Author URI: mailto:maki3omar@gmail.com
 * Version: 1.0.0
 * Text Domain: mo-aramex-shipping
 * Domain Path: /languages
 * Requires at least: 5.3
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MO_ARAMEX_VERSION', '1.0.0');
define('MO_ARAMEX_PLUGIN_FILE', __FILE__);
define('MO_ARAMEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MO_ARAMEX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MO_ARAMEX_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation check
 *
 * @return void
 */
function mo_aramex_activation_check()
{
    if (!class_exists('SoapClient')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__('Sorry, but you cannot run this plugin, it requires the',
                'mo-aramex-shipping') . "<a href='http://php.net/manual/en/class.soapclient.php'>SOAP</a>" . __(' support on your server/hosting to function.',
                'mo-aramex-shipping'));
    }
}

register_activation_hook(__FILE__, 'mo_aramex_activation_check');

/**
 * Check if WooCommerce is active
 */
function mo_aramex_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'mo_aramex_woocommerce_missing_notice');
        return;
    }
    
    // Initialize the plugin
    MO_Aramex_Plugin::instance();
}
add_action('plugins_loaded', 'mo_aramex_init');

/**
 * WooCommerce missing notice
 */
function mo_aramex_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>' . __('MO Aramex Shipping Integration', 'mo-aramex-shipping') . '</strong> ' . __('requires WooCommerce to be installed and active.', 'mo-aramex-shipping') . '</p></div>';
}

/**
 * Main Plugin Class
 */
class MO_Aramex_Plugin {
    
    /**
     * Plugin instance
     *
     * @var MO_Aramex_Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return MO_Aramex_Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Define constants
     */
    private function define_constants() {
        // Constants already defined above
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/core/class-mo-aramex-helper.php';
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipping/class-mo-aramex-shipping-method.php';
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-updater.php';
        
        // Include bulk operation classes
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk.php';
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk-printlabel.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'init_shipping_method'));
        add_action('admin_init', array($this, 'init_admin_hooks'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        $this->load_plugin_textdomain();
        
        // Initialize shipping method
        $this->init_shipping_method();
        
        // Initialize update checker
        new MO_Aramex_Updater();
    }
    
    /**
     * Initialize shipping method
     */
    public function init_shipping_method() {
        if (class_exists('WC_Shipping_Method')) {
            add_action('woocommerce_shipping_init', array($this, 'include_shipping_method'));
            add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
        }
    }
    
    /**
     * Include shipping method
     */
    public function include_shipping_method() {
        if (!class_exists('MO_Aramex_Shipping_Method')) {
            require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipping/class-mo-aramex-shipping-method.php';
        }
    }
    
    /**
     * Add shipping method
     *
     * @param array $methods
     * @return array
     */
    public function add_shipping_method($methods) {
        $methods['mo-aramex'] = 'MO_Aramex_Shipping_Method';
        return $methods;
    }
    
    /**
     * Initialize admin hooks
     */
    public function init_admin_hooks() {
        // Register AJAX handlers for bulk operations
        add_action('wp_ajax_the_aramex_bulk', array($this, 'handle_bulk_shipment'));
        add_action('wp_ajax_the_aramex_bulk_printlabel', array($this, 'handle_bulk_printlabel'));
        
        // Add admin menu hooks for bulk operations
        add_action('admin_menu', array($this, 'add_admin_menu_hooks'));
        add_action('admin_footer', array($this, 'add_bulk_buttons_to_orders_page'));
    }
    
    /**
     * Add admin menu hooks
     */
    public function add_admin_menu_hooks() {
        // Hook into WooCommerce orders page
        add_action('manage_shop_order_posts_custom_column', array($this, 'add_bulk_actions_to_orders'));
    }
    
    /**
     * Add bulk buttons to orders page
     */
    public function add_bulk_buttons_to_orders_page() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-shop_order') {
            // Include bulk templates
            include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/bulk.php';
            include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/bulk_printlabel.php';
            
            // Call the functions to display bulk forms
            if (function_exists('aramex_display_bulk_in_admin')) {
                aramex_display_bulk_in_admin();
            }
            if (function_exists('aramex_display_bulk_printlabel_in_admin')) {
                aramex_display_bulk_printlabel_in_admin();
            }
        }
    }
    
    /**
     * Handle bulk shipment creation
     */
    public function handle_bulk_shipment() {
        // Include the bulk shipment class
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk.php';
        
        // Create instance and run
        $bulk_method = new Aramex_Bulk_Method();
        $bulk_method->run();
    }
    
    /**
     * Handle bulk print label
     */
    public function handle_bulk_printlabel() {
        // Include the bulk print label class
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk-printlabel.php';
        
        // Create instance and run
        $bulk_printlabel_method = new Aramex_Bulk_Printlabel_Method();
        $bulk_printlabel_method->run();
    }
    
    /**
     * Add bulk actions to orders (placeholder for future use)
     */
    public function add_bulk_actions_to_orders($column) {
        // This can be used to add custom columns or actions to the orders list
    }
    
    /**
     * Load plugin text domain
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain('mo-aramex-shipping', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
