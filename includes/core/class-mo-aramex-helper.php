<?php
/**
 * MO Aramex Helper Class
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MO_Aramex_Helper')) {

    /**
     * Class MO_Aramex_Helper is a helper for MO Aramex shipping
     */
    class MO_Aramex_Helper
    {
        /**
         * Path to WSDL file
         *
         * @var string Path to WSDL file
         */
        protected $wsdlBasePath;

        /**
         * Get path to WSDL file
         *
         * @return string Path to WSDL file
         */
        private function getPath()
        {
            return __DIR__ . '/../../wsdl/';
        }

        /**
         * Get Settings
         *
         * @param string $nonce Nonce
         * @return mixed|void Settings
         */
        private function getSettings($nonce)
        {
            if (wp_verify_nonce($nonce, 'mo-aramex-shipment-check' . wp_get_current_user()->user_email) == false) {
                echo(__('Invalid form data.', 'mo-aramex-shipping'));
                die();
            }

            $settings = array(
                'api_source' => get_option('woocommerce_mo-aramex_settings')['api_source'],
                'api_username' => get_option('woocommerce_mo-aramex_settings')['username'],
                'api_password' => get_option('woocommerce_mo-aramex_settings')['password'],
                'api_account_pin' => get_option('woocommerce_mo-aramex_settings')['account_pin'],
                'api_account_number' => get_option('woocommerce_mo-aramex_settings')['account_number'],
                'api_account_entity' => get_option('woocommerce_mo-aramex_settings')['account_entity'],
                'api_account_country_code' => get_option('woocommerce_mo-aramex_settings')['account_country_code'],
                'api_version' => get_option('woocommerce_mo-aramex_settings')['api_version'],
                'api_company' => get_option('woocommerce_mo-aramex_settings')['company'],
                'api_address' => get_option('woocommerce_mo-aramex_settings')['address'],
                'api_city' => get_option('woocommerce_mo-aramex_settings')['city'],
                'api_state' => get_option('woocommerce_mo-aramex_settings')['state'],
                'api_postal_code' => get_option('woocommerce_mo-aramex_settings')['postal_code'],
                'api_country_code' => get_option('woocommerce_mo-aramex_settings')['country_code'],
                'api_name' => get_option('woocommerce_mo-aramex_settings')['name'],
                'api_email' => get_option('woocommerce_mo-aramex_settings')['email'],
                'api_phone' => get_option('woocommerce_mo-aramex_settings')['phone'],
                'api_phone_ext' => get_option('woocommerce_mo-aramex_settings')['phone_ext'],
                'api_cell_phone' => get_option('woocommerce_mo-aramex_settings')['cell_phone'],
                'api_comment' => get_option('woocommerce_mo-aramex_settings')['comment'],
                'api_reference' => get_option('woocommerce_mo-aramex_settings')['reference'],
                'api_address_book' => get_option('woocommerce_mo-aramex_settings')['address_book'],
            );

            return $settings;
        }

        /**
         * Get client info
         *
         * @param array $settings Settings
         * @return array Client info
         */
        public function getClientInfo($settings)
        {
            $clientInfo = array(
                'UserName' => $settings['api_username'],
                'Password' => $settings['api_password'],
                'Version' => $settings['api_version'],
                'AccountNumber' => $settings['api_account_number'],
                'AccountPin' => $settings['api_account_pin'],
                'AccountEntity' => $settings['api_account_entity'],
                'AccountCountryCode' => $settings['api_account_country_code'],
            );

            return $clientInfo;
        }

        /**
         * Get sender info
         *
         * @param array $settings Settings
         * @return array Sender info
         */
        public function getSenderInfo($settings)
        {
            $senderInfo = array(
                'Reference1' => $settings['api_reference'],
                'Reference2' => '',
                'AccountNumber' => $settings['api_account_number'],
                'Company' => $settings['api_company'],
                'AddressLine1' => $settings['api_address'],
                'City' => $settings['api_city'],
                'StateOrProvinceCode' => $settings['api_state'],
                'PostCode' => $settings['api_postal_code'],
                'CountryCode' => $settings['api_country_code'],
                'Contact' => array(
                    'Department' => '',
                    'PersonName' => $settings['api_name'],
                    'Title' => '',
                    'CompanyName' => $settings['api_company'],
                    'PhoneNumber1' => $settings['api_phone'],
                    'PhoneNumber1Ext' => $settings['api_phone_ext'],
                    'PhoneNumber2' => $settings['api_cell_phone'],
                    'FaxNumber' => '',
                    'CellPhone' => $settings['api_cell_phone'],
                    'EmailAddress' => $settings['api_email'],
                    'Type' => ''
                ),
            );

            return $senderInfo;
        }

        /**
         * Get WSDL path
         *
         * @param string $wsdl WSDL filename
         * @return string Full WSDL path
         */
        public function getWsdlPath($wsdl)
        {
            return $this->getPath() . $wsdl;
        }

        /**
         * Log API calls
         *
         * @param string $message Log message
         * @param string $level Log level
         */
        public function log($message, $level = 'info')
        {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MO Aramex: ' . $message);
            }
        }

        /**
         * Format input data
         *
         * @param $array Input data
         * @return array Result of formatting
         */
        protected function formatPost($array)
        {
            $out = array();
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $key1 => $val1) {
                        if (!is_array($val1)) {
                            if ($val1 != "") {
                                $out[$key][$key1] = htmlspecialchars(strip_tags(trim(sanitize_text_field($val1))));
                            } else {
                                $out[$key][$key1] = "";
                            }
                        }
                    }
                } else {
                    $out[$key] = htmlspecialchars(strip_tags(trim(sanitize_text_field($val))));
                }
            }
            return $out;
        }

        /**
         * Get info about Admin
         *
         * @param $nonce Nonce
         * @return array Admin info
         */
        public static function getInfo($nonce)
        {
            // The MO_Aramex_Shipping_Method class saves settings under 'woocommerce_mo-aramex_settings'
            $settings = get_option('woocommerce_mo-aramex_settings');
            
            // Debug: Log what we're getting from the database
            if (function_exists('custom_plugin_log')) {
                custom_plugin_log('Database settings for woocommerce_mo-aramex_settings: ' . print_r($settings, true));
            }
            
            if (!$settings) {
                // Try alternative key (original aramex settings)
                $alt_settings = get_option('woocommerce_aramex_settings');
                if (function_exists('custom_plugin_log')) {
                    custom_plugin_log('Alternative settings for woocommerce_aramex_settings: ' . print_r($alt_settings, true));
                }
                if ($alt_settings) {
                    $settings = $alt_settings;
                } else {
                    return array();
                }
            }

            // Determine base URL based on sandbox flag
            $wsdl_path = __DIR__ . '/../../wsdl/';
            if (isset($settings['sandbox_flag']) && $settings['sandbox_flag'] == 1) {
                $baseUrl = $wsdl_path . 'test/';
            } else {
                $baseUrl = $wsdl_path;
            }

            $clientInfo = array(
                'AccountCountryCode' => $settings['account_country_code'] ?? '',
                'AccountEntity' => $settings['account_entity'] ?? '',
                'AccountNumber' => $settings['account_number'] ?? '',
                'AccountPin' => $settings['account_pin'] ?? '',
                'UserName' => $settings['user_name'] ?? '',
                'Password' => $settings['password'] ?? '',
                'Version' => 'v1.0',
                'Source' => 52,
                'address' => $settings['address'] ?? '',
                'city' => $settings['city'] ?? '',
                'state' => $settings['state'] ?? '',
                'postalcode' => $settings['postalcode'] ?? '',
                'country' => $settings['country'] ?? '',
                'name' => $settings['name'] ?? '',
                'company' => $settings['company'] ?? '',
                'phone' => $settings['phone'] ?? '',
                'email' => $settings['email_origin'] ?? '',
                'report_id' => $settings['report_id'] ?? '',
            );

            $copyInfo = array(
                'copy_to' => $settings['copy_to'] ?? '',
                'copy_method' => $settings['copy_method'] ?? '',
            );

            return array('baseUrl' => $baseUrl, 'clientInfo' => $clientInfo, 'copyInfo' => $copyInfo);
        }
    }
}
