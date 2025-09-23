<?php
/*
Plugin Name:  Aramex Shipping WooCommerce
Plugin URI:   https://aramex.com
Description:  Aramex Shipping WooCommerce plugin
Version:      1.0.0
Author:       aramex.com
Author URI:   https://www.aramex.com/solutions-services/developers-solutions-center
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  aramex
Domain Path:  /languages
*/

if (!class_exists('Aramex_Helper')) {

    /**
     * Class Aramex_Helper is a helper
     */
    class Aramex_Helper
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
            if (wp_verify_nonce($nonce, 'aramex-shipment-check' . wp_get_current_user()->user_email) == false) {
                echo(__('Invalid form data.', 'aramex'));
                die();
            }

            $includedStuff = get_included_files();
            $string = 'wp-config.php';
            $found = false;
            foreach ($includedStuff as $key => $url) {
                if (strpos($url, $string) !== false) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                require_once('../../../../../wp-config.php');
            }
            return get_option('woocommerce_aramex_settings');
        }

        /**
         * Get Path to WSDL file
         *
         * @param string $nonce Nonce
         * @return string Path to Wsdl file
         */
        private function getWsdlPath($nonce)
        {
            $settings = $this->getSettings($nonce);
            if ($settings['sandbox_flag'] == 1) {
                $path = $this->getPath() . 'test/';
            } else {
                $path = $this->getPath();
            }
            return $path;
        }

        /**
         * Get admin`s settings
         *
         * @param string $nonce Nonce
         * @return array Admin settings
         */
        private function getClientInfo($nonce)
        {
            $settings = $this->getSettings($nonce);
            return array(
                'AccountCountryCode' => $settings['account_country_code'],
                'AccountEntity' => $settings['account_entity'],
                'AccountNumber' => $settings['account_number'],
                'AccountPin' => $settings['account_pin'],
                'UserName' => $settings['user_name'],
                'Password' => $settings['password'],
                'Version' => 'v1.0',
                'Source' => 52,
                'address' => $settings['address'],
                'city' => $settings['city'],
                'state' => $settings['state'],
                'postalcode' => $settings['postalcode'],
                'country' => $settings['country'],
                'name' => $settings['name'],
                'company' => $settings['company'],
                'phone' => $settings['phone'],
                'email' => $settings['email_origin'],
                'report_id' => $settings['report_id'],
            );
        }

        /**
         * Get admin`s COD settings
         *
         * @param string $nonce Nonce
         * @return array Admin`s COD settings
         */
        private function getClientInfoCOD($nonce)
        {
            $settings = $this->getSettings($nonce);
            return array(
                'AccountCountryCode' => $settings['cod_account_country_code'],
                'AccountEntity' => $settings['cod_account_entity'],
                'AccountNumber' => $settings['cod_account_number'],
                'AccountPin' => $settings['cod_account_pin'],
                'UserName' => $settings['user_name'],
                'Password' => $settings['password'],
                'Version' => 'v1.0',
                'Source' => 52,
                'address' => $settings['address'],
                'city' => $settings['city'],
                'state' => $settings['state'],
                'postalcode' => $settings['postalcode'],
                'country' => $settings['country'],
                'name' => $settings['name'],
                'company' => $settings['company'],
                'phone' => $settings['phone'],
                'email' => $settings['email_origin'],
                'report_id' => $settings['report_id'],

            );
        }

        /**
         * Get admin`s Email settings
         *
         * @param string $nonce Nonce
         * @return array Admin`s Email settings
         */
        private function getEmailOptions($nonce)
        {
            $settings = $this->getSettings($nonce);
            return array(
                'copy_to' => $settings['copy_to'],
                'copy_method' => $settings['copy_method'],
            );
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
        protected function getInfo($nonce)
        {
            $baseUrl = $this->getWsdlPath($nonce);
            $clientInfo = $this->getClientInfo($nonce);
            $copyInfo = $this->getEmailOptions($nonce);
            return (array('baseUrl' => $baseUrl, 'clientInfo' => $clientInfo, '$copyInfo' => $copyInfo));
        }

        /**
         * Get info about Admin (COD)
         *
         * @param $nonce Nonce
         * @return array Admin info
         */
        protected function getInfoCod($nonce)
        {
            $baseUrl = $this->getWsdlPath($nonce);
            $clientInfo = $this->getClientInfoCOD($nonce);
            $copyInfo = $this->getEmailOptions($nonce);
            return (array('baseUrl' => $baseUrl, 'clientInfo' => $clientInfo, '$copyInfo' => $copyInfo));
        }
    }
}
