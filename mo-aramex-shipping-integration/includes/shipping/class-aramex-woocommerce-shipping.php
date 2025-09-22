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

if (!class_exists('Aramex_Shipping_Method')) {

    /**
     * Controller for Aramex shipping
     */
    class Aramex_Shipping_Method extends WC_Shipping_Method
    {
        /**
         * Aramex_Shipping_Method constructor
         *
         * @return void
         */
        public function __construct()
        {
            $this->id = 'aramex';
            $this->method_title = __('Aramex Global Settings', 'aramex');
            $this->method_description = __('Shipping Method for Aramex', 'aramex');
            $this->init();
            $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
            $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Aramex Shipping', 'aramex');
            include_once __DIR__ . '../../core/class-aramex-helper.php';
            add_filter( 'woocommerce_package_rates', array( $this , 'conditional_shipping' ), 10, 2 );
        }

        function conditional_shipping( $rates, $packages ) {
            foreach ( $rates as $rate_id => $rate ) {
                if($rate->method_id=='aramex'){
                    if(WC()->session->get('aramex_error')==1){
                        unset( $rates[$rate_id] );
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
            // Save settings in admin if you have any defined
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Define settings field for this shipping
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = include('data-aramex-settings.php');
        }

        /**
         * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
         *
         * @param array $package Package
         * @return void
         */
        public function calculate_shipping($package = array())
        {
            $settings = new Aramex_Shipping_Method();
            $allowed_domestic_methods = $settings->form_fields['allowed_domestic_methods']['options'];
            $allowed_international_methods = $settings->form_fields['allowed_international_methods']['options'];
            $rate_calculator_checkout_page = $settings->settings['rate_calculator_checkout_page'];
            $rate_calculator_checkout_page_only_for_international = $settings->settings['rate_calculator_checkout_page_only_for_international'];
            $weightUnit = get_option('woocommerce_weight_unit');
            if ($weightUnit == 'lbs'){
                $weightUnit = 'lb';
            }
            if ($rate_calculator_checkout_page != 1) {
                return false;
            }

            $referer_parse = parse_url($_SERVER['REQUEST_URI']);
            if (strpos($referer_parse['path'], '/product/')!==false) {
                return false;
            }
            $rate_calculator_checkout_page = $this->settings['rate_calculator_checkout_page'];
            if ($rate_calculator_checkout_page == "0") {
                WC()->session->set('aramex_block', true);
                return false;
            } else {
                WC()->session->set('aramex_block', false);
            }
            $pkgWeight = 0;
            $pkgQty = 0;
            $weight = 0;
            $itemDetails = array();
            $dimensions_lengths = array();
            $dimensions_widths = array();
            $dimensions_height = 0;
            $dimensions_unit = '';
            foreach ($package['contents'] as $item_id => $values) {
                $product = $values['data'];
                if( $product->is_type( 'simple' ) ){
                    // a simple product
                    $array_weight = $product->get_data();
                    $weight = $array_weight['weight'];

                  } elseif( $product->is_type( 'variation' ) || $product->is_type( 'variable' ) ){
                    // a variable product
                    $array_weight = $product->get_data();
                    if(empty($array_weight['weight'])){
                        $parent_weight = $product->get_parent_data();
                        $weight =  $parent_weight['weight'];
                    }else{
                        $weight = $array_weight['weight'];
                    }
                  }
                $pkgWeight = $pkgWeight + (float)$weight * $values['quantity'];
                $pkgQty = $pkgQty + $values['quantity'];

                $product_data = $product->get_data();
                $product_name = $product_data['name'];
                $product_weight = $product_data['weight'];
                $product_length = $product_data['length'];
                $product_width = $product_data['width'];
                $product_price = 0;
                $product_height = (float) $product_data['height'];
                $regular_price = $product_data['regular_price'];
                $sale_price = $product_data['sale_price'];
                $product_weight_unit = get_option('woocommerce_weight_unit');
                $CurrencyCode = get_woocommerce_currency();
                $product_id = $product_data['id'];
                $product_unit = get_option('woocommerce_dimension_unit');
                if(!empty($sale_price)){
                    $product_price = $sale_price;
                }else{
                    $product_price = $regular_price;
                }
                
                $dimensions_height += $product_height;
                
                $dimensions_unit = $product_unit;
                array_push($dimensions_widths,$product_width);
                array_push($dimensions_lengths,$product_length);    

                array_push($itemDetails,[
                        'Quantity' => $values['quantity'],
                        'Weight' => [
                            'Value' => $product_weight,
                            'Unit' => $product_weight_unit
                        ],
                        'GoodsDescription' => $product_name,
                        'CustomsValue' => [
                            'Value' => $product_price,
                            'CurrencyCode' => $CurrencyCode
                        ],
                        'PiecesDimensions' => [
                            'Dimensions' =>  array(
                                'Length' => $product_length,
                                'Width' => $product_width,
                                'Height' => $product_height,
                                'Unit' =>  $product_unit
                            ),
                        ],
                                   
                    ]);
            }
         
            $product_group = 'EXP';
            $allowed_methods = array();
            $allowed_methods = $this->settings['allowed_international_methods'];
            if (strtolower($this->settings['country']) == strtolower($package['destination']['country'])) {
                $product_group = 'DOM';
                $allowed_methods = $this->settings['allowed_domestic_methods'];
            }
            if(!empty($rate_calculator_checkout_page_only_for_international) && $product_group == 'DOM'){
            	WC()->session->set('aramex_block', false);
            	return;
            }

            $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
            if(!is_array($allowed_methods)){
            	return;
            }
            $dimensions_length = max($dimensions_lengths);
            $dimensions_width = max($dimensions_widths);
            foreach ($allowed_methods as $key => $allowed_method) {
                $price = "";
                $curr_code = "";
                $post_code = str_replace(" ","",$package['destination']['postcode']);
                $OriginAddress = array(
                    'StateOrProvinceCode' => $this->settings['state'],
                    'City' => $this->settings['city'],
                    'PostCode' => str_replace(" ","",$this->settings['postalcode']),
                    'CountryCode' => $this->settings['country'],
                );

                $DestinationAddress = array(
                    'StateOrProvinceCode' => $package['destination']['state'],
                    'City' => $package['destination']['city'],
                    'PostCode' => str_replace(" ","",$package['destination']['postcode']),
                    'CountryCode' => $package['destination']['country'],
                );
                $ShipmentDetails = array(
                    'PaymentType' => 'P',
                   // 'PaymentOptions' => 'ACCT',
                    'ProductGroup' => $product_group,
                    'ProductType' => $allowed_method,
                    'Dimensions' => array(
                        'Length' => $dimensions_length,
                        'Width' => $dimensions_width,
                        'Height' => $dimensions_height,
                        'Unit' => $dimensions_unit
                    ),
                    'ActualWeight' => array('Value' => $pkgWeight, 'Unit' => $weightUnit),
                    // 'ChargeableWeight' => array('Value' => $pkgWeight, 'Unit' => $weightUnit),
                    'NumberOfPieces' => $pkgQty,
                    'Items' => [
                        'ShipmentItem' =>  $itemDetails,
                    ],
                );

                //SOAP object
                $soapClient = new SoapClient($info['baseUrl'] . 'aramex-rates-calculator-wsdl.wsdl',
                    array("trace" => true, 'cache_wsdl' => WSDL_CACHE_NONE));
                //$baseCurrencyCode = get_woocommerce_currency();
                $baseCurrencyCode = get_option('woocommerce_currency');
                
                $params = array(
                    'ClientInfo' => $info['clientInfo'],
                    'OriginAddress' => $OriginAddress,
                    'DestinationAddress' => $DestinationAddress,
                    'ShipmentDetails' => $ShipmentDetails,
                    'PreferredCurrencyCode' => $baseCurrencyCode
                );
                if ($allowed_method == "CDA") {
                    $params['ShipmentDetails']['Services'] = "";
                } else {
                    $params['ShipmentDetails']['Services'] = "";
                }

                
               $response = array();
                try {   
                    /*$price = "";*/
                    custom_plugin_log('Before CalculateRate API call in frontend.');
                    custom_plugin_log('CalculateRate API Request in frontend.'.print_r($params, true));

                    $results = $soapClient->CalculateRate($params);

                    custom_plugin_log('CalculateRate API Response in frontend.'.print_r($results, true));

                    $response = array();
                    if ($results->HasErrors) {
                        WC()->session->set('aramex_error', true);
                        if (is_countable($results->Notifications->Notification) && count($results->Notifications->Notification) > 1) {
                            $error = "";
                            foreach ($results->Notifications->Notification as $notify_error) {
                                $error .= 'Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message . "  *******  ";
                            }
                            $response['error'] = $error;
                        } else {
                            if ($results->Notifications->Notification->Code == 'ERR20') {
                                continue;
                            }
                            if ($results->Notifications->Notification->Code == 'ERR61') {
                                continue;
                            }
                            $response['error'] = 'Aramex: ' . $results->Notifications->Notification->Code . ' - ' . $results->Notifications->Notification->Message;
                        }
                        $response['type'] = 'error';
                        $aramex_visit_checkout = WC()->session->get('aramex_visit_checkout');
                        $aramex_set_first_success = WC()->session->get('aramex_set_first_success');
                        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

                        if (!$aramex_visit_checkout && !$aramex_set_first_success) {
                            $aramex_visit_checkout = 1;
                        } else {
                            $aramex_visit_checkout = $aramex_visit_checkout + 1;
                        }

                        if ($aramex_visit_checkout === 1) {
                            $response['type'] = 'error_aramex';
                            WC()->session->set('aramex_visit_checkout', $aramex_visit_checkout);
                        }
                    } else {
                        $aramex_visit_checkout = WC()->session->get('aramex_visit_checkout');
                        if (!$aramex_visit_checkout) {
                            WC()->session->set('aramex_set_first_success', true);
                        }
                        WC()->session->set('aramex_error', false);
                        $response['type'] = 'success';
                        $price = $results->TotalAmount->Value;
                        $curr_code = $results->TotalAmount->CurrencyCode;
                    }
                } catch (Exception $e) {
                    $response['type'] = 'error';
                    $response['error'] = $e->getMessage();
                }

                if(!empty($response)){
                    if (isset($response['type']) && $response['type'] == 'error_aramex') {
                        $message = null;
                        $messageType = "error";
                        if(is_page( 'checkout' ) || is_checkout()){
                            if (!wc_has_notice($message, $messageType)) {
                                wc_add_notice($message, $messageType);
                            }
                        }
                    }
                }
                
                if(!empty($response)){
                    if (isset($response['type']) && $response['type'] == 'error') {
                        $message = $response['error'];
                        $messageType = "error";
                        if(is_page( 'checkout' ) || is_checkout()){
                            if (!wc_has_notice($message, $messageType)) {
                                wc_add_notice($message, $messageType);
                            }
                        }
                    }
                }
                

                if ($product_group == 'DOM') {
                    foreach ($allowed_domestic_methods as $key_dom => $domestic_method) {
                        if ($key_dom == $allowed_method) {
                            $title = $domestic_method;
                            break;
                        }
                    }
                } else {
                    foreach ($allowed_international_methods as $key_int => $international_method) {
                        if ($key_int == $allowed_method) {
                            $title = $international_method;
                            break;
                        }
                    }
                }
                if(!empty($response)){
                    if (isset($response['type']) && $response['type'] == 'error') {
                        $rate = array(
                            'id' => $allowed_method . "_aramex",
                            'label' => "Aramex",
                            'cost' => "",
                            'meta_data' => [
                                'currency' => $curr_code
                            ],
                        );
                        $this->add_rate($rate);
                        break;
                    }
                }
                $title_without = $this->settings['hide_shipping_product1'];
                $title = $title ? "Aramex " . $title : "";
                $price = empty($this->settings['aramex_round']) ? ceil( (float) $price ) : $price;
                $rate = array(
                    'id' => $allowed_method . "_aramex",
                    'label' => empty(trim($title_without)) ? $title : $title_without,
                    'cost' => $price,
                    'meta_data' => [
                        'currency' => $curr_code
                    ],
                );
                $this->add_rate($rate);
            }
        }



        /**
         *  Get total info about Admin
         *
         * @param string $nonce Nonce
         * @return array Total info
         */
        private function getInfo($nonce)
        {
            $baseUrl = $this->getWsdlPath($nonce);
            $clientInfo = $this->getClientInfo($nonce);

            return (array('baseUrl' => $baseUrl, 'clientInfo' => $clientInfo));
        }

        /**
         * Get info about Admin
         *
         * @param string $nonce Nonce
         * @return array
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
         * Get path of WSDl file
         *
         * @return string
         */
        private function getPath()
        {
            return __DIR__ . '/../../wsdl/';
        }

        /**
         * Get Admin settings
         *
         * @param string $nonce Nonce
         * @return mixed|void
         */
        private function getSettings($nonce)
        {
            if (wp_verify_nonce($nonce, 'aramex-shipment-check' . wp_get_current_user()->user_email) == false) {
                echo(__('Invalid form data.'));
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
         * Get path of WSDL file
         *
         * @param string $nonce Nonce
         * @return string Path
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
    }
}
