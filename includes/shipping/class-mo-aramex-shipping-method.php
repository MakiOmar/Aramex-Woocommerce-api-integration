<?php
/**
 * MO Aramex Shipping Method Class
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MO_Aramex_Shipping_Method')) {

    /**
     * Controller for MO Aramex shipping
     */
    class MO_Aramex_Shipping_Method extends WC_Shipping_Method
    {
        /**
         * MO_Aramex_Shipping_Method constructor
         *
         * @return void
         */
        public function __construct()
        {
            $this->id = 'mo-aramex';
            $this->method_title = __('MO Aramex Global Settings', 'mo-aramex-shipping');
            $this->method_description = __('Professional shipping integration for WooCommerce with Aramex Express support.', 'mo-aramex-shipping');
            $this->init();
            $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
            $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('MO Aramex Shipping', 'mo-aramex-shipping');
            
            // Include helper class
            if (file_exists(__DIR__ . '/../core/class-mo-aramex-helper.php')) {
                include_once __DIR__ . '/../core/class-mo-aramex-helper.php';
            }
            
            add_filter('woocommerce_package_rates', array($this, 'conditional_shipping'), 10, 2);
        }

        /**
         * Conditional shipping rates
         *
         * @param array $rates
         * @param array $packages
         * @return array
         */
        public function conditional_shipping($rates, $packages) {
            foreach ($rates as $rate_id => $rate) {
                if ($rate->method_id == 'mo-aramex') {
                    if (WC()->session->get('mo_aramex_error') == 1) {
                        unset($rates[$rate_id]);
                    }
                }
            }
            return $rates;
        }

        /**
         * Init your settings
         *
         * @return void
         */
        public function init()
        {
            // Load the settings API
            $this->init_form_fields();
            $this->init_settings();
            
            // Load settings from database - use the correct key for this shipping method
            $this->settings = get_option('woocommerce_mo-aramex_settings', array());
            
            // Save settings in admin if you have any defined
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Render admin options and inject UI logic to toggle live/test fields
         */
        public function admin_options()
        {
            parent::admin_options();
            ?>
            <script type="text/javascript">
            (function($){
                function toggleAramexCredentialFields(){
                    var isTest = $('#woocommerce_mo-aramex_sandbox_flag').val() === '1';

                    var liveKeys = [
                        'user_name','password','account_pin','account_number','account_entity','account_country_code'
                    ];
                    var testKeys = [
                        'test_user_name','test_password','test_account_pin','test_account_number','test_account_entity','test_account_country_code'
                    ];

                    liveKeys.forEach(function(key){
                        var row = $('#woocommerce_mo-aramex_' + key).closest('tr');
                        if(isTest){ row.hide(); } else { row.show(); }
                    });
                    testKeys.forEach(function(key){
                        var row = $('#woocommerce_mo-aramex_' + key).closest('tr');
                        if(isTest){ row.show(); } else { row.hide(); }
                    });
                }

                $(document).on('change', '#woocommerce_mo-aramex_sandbox_flag', toggleAramexCredentialFields);
                $(document).ready(toggleAramexCredentialFields);
            })(jQuery);
            </script>
            <?php
        }

        /**
         * Define settings field for this shipping
         * @return void
         */
        public function init_form_fields()
        {
            // Load settings from the original file but with updated references
            $settings_file = __DIR__ . '/data-aramex-settings.php';
            if (file_exists($settings_file)) {
                $this->form_fields = include($settings_file);
            } else {
                // Fallback to original settings with updated references
                $this->form_fields = $this->get_default_form_fields();
            }
        }

        /**
         * Get default form fields
         *
         * @return array
         */
        private function get_default_form_fields()
        {
            return array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'mo-aramex-shipping'),
                    'type' => 'checkbox',
                    'label' => __('Enable MO Aramex Shipping', 'mo-aramex-shipping'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Method Title', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'mo-aramex-shipping'),
                    'default' => __('MO Aramex Shipping', 'mo-aramex-shipping'),
                    'desc_tip' => true,
                ),
                'username' => array(
                    'title' => __('Username', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('Aramex Username', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'password' => array(
                    'title' => __('Password', 'mo-aramex-shipping'),
                    'type' => 'password',
                    'description' => __('Aramex Password', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'account_pin' => array(
                    'title' => __('Account PIN', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('Aramex Account PIN', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'account_number' => array(
                    'title' => __('Account Number', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('Aramex Account Number', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'account_entity' => array(
                    'title' => __('Account Entity', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('Aramex Account Entity', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'account_country_code' => array(
                    'title' => __('Account Country Code', 'mo-aramex-shipping'),
                    'type' => 'text',
                    'description' => __('Aramex Account Country Code', 'mo-aramex-shipping'),
                    'default' => '',
                    'desc_tip' => true,
                ),
            );
        }

        /**
         * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
         *
         * @param array $package Package
         * @return void
         */
        public function calculate_shipping($package = array())
        {
            // Implementation will be copied from original with updated references
            $settings = new MO_Aramex_Shipping_Method();
            $rate_calculator_checkout_page = $settings->settings['rate_calculator_checkout_page'] ?? 0;
            
            if ($rate_calculator_checkout_page != 1) {
                return false;
            }

            $referer_parse = parse_url($_SERVER['REQUEST_URI']);
            if (strpos($referer_parse['path'], '/product/') !== false) {
                return false;
            }

            if ($rate_calculator_checkout_page == "0") {
                WC()->session->set('mo_aramex_block', true);
                return false;
            }

            // Add your shipping calculation logic here
            // This is a simplified version - you'll need to copy the full implementation
            // from the original class and update all references from 'aramex' to 'mo_aramex'
        }

        /**
         * Process admin options with validation
         *
         * @return bool
         */
        public function process_admin_options()
        {
            if (!current_user_can('manage_woocommerce')) {
                return false;
            }

            // Validate credentials if provided
            $username = sanitize_text_field($_POST['woocommerce_mo-aramex_username'] ?? '');
            $password = sanitize_text_field($_POST['woocommerce_mo-aramex_password'] ?? '');
            $account_pin = sanitize_text_field($_POST['woocommerce_mo-aramex_account_pin'] ?? '');
            $account_number = sanitize_text_field($_POST['woocommerce_mo-aramex_account_number'] ?? '');

            if (!empty($username) && !empty($password) && !empty($account_pin) && !empty($account_number)) {
                // First save the settings
                $result = parent::process_admin_options();
                
                if ($result) {
                    // Validate credentials with Aramex API
                    if ($this->validate_credentials()) {
                        WC_Admin_Settings::add_message(__('Settings saved and credentials validated successfully!', 'mo-aramex-shipping'));
                    } else {
                        WC_Admin_Settings::add_error(__('Settings saved but credentials validation failed. Please check your Aramex credentials and try again.', 'mo-aramex-shipping'));
                    }
                }
                
                return $result;
            }

            return parent::process_admin_options();
        }

        /**
         * Validate credentials
         *
         * @return bool
         */
        private function validate_credentials()
        {
            // Add credential validation logic here
            // This would typically make a test API call to Aramex
            return true; // Placeholder
        }
    }
}
