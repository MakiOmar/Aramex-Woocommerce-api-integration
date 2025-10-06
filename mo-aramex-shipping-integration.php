<?php
/**
 * Plugin Name: MO Aramex Shipping Integration
 * Plugin URI: https://github.com/maki3omar
 * Description: Professional Aramex shipping integration for WooCommerce with advanced features
 * Author: Mohammad Omar
 * Author URI: mailto:maki3omar@gmail.com
 * Version: 1.0.15
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
define('MO_ARAMEX_VERSION', '1.0.15');
define('MO_ARAMEX_PLUGIN_FILE', __FILE__);
define('MO_ARAMEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MO_ARAMEX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MO_ARAMEX_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize Plugin Update Checker
 */
require_once MO_ARAMEX_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize update checker and make it globally accessible
// Using custom update server to avoid GitHub API rate limiting
$GLOBALS['puc_plugin_update_checker'] = PucFactory::buildUpdateChecker(
    'https://github.com/MakiOmar/Aramex-Woocommerce-api-integration/raw/master/update-info.json',
    __FILE__,
    'mo-aramex-shipping-integration'
);

// Note: setBranch() is not available for custom update servers, only for VCS-based checkers

// Add custom headers to avoid rate limiting (using the correct method name)
if (method_exists($GLOBALS['puc_plugin_update_checker'], 'addHttpRequestArgFilter')) {
    $GLOBALS['puc_plugin_update_checker']->addHttpRequestArgFilter(function($options) {
        if (!isset($options['headers'])) {
            $options['headers'] = array();
        }
        
                       $options['headers']['User-Agent'] = 'MO-Aramex-Plugin/1.0.15';
        $options['headers']['Accept'] = 'application/vnd.github.v3+json';
        $options['headers']['X-MO-Aramex-Plugin'] = 'MO Aramex Shipping Integration';
        $options['headers']['X-Plugin-Version'] = MO_ARAMEX_VERSION;
        $options['headers']['Cache-Control'] = 'no-cache';
        
        return $options;
    });
}

// Add debugging hook to see what's happening
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'mo-aramex-update-debug') {
        $update_checker = $GLOBALS['puc_plugin_update_checker'] ?? null;
        if ($update_checker) {
            echo '<div class="notice notice-info"><p><strong>Update Checker Status:</strong> ';
            echo 'Active | Update Source: Custom Update Server | ';
            echo 'Current Version: ' . MO_ARAMEX_VERSION;
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p><strong>Update Checker Status:</strong> Not Initialized</p></div>';
        }
    }
});

// Load update debug class for troubleshooting
require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-update-debug.php';

// Load logging system
require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-logger.php';
require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-log-helper.php';
require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-log-viewer.php';

// Optional: If you're using a private repository, specify the access token.
// $updateChecker->setAuthentication('your-github-token');

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
 * Custom logging function
 */
function custom_plugin_log($message) {
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir']; // Base directory path

    $log_file_folder = $upload_path . '/aramex-shipping-plugin-logs/';

    if (!is_dir($log_file_folder)) {
        if (!mkdir($log_file_folder, 0777, true)) {
            echo "Error creating the directory.";
        }
    }

    $log_file = $log_file_folder . date('Y-m-d') . '.log';

    if (!file_exists($log_file)) {
        file_put_contents($log_file, '');
        chmod($log_file, 0666);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    error_log($log_message, 3, $log_file);
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
        // require_once MO_ARAMEX_PLUGIN_DIR . 'includes/class-mo-aramex-updater.php'; // Disabled - using main plugin update checker
        
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
        
        // Initialize update checker (disabled - using main plugin update checker instead)
        // new MO_Aramex_Updater();
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
        // Load all Aramex classes and register AJAX actions immediately
        $this->load_aramex_classes();
        
        // Add admin menu hooks for bulk operations
        add_action('admin_menu', array($this, 'add_admin_menu_hooks'));
        add_action('admin_footer', array($this, 'custom_aramex_bulk_admin_footer'));
        add_action('admin_footer', array($this, 'aramex_bulk_print_label_admin_footer'));
        
        // Add admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'load_aramex_wp_admin_style'));
        add_action('admin_enqueue_scripts', array($this, 'load_aramex_script_common'));
        add_action('admin_enqueue_scripts', array($this, 'load_aramex_script_jquery_chained'));
        add_action('admin_enqueue_scripts', array($this, 'load_aramex_script_validate_aramex'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_aramex_custom_plugin_styles_admin'));
        
        // Add frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'add_aramex_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_aramex_custom_plugin_styles'));
        
        // Add WooCommerce hooks
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aramex_display_order_data_in_admin'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aramex_display_rate_calculator_in_admin'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aramex_display_schedule_pickup_in_admin'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aramex_display_track_in_admin'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'aramex_display_printlabel_in_admin'));
        add_action('woocommerce_after_checkout_form', array($this, 'aramex_display_apilocationvalidator_in_checkout'));
        add_action('woocommerce_account_edit-address_endpoint', array($this, 'aramex_display_apilocationvalidator_in_account'));
        add_action('woocommerce_view_order', array($this, 'aramex_view_order_tracking'), 20);
        
        // Add template includes
        $this->include_admin_templates();
        
        // Add additional WooCommerce hooks
        add_action('woocommerce_review_order_before_cart_contents', array($this, 'aramex_validate_order'), 10);
        add_action('woocommerce_after_checkout_validation', array($this, 'aramex_validate_order'), 10);
        add_filter('woocommerce_locate_template', array($this, 'aramex_woocommerce_locate_template'), 10, 3);
        add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
        add_action('woocommerce_review_order_before_submit', array($this, 'woocommerce_review_order_after_submit'));
        add_action('woocommerce_before_checkout_billing_form', array($this, 'woocommerce_before_checkout_billing_form'));
        add_action('woocommerce_before_cart', array($this, 'woocommerce_before_cart'));
        add_action('woocommerce_product_meta_start', array($this, 'aramex_display_aramexcalculator'));
        add_filter('woocommerce_shipping_fields', array($this, 'aramex_woocommerce_shipping_fields'));
    }
    
    /**
     * Add admin menu hooks
     */
    public function add_admin_menu_hooks() {
        // Hook into WooCommerce orders page
        add_action('manage_shop_order_posts_custom_column', array($this, 'add_bulk_actions_to_orders'));
    }
    
    
    /**
     * Add bulk actions to orders (placeholder for future use)
     */
    public function add_bulk_actions_to_orders($column) {
        // This can be used to add custom columns or actions to the orders list
    }
    
    /**
     * Load all Aramex classes and register AJAX actions
     */
    public function load_aramex_classes() {
        // Debug: Log that we're loading classes
        if (function_exists('custom_plugin_log')) {
            custom_plugin_log('Loading Aramex classes and registering AJAX actions');
        }
        
        // Include required files first
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/core/class-mo-aramex-helper.php';
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipping/class-mo-aramex-shipping-method.php';
        
        // Include all shipment classes
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-ratecalculator.php';
        add_action('wp_ajax_the_aramex_rate_calculator', array(new Aramex_Ratecalculator_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-track.php';
        add_action('wp_ajax_the_aramex_track', array(new Aramex_Track_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-shedulepickup.php';
        add_action('wp_ajax_the_aramex_pickup', array(new Aramex_Shedule_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-printlabel.php';
        add_action('wp_ajax_the_aramex_print_lable', array(new Aramex_Printlabel_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk.php';
        add_action('wp_ajax_the_aramex_bulk', array(new Aramex_Bulk_Method(), 'run'));
        
        // Debug: Log AJAX action registration
        if (function_exists('custom_plugin_log')) {
            custom_plugin_log('AJAX action the_aramex_bulk registered successfully');
        }
        
        // Add a simple test AJAX action
        add_action('wp_ajax_test_aramex_ajax', function() {
            if (function_exists('custom_plugin_log')) {
                custom_plugin_log('Test AJAX action called successfully');
            }
            wp_die('Test AJAX working');
        });
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-bulk-printlabel.php';
        add_action('wp_ajax_the_aramex_bulk_printlabel', array(new Aramex_Bulk_Printlabel_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/shipment/class-aramex-woocommerce-shipment.php';
        add_action('admin_post_the_aramex_shipment', array(new Aramex_Shipment_Method(), 'run'));
        
        // Include calculator classes
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/aramexcalculator/class-aramex-woocommerce-aramexcalculator.php';
        add_action('wp_ajax_the_aramex_calculator', array(new Aramex_Aramexcalculator_Method(), 'run'));
        add_action('wp_ajax_nopriv_the_aramex_calculator', array(new Aramex_Aramexcalculator_Method(), 'run'));
        
        // Include location validator classes
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/apilocationvalidator/class-aramex-woocommerce-serchautocities.php';
        add_action('wp_ajax_the_aramex_searchautocities', array(new Aramex_Serchautocities_Method(), 'run'));
        add_action('wp_ajax_nopriv_the_aramex_searchautocities', array(new Aramex_Serchautocities_Method(), 'run'));
        
        require_once MO_ARAMEX_PLUGIN_DIR . 'includes/apilocationvalidator/class-aramex-woocommerce-applyvalidation.php';
        add_action('wp_ajax_the_aramex_appyvalidation', array(new Aramex_Applyvalidation_Method(), 'run'));
        add_action('wp_ajax_nopriv_the_aramex_appyvalidation', array(new Aramex_Applyvalidation_Method(), 'run'));
    }
    
    /**
     * Include admin templates
     */
    private function include_admin_templates() {
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/shipment.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/calculate_rate.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/schedule_pickup.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/track.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/printlabel.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/frontend/apilocationvalidator.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/frontend/aramexcalculator.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/frontend/apilocationvalidator_account.php';
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/frontend/account_track.php';
    }
    
    /**
     * Custom bulk admin footer
     */
    public function custom_aramex_bulk_admin_footer() {
        global $post_type;
        if ($post_type == 'shop_order' && isset($_GET['post_type']) || (isset($_GET['page']) && $_GET['page'] === 'wc-orders' && (!isset($_GET['action']) || $_GET['action'] !== 'edit'))) {
            include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/bulk.php';
            if (function_exists('aramex_display_bulk_in_admin')) {
                aramex_display_bulk_in_admin();
            }
        }
    }
    
    /**
     * Bulk print label admin footer
     */
    public function aramex_bulk_print_label_admin_footer() {
        global $post_type;
        if ($post_type == 'shop_order' && isset($_GET['post_type']) || (isset($_GET['page']) && $_GET['page'] === 'wc-orders' && (!isset($_GET['action']) || $_GET['action'] !== 'edit'))) {
            include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/bulk_printlabel.php';
            if (function_exists('aramex_display_bulk_printlabel_in_admin')) {
                aramex_display_bulk_printlabel_in_admin();
            }
        }
    }
    
    /**
     * Load Aramex admin styles
     */
    public function load_aramex_wp_admin_style() {
        wp_register_style('custom_wp_admin_css', MO_ARAMEX_PLUGIN_URL . 'assets/css/aramex.css');
        wp_enqueue_style('custom_wp_admin_css');
    }
    
    /**
     * Load common Aramex scripts
     */
    public function load_aramex_script_common() {
        wp_register_script('common_aramex', MO_ARAMEX_PLUGIN_URL . 'assets/js/common.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('common_aramex');
    }
    
    /**
     * Load jQuery chained scripts
     */
    public function load_aramex_script_jquery_chained() {
        wp_register_script('jquery_chained', MO_ARAMEX_PLUGIN_URL . 'assets/js/jquery.chained.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('jquery_chained');
    }
    
    /**
     * Load jQuery validation scripts
     */
    public function load_aramex_script_validate_aramex() {
        wp_register_script('validate_aramex', MO_ARAMEX_PLUGIN_URL . 'assets/js/jquery.validate.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('validate_aramex');
    }
    
    /**
     * Add Aramex scripts
     */
    public function add_aramex_scripts() {
        wp_enqueue_script('jquery-ui-autocomplete');
    }
    
    /**
     * Register frontend Aramex styles
     */
    public function register_frontend_aramex_custom_plugin_styles() {
        wp_register_style('aramex-stylesheet', MO_ARAMEX_PLUGIN_URL . 'assets/css/jquery-ui.css');
        wp_enqueue_style('aramex-stylesheet');
    }
    
    /**
     * Register admin Aramex styles
     */
    public function register_admin_aramex_custom_plugin_styles_admin() {
        wp_register_style('aramex-stylesheet', MO_ARAMEX_PLUGIN_URL . 'assets/css/jquery-ui.css');
        wp_enqueue_style('aramex-stylesheet');
    }
    
    /**
     * Display order data in admin (placeholder)
     */
    public function aramex_display_order_data_in_admin() {
        // This will be handled by the included templates
    }
    
    /**
     * Display rate calculator in admin (placeholder)
     */
    public function aramex_display_rate_calculator_in_admin() {
        // This will be handled by the included templates
    }
    
    /**
     * Display schedule pickup in admin (placeholder)
     */
    public function aramex_display_schedule_pickup_in_admin() {
        // This will be handled by the included templates
    }
    
    /**
     * Display track in admin (placeholder)
     */
    public function aramex_display_track_in_admin() {
        // This will be handled by the included templates
    }
    
    /**
     * Display print label in admin (placeholder)
     */
    public function aramex_display_printlabel_in_admin() {
        // This will be handled by the included templates
    }
    
    /**
     * Display API location validator in checkout (placeholder)
     */
    public function aramex_display_apilocationvalidator_in_checkout() {
        // This will be handled by the included templates
    }
    
    /**
     * Display API location validator in account (placeholder)
     */
    public function aramex_display_apilocationvalidator_in_account() {
        // This will be handled by the included templates
    }
    
    /**
     * View order tracking (placeholder)
     */
    public function aramex_view_order_tracking() {
        // This will be handled by the included templates
    }
    
    /**
     * Validate Aramex orders
     */
    public function aramex_validate_order($posted) {
        $packages = WC()->shipping->get_packages();
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        if (is_array($chosen_methods) && in_array('aramex', $chosen_methods)) {
            foreach ($packages as $i => $package) {
                if ($chosen_methods[$i] != "aramex") {
                    continue;
                }
                $weight = 0;
                foreach ($package['contents'] as $item_id => $values) {
                    $product = $values['data'];
                    $weight = $weight + $product->get_weight() * $values['quantity'];
                }
                $weight = wc_get_weight($weight, 'kg');
                if ($weight == 0) {
                    $message = __('Sorry, order weight must be greater than 0 kg', 'mo-aramex-shipping');
                    $messageType = "error";
                    if (!wc_has_notice($message, $messageType)) {
                        wc_add_notice($message, $messageType);
                    }
                }
            }
        }
    }
    
    /**
     * Overwrite woocommerce templates to plugin's woocommerce local folder
     */
    public function aramex_woocommerce_locate_template($template, $template_name, $template_path) {
        global $woocommerce;
        $template1 = $template;
        if (!$template_path) {
            $template_path = $woocommerce->template_url;
        }
        $plugin_path = MO_ARAMEX_PLUGIN_DIR . '/woocommerce/';
        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );
        // Modification: Get the template from this plugin, if it exists
        if (!$template && file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }
        // Use default template
        if (!$template) {
            $template = $template1;
        }
        // Return what we found
        return $template;
    }
    
    /**
     * Register Block Button Template
     */
    public function woocommerce_review_order_after_submit() {
        include_once MO_ARAMEX_PLUGIN_DIR . 'templates/adminhtml/block_button.php';
        if (function_exists('block_button')) {
            block_button();
        }
    }
    
    /**
     * Unset data in session
     */
    public function woocommerce_before_checkout_billing_form() {
        WC()->session->__unset('aramex_visit_checkout');
        WC()->session->__unset('aramex_set_first_success');
    }
    
    /**
     * Unset Data in Session
     */
    public function woocommerce_before_cart() {
        WC()->session->__unset('aramex_visit_checkout');
        WC()->session->__unset('aramex_set_first_success');
    }
    
    /**
     * Register Aramexcalculator Template
     */
    public function aramex_display_aramexcalculator() {
        $user_id = get_current_user_id();
        $settings = new MO_Aramex_Shipping_Method();
        $countries_obj = new WC_Countries();
        global $product;
        $data = array();
        $data['aramexcalculator'] = $settings->settings['aramexcalculator'];
        $data['countries'] = $countries_obj->__get('countries');
        $data['customer_city'] = get_user_meta($user_id, 'shipping_city', true);
        $data['customer_country'] = get_user_meta($user_id, 'shipping_country', true);
        $data['customer_postcode'] = get_user_meta($user_id, 'shipping_postcode', true);
        $data['product_id'] = $product->get_id();
        $data['currency'] = get_woocommerce_currency();
        if (function_exists('aramex_display_aramexcalculator_in_frontend')) {
            aramex_display_aramexcalculator_in_frontend($data);
        }
    }
    
    /**
     * Add custom fields to Check out page
     */
    public function aramex_woocommerce_shipping_fields($fields) {
        $fields['shipping_phone'] = array(
            'label'       =>  __('Phone', 'mo-aramex-shipping'),            
            'required'    => true,            
            'clear'       => false,            
            'type'        => 'tel',                
            'class'       => array('validate-phone')
        );
       
        $fields['shipping_email'] = array(
            'label'       =>  __('Email address', 'mo-aramex-shipping'), 
            'required'    => true,      
            'clear'       => false,
            'type'        => 'email',
            'class'       => array('validate-email')
        );
        return $fields;
    }
    
    /**
     * Get plugins file path
     */
    public function aramex_plugin_plugin_path() {
        // gets the absolute path to this plugin directory
        return untrailingslashit(MO_ARAMEX_PLUGIN_DIR);
    }
    
    
    /**
     * Load plugin text domain
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain('mo-aramex-shipping', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
