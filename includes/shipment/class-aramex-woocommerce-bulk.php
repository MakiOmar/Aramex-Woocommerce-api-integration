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
include_once __DIR__ . '../../core/class-mo-aramex-helper.php';

/**
 * Controller for for Bulk functionality
 */
class Aramex_Bulk_Method extends MO_Aramex_Helper
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Debug: Log class instantiation
        if (function_exists('custom_plugin_log')) {
            custom_plugin_log('Aramex_Bulk_Method class instantiated');
        }
    }

    /**
     * Starting method
     *
     * @return mixed|string|void
     */
    public function run()
    {
        // Log the incoming request for debugging
        custom_plugin_log('Bulk shipment request received: ' . print_r($_POST, true));
        custom_plugin_log('Current user ID: ' . get_current_user_id());
        custom_plugin_log('Current user email: ' . wp_get_current_user()->user_email);
        
        $post_out = $this->formatPost($_POST);
        custom_plugin_log('Formatted POST data: ' . print_r($post_out, true));
        
        // Check if we have the required data
        if (!isset($post_out['selectedOrders']) || empty($post_out['selectedOrders'])) {
            custom_plugin_log('No orders selected for bulk shipment');
            echo json_encode(array('message' => '<p class="aramex_red">' . __('No orders selected', 'aramex') . '</p>'));
            die();
        }
        
        // Verify nonce for AJAX request
        $nonce_action = 'aramex-shipment-nonce' . wp_get_current_user()->user_email;
        if (!wp_verify_nonce($_POST['_wpnonce'], $nonce_action) || get_current_user_id() == 0) {
            custom_plugin_log('Invalid nonce or user not logged in. Nonce: ' . ($_POST['_wpnonce'] ?? 'not set') . ', Action: ' . $nonce_action);
            echo json_encode(array('message' => '<p class="aramex_red">' . __('Invalid form data.', 'aramex') . '</p>'));
            die();
        }

        $post = array();
        $params = array();
        $params1 = $post_out['str'];

        $mail = isset($params1['aramex_email_customer'])? $params1['aramex_email_customer']: '' ;
        $orders = array();
        include_once(plugin_dir_path(__FILE__) . '../../includes/shipping/class-mo-aramex-shipping-method.php');
        $settings = new MO_Aramex_Shipping_Method();
        
        // Check if settings are properly loaded
        if (!isset($settings->settings) || empty($settings->settings)) {
            custom_plugin_log('Aramex settings not properly loaded');
            echo json_encode(array('message' => '<p class="aramex_red">' . __('Aramex settings not configured properly', 'aramex') . '</p>'));
            die();
        }
        
        $post['aramex_shipment_shipper_country'] = $settings->settings['country'];
        //check "pending" status
        try {
            if (count($post_out["selectedOrders"])) {
            foreach ($post_out["selectedOrders"] as $key => $order_id) {
                $order = wc_get_order($order_id);

                $post_status = $order->get_status();
                if ($post_status == "processing") {
                    $shippingCountry = $order->shipping_country;
                    if ($shippingCountry == $post['aramex_shipment_shipper_country']) {
                        $orders[$key]['method'] = "DOM";
                    } else {
                        $orders[$key]['method'] = "EXP";
                    }
                    $orders[$key]['order_id'] = $order_id;
                } else {
                    $responce = "<p class='aramex_red'>" . __('Select orders with processing status, please', 'aramex'). "</p>";
                    echo json_encode(array('message' => $responce));
                    die();
                }
            }



            //domestic metods must be first
            $dom = array();
            $exp = array();
            foreach ($orders as $key => $order_item) {
                if ($order_item['method'] == 'DOM') {
                    $dom[$key]['method'] = "DOM";
                    $dom[$key]['order_id'] = $order_item['order_id'];
                } else {
                    $exp[$key]['method'] = "EXP";
                    $exp[$key]['order_id'] = $order_item['order_id'];
                }
            }
            $orders = array();
            $total = count($dom) + count($exp);
            for ($i = 0; $i < $total; $i++) {
                foreach ($dom as $key => $item) {
                    $orders[$key]['method'] = "DOM";
                    $orders[$key]['order_id'] = $item['order_id'];
                }
                foreach ($exp as $key => $item) {
                    $orders[$key]['method'] = "EXP";
                    $orders[$key]['order_id'] = $item['order_id'];
                }
            }
        }
                    
        //domestic metods must be first
        if (count($orders)) {
            $responce = "";
            foreach ($orders as $key => $orderItem) {
                $major_par = null;
                $params = null;
                $order = null;
                $post['aramex_shipment_original_reference'] = (int)$orderItem['order_id'];
                $order = wc_get_order($orderItem['order_id']);
                $descriptionOfGoods = "";
                //calculating total weight of current order
                $totalWeight = 0;
                $weight = 0;
                $itemsv = $order->get_items();
                $itemDetails = array();
                $PiecesDimensions = array();
                foreach ($itemsv as $itemvv) {
                    if ($itemvv['qty'] > 0) {
                        $product = wc_get_product($itemvv['product_id']);
                        if (!$product->is_virtual()) {
                            $productData =  $product->get_data();
                            if( $product->is_type( 'simple' ) ){
                                // a simple product
                                $weight = $productData['weight'];
                              } elseif( $product->is_type( 'variation' ) || $product->is_type( 'variable' ) ){
                                // a variable product
                                if(empty($productData['weight'])){
									$variation_product_id=$itemvv->get_variation_id();
									$variation_product=wc_get_product($variation_product_id);
									$weight=$variation_product->get_weight();
                                    if(wc_get_product($product->get_parent_id())){
                                        $parent_weight = $product->get_parent_data();
                                        $weight =  $parent_weight['weight'];
                                    }
                                }else{
                                    $weight = $productData['weight'];
                                }
                              }

                            $totalWeight += (float)$weight * $itemvv['qty'];
                            $descriptionOfGoods .= $itemvv['product_id'] . ' - ' . trim($itemvv['name']) . ' : ';
                            $qty = $itemvv['qty'];

                            $product_id = $itemvv['product_id'];

                            // Get product length
                            $product_length = get_post_meta($product_id, '_length', true);

                            // Get product width
                            $product_width = get_post_meta($product_id, '_width', true);

                            // Get product height
                            $product_height = get_post_meta($product_id, '_height', true);
                            // Get product unit (default is 'cm' for centimeters)
                            $product_unit = get_post_meta($product_id, '_length_unit', true);

                            // If the product unit is not set, get the default unit from WooCommerce settings
                            if (empty($product_unit)) {
                                $product_unit = get_option('woocommerce_dimension_unit');
                            }
                            $product_price = 0;
                            $product_regular_price = get_post_meta($product_id, '_regular_price', true);
                            $product_sale_price = get_post_meta($product_id, '_sale_price', true);
                            if(!empty($product_sale_price)){
                                $product_price = $product_sale_price;
                            }else{
                                $product_price = $product_regular_price;
                            }
                            $CurrencyCode = get_woocommerce_currency();
                            $product_weight = get_post_meta($product_id, '_weight', true);
							if(
								(empty($product_weight) && $product->is_type('variation') && !empty($weight))
								||
								(empty($product_weight) && $product->is_type('variable') && !empty($weight))
							){
								$product_weight=$weight;
							}
                            // Get product weight unit (default is 'kg' for kilograms)
                            $product_weight_unit = get_option('woocommerce_weight_unit');
                           
                            $attribute_value = $product->get_attribute('hscode');
                            if(empty($attribute_value )){
                                $attribute_value = "";
                            }
                            array_push($itemDetails,[
                                'Quantity' =>  $itemvv['qty'],
                                'Weight' => [
                                    'Value' => $product_weight,
                                    'Unit' => $product_weight_unit
                                ],
                                'CommodityCode' => $attribute_value,
                                'GoodsDescription' => trim($itemvv['name']),
                                'CustomsValue' => [
                                    'Value' => $product_price,
                                    'CurrencyCode' => $CurrencyCode
                                ],
                                   
                            ]);

                            $ActualWeight = $itemvv['qty'] * $product_weight;
                            array_push($PiecesDimensions,[
                                'Length' => $product_length,
                                'Width' => $product_width,
                                'Height' => $product_height,
                                'ActualWeight' => array(
                                    'Value' => $ActualWeight,
                                    'Unit' => $product_weight_unit
                                ),
                             //   'NumberOfPieces' => $itemvv['qty'],
                                'Unit' => $product_unit,
                            ]);
                        }
                    }
                }



/*
                foreach ($itemsv as $itemvv) {
                    if ($itemvv['qty'] > 0) {
                        $product = wc_get_product($itemvv['product_id']);
                        $weight = $product->weight * $itemvv['qty'];
                        $descriptionOfGoods .= $itemvv['product_id'] . ' - ' . trim($itemvv['name']) . ' : ';
                        $totalWeight += $weight;
                        $qty = $itemvv['qty'];
                    }
                }
*/


                if ($orderItem['method'] == 'DOM') {
                    $aramex_shipment_info_product_type = ($params1['aramex_shipment_info_product_type_dom']) ? $params1['aramex_shipment_info_product_type_dom'] : "";
                    $aramex_shipment_info_payment_type = ($params1['aramex_shipment_info_payment_type_dom']) ? $params1['aramex_shipment_info_payment_type_dom'] : "";
                    $aramex_shipment_info_payment_option = "";
                    $aramex_shipment_info_service_type = ($params1['aramex_shipment_info_service_type_dom']) ? $params1['aramex_shipment_info_service_type_dom'] : "";
                    $aramex_shipment_currency_code = ($params1['aramex_shipment_currency_code_dom']) ? $params1['aramex_shipment_currency_code_dom'] : "";
                    $aramex_shipment_info_custom_amount = "";
                } else {
                    $aramex_shipment_info_product_type = ($params1['aramex_shipment_info_product_type']) ? $params1['aramex_shipment_info_product_type'] : "";
                    $aramex_shipment_info_payment_type = ($params1['aramex_shipment_info_payment_type']) ? $params1['aramex_shipment_info_payment_type'] : "";
                    $aramex_shipment_info_payment_option = ($params1['aramex_shipment_info_payment_option']) ? $params1['aramex_shipment_info_payment_option'] : "";
                    $aramex_shipment_info_service_type = ($params1['aramex_shipment_info_service_type']) ? $params1['aramex_shipment_info_service_type'] : "";
                    $aramex_shipment_currency_code = ($params1['aramex_shipment_currency_code']) ? $params1['aramex_shipment_currency_code'] : "";
                    $aramex_shipment_info_custom_amount = ($params1['aramex_shipment_info_custom_amount']) ? $params1['aramex_shipment_info_custom_amount'] : "";
                }

                $company_name = isset($order->billing_country) ? $order->billing_company : '';
                if ($company_name == "") {
                    $company_name = $order->shipping_company;
                }
                if ($company_name == "") {
                    $company_name = $order->shipping_first_name . " " . $order->shipping_last_name;
                }
                
                $option = get_option('woocommerce_aramex_settings');
                $ioss_num = isset($option['ioss_num']) ? $option['ioss_num'] : '';

                $AdditionalPropertyDetails = array();      
                if (!empty($ioss_num)){
                        array_push($AdditionalPropertyDetails,[
                            'CategoryName' => "CustomsClearance",
                            'Name' => "IOSS",
                            'Value' => $ioss_num
                        ]);
                }

                $taxidvat = isset($option['taxidvat']) ? $option['taxidvat'] : '';
                if (!empty($taxidvat)){
                        array_push($AdditionalPropertyDetails,[
                            'CategoryName' => "CustomsClearance",
                            'Name' => "ShipperTaxIdVATEINNumber",
                            'Value' => $taxidvat
                        ]);
                }

                //shipper parameters
                $params['Shipper'] = array(
                    'Reference1' => (string)$order->id,
                    'Reference2' => '',
                    'AccountNumber' => (string)$settings->settings['account_number'],
                    //Party Address
                    'PartyAddress' => array(
                        'Line1' => addslashes($settings->settings['address']),
                        'Line2' => '',
                        'Line3' => '',
                        'City' => $settings->settings['city'],
                        'StateOrProvinceCode' => $settings->settings['state'],
                        'PostCode' => str_replace(" ","",$settings->settings['postalcode']),
                        'CountryCode' => $settings->settings['country'],
                    ),
                    //Contact Info
                    'Contact' => array(
                        'Department' => '',
                        'PersonName' => $settings->settings['name'],
                        'Title' => '',
                        'CompanyName' => $settings->settings['company'],
                        'PhoneNumber1' => $settings->settings['phone'],
                        'PhoneNumber1Ext' => '',
                        'PhoneNumber2' => '',
                        'PhoneNumber2Ext' => '',
                        'FaxNumber' => '',
                        'CellPhone' => $settings->settings['phone'],
                        'EmailAddress' => $settings->settings['email_origin'],
                        'Type' => ''
                    ),
                );

                //consinee parameters
                $params['Consignee'] = array(
                    'Reference1' => (string)$order->id,
                    'Reference2' => '',
                    'AccountNumber' => "",
                    //Party Address
                    'PartyAddress' => array(
                        'Line1' => ($order->shipping_address_1) ? $order->shipping_address_1 . " " . $order->shipping_address_2 : '',
                        'Line2' => '',
                        'Line3' => '',
                        'City' => ($order->shipping_city) ? $order->shipping_city : '',
                        'StateOrProvinceCode' => '',
                        'PostCode' => str_replace(" ","",($order->shipping_postcode) ? $order->shipping_postcode : ''),
                        'CountryCode' => ($order->shipping_country) ? $order->shipping_country : '',
                    ),
                    //Contact Info
                    'Contact' => array(
                        'Department' => '',
                        'PersonName' => ($order->shipping_first_name) ? $order->shipping_first_name . " " . $order->shipping_last_name : '',
                        'Title' => '',
                        'CompanyName' => $company_name,
                        'PhoneNumber1' => ($order->billing_phone) ? $order->billing_phone : '',
                        'PhoneNumber1Ext' => '',
                        'PhoneNumber2' => '',
                        'PhoneNumber2Ext' => '',
                        'FaxNumber' => '',
                        'CellPhone' => ($order->billing_phone) ? $order->billing_phone : '',
                        'EmailAddress' => ($order->billing_email) ? $order->billing_email : '',
                        'Type' => ''
                    )
                );
                //new
                if ($aramex_shipment_info_payment_type == 3) {
                    $params['ThirdParty'] = array(
                        'Reference1' => (string)$order->id, //'ref11111',
                        'Reference2' => '',
                        'AccountNumber' => (string)$settings->settings['account_number'],
                        'AccountPin' => (string)$settings->settings['account_pin'],
                        //Party Address
                        'PartyAddress' => array(
                            'Line1' => ($order->shipping_address_1) ? $order->shipping_address_1 . " " . $order->shipping_address_2 : '',
                            'Line2' => '',
                            'Line3' => '',
                            'City' => ($order->shipping_city) ? $order->shipping_city : '',
                            'StateOrProvinceCode' => '',
                            'PostCode' => str_replace(" ","",($order->shipping_postcode) ? $order->shipping_postcode : ''),
                            'CountryCode' => ($order->shipping_country) ? $order->shipping_country : '',
                        ),
                        //Contact Info
                        'Contact' => array(
                            'Department' => '',
                            'PersonName' => ($order->shipping_first_name) ? $order->shipping_first_name . " " . $order->shipping_last_name : '',
                            'Title' => '',
                            'CompanyName' => $company_name,
                            'PhoneNumber1' => ($order->billing_phone) ? $order->billing_phone : '',
                            'PhoneNumber1Ext' => '',
                            'PhoneNumber2' => '',
                            'PhoneNumber2Ext' => '',
                            'FaxNumber' => '',
                            'CellPhone' => ($order->billing_phone) ? $order->billing_phone : '',
                            'EmailAddress' => ($order->billing_email) ? $order->billing_email : '',
                            'Type' => ''
                        ),
                    );
                }

                // To covert the weight unit from 'lbs' to 'lb'
                $weightUnit = get_option('woocommerce_weight_unit');
                if ($weightUnit == 'lbs'){
                    $weightUnit = 'lb';
                }

                // Other Main Shipment Parameters
                $params['Reference1'] = (string)$order->id;
                $params['Reference2'] = '';
                $params['Reference3'] = '';
                $params['ForeignHAWB'] = '';

                $params['TransportType'] = 0;
                $params['ShippingDateTime'] = time(); //date('m/d/Y g:i:sA');
                $params['DueDate'] = time() + (7 * 24 * 60 * 60); //date('m/d/Y g:i:sA');
                $params['PickupLocation'] = 'Reception';
                $params['PickupGUID'] = '';
                $params['Comments'] = '';
                $params['AccountingInstrcutions'] = '';
                $params['OperationsInstructions'] = '';
                $params['Details'] = array(
                    'Dimensions' => array(
                        'Length' => '0',
                        'Width' => '0',
                        'Height' => '0',
                        'Unit' => 'cm'
                    ),
                    'PieceDimensions' => [
                        'Dimensions' => $PiecesDimensions
                    ],
                    'ActualWeight' => array(
                        'Value' => (string)$totalWeight,
                        'Unit' => $weightUnit
                    ),
                    'ProductGroup' => $orderItem['method'],
                    'ProductType' => $aramex_shipment_info_product_type,
                    'PaymentType' => $aramex_shipment_info_payment_type,
                    'PaymentOptions' => $aramex_shipment_info_payment_option,
                    'Services' => $aramex_shipment_info_service_type,
                    'NumberOfPieces' => $qty,
                    'DescriptionOfGoods' => $descriptionOfGoods,
                    'GoodsOriginCountry' => $settings->settings['country'],
                    'Items' =>  [
                        'ShipmentItem' => $itemDetails
                    ],
                    'AdditionalProperties' => [
                        'AdditionalProperty' => $AdditionalPropertyDetails
                     ]
                );

                 if ($aramex_shipment_info_service_type != null)
                {

                    // $hasCODS= array_search("CODS",$aramex_shipment_info_service_type,false);
                    $hasCODS = "CODS";
                    // if ($hasCODS !== false)
                    if ($hasCODS === $aramex_shipment_info_service_type)
                    {         
                         $params['Details']['CashOnDeliveryAmount'] = array(
                        'Value' => $order->get_total(),
                        'CurrencyCode' => $aramex_shipment_currency_code
                        );
                    }
                    else
                    {
                        $params['Details']['CashOnDeliveryAmount'] = null ;
                    }

                }
                else
                {
                    $params['Details']['CashOnDeliveryAmount'] = null ;       
                }
               
                $params['Details']['CustomsValueAmount'] = array(
                    'Value' => $aramex_shipment_info_custom_amount,
                    'CurrencyCode' => $aramex_shipment_currency_code
                );

                $major_par['Shipments'][] = $params;
                $info = Aramex_Helper::getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
                $major_par['ClientInfo'] = $info['clientInfo'];
                $report_id = trim($settings->settings['report_id']);
                if ($report_id == "") {
                    $report_id = 9729;
                }
                $major_par['LabelInfo'] = array(
                    'ReportID' => $report_id,
                    'ReportType' => 'URL'
                );
                $replay = $this->postAction($major_par, $order, $orderItem['method'], $mail);

                if ($replay[0] == "DOM") {
                    $method = "Domestic Product Group";
                } else {
                    $method = "International Product Group";
                };

                if ($replay[1] == "error") {
                    $responce .= "<p class='aramex_red'>" . __('Aramex Shipment Number -',
                            'aramex') . $orderItem['order_id'] . __(' not created',
                            'aramex') . " (" . $method . ")</p>";
                } else {
                    $responce .= "<p class='aramex_green'>" . __('Aramex Shipment Number:',
                            'aramex') . $orderItem['order_id'] . __('has been created',
                            'aramex') . "(" . $method . ")</p>";
                }

            }
            echo json_encode(array('message' => $responce));
           die();
        } else {
            $errors = "<p class='aramex_red'>" . __('No orders with Pending status selected', 'aramex') . "</p>";
            echo json_encode(array('message' => $errors));
            die();
        }
        } catch (Exception $e) {
            custom_plugin_log('Error in bulk shipment: ' . $e->getMessage());
            
            // Log API error
            mo_aramex_log_api_error(
                'CreateShipments (Bulk)',
                $e->getMessage(),
                $e->getCode(),
                array('selected_orders' => $selectedOrders)
            );
            
            echo json_encode(array('message' => '<p class="aramex_red">' . __('Error processing bulk shipment: ', 'aramex') . $e->getMessage() . '</p>'));
            die();
        }
    }

    /**
     * Send request to Aramex server in order to make shipment
     *
     * @param array $major_par Data for Shipment calculation
     * @param object $order Order object
     * @param string $method Shipping method
     * @param string $mail Email
     * @return array Result from Aramex server
     */
    private function postAction($major_par, $order, $method, $mail)
    {
        $shipper_name = $order->shipping_first_name . " " . $order->shipping_last_name;
        $info = Aramex_Helper::getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));

        //SOAP object
     
        $soapClient = new SoapClient($info['baseUrl'] . 'shipping.wsdl', array('soap_version' => SOAP_1_1));
        try {
            //create shipment call
            // Log API call
            $start_time = microtime(true);
            mo_aramex_log_api_call(
                'CreateShipments (Bulk)',
                $major_par,
                'SOAP',
                array('WSDL' => $info['baseUrl'] . 'shipping.wsdl')
            );
            
            $auth_call = $soapClient->CreateShipments($major_par);
            $execution_time = microtime(true) - $start_time;
            
            // Log API response
            mo_aramex_log_api_response(
                'CreateShipments (Bulk)',
                $auth_call,
                200,
                array(),
                $execution_time
            );
            if ($auth_call->HasErrors) {
                if (empty($auth_call->Shipments)) {
                    if (count((array)$auth_call->Notifications->Notification) > 1) {
                        if(is_array($auth_call->Notifications->Notification)){
                            foreach ($auth_call->Notifications->Notification as $notify_error) {
                                aramex_errors()->add('error',
                                __('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message));
                            }
                        }else{
                            aramex_errors()->add('error',
                                __('Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message));
                        }
                    } else {
                        aramex_errors()->add('error',
                            __('Aramex: ' . $auth_call->Notifications->Notification->Code . ' - ' . $auth_call->Notifications->Notification->Message));
                    }
                } else {
                    if (count((array)$auth_call->Shipments->ProcessedShipment->Notifications->Notification) > 1) {
                        $notification_string = '';
                        if(is_array($auth_call->Shipments->ProcessedShipment->Notifications->Notification)){
                            foreach ($auth_call->Shipments->ProcessedShipment->Notifications->Notification as $notification_error) {
                                $notification_string .= $notification_error->Code . ' - ' . $notification_error->Message . ' <br />';
                            }
                        }else{
                            $notification_string .= $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Message . ' <br />';
                        }
                        $this->aramex_errors()->add('error', __($notification_string, 'aramex'));
                    } else {
                        $this->aramex_errors()->add('error',
                            __('Aramex: ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $auth_call->Shipments->ProcessedShipment->Notifications->Notification->Message),
                            'aramex');
                    }
                }
                return array($method, 'error');
            } else {
                $commentdata = array(
                    'comment_post_ID' => $order->get_id(),
                    'comment_author' => '',
                    'comment_author_email' => '',
                    'comment_author_url' => '',
                    'comment_content' => "AWB No. " . $auth_call->Shipments->ProcessedShipment->ID . " - Order No. " . $auth_call->Shipments->ProcessedShipment->Reference1,
                    'comment_type' => 'order_note',
                    'user_id' => "0",
                );

                wp_new_comment($commentdata);
                $order = new WC_Order($order->id);
                $order->add_order_note($commentdata['comment_content']);
                $order->save();
                if (!empty($order)) {
                    $order->update_status('printing-aramex', __('Aramex shipment created.', 'woocommerce'));
                    update_post_meta($order->id, 'aramex_awb_no', $auth_call->Shipments->ProcessedShipment->ID);
                }

                /* sending mail */
                global $woocommerce;
                $mailer = $woocommerce->mailer();
                $supportEmail = $mailer->get_from_address();
                $message_body = sprintf(__('<p>Dear <b>%s</b> </p>', 'aramex'), $shipper_name);
                $message_body .= sprintf(__('<p>Your order is #%s </p>', 'aramex'),
                    $auth_call->Shipments->ProcessedShipment->Reference1);
                $message_body .= sprintf(__('<p>Created Airway bill number: %s </p>', 'aramex'),
                    $auth_call->Shipments->ProcessedShipment->ID);
                $message_body .= __('<p>You can track shipment on <a href="http://www.aramex.com/express/track.aspx">http://www.aramex.com/express/track.aspx</a> </p>',
                    'aramex');
                $message_body .= __('<p>If you have any questions, please feel free to contact us <b>'.$supportEmail.'</b> </p>',
                    'aramex');
                $message = $mailer->wrap_message(
                // Message head and message body.
                    sprintf(__('Aramex shipment #%s created', 'aramex'), $order->get_id()), $message_body);

                if ($mail == 'yes') {
                    // Cliente email
                    $to = array();
                    $to[] = $order->billing_email;
                    $to[] = $info['copyInfo']['copy_to'];
                    $emailsTo = implode(',', $to);
                    if (trim($info['copyInfo']['copy_to']) == "") {
                        $emailsTo = trim($emailsTo, ',');
                    }
                    $mailheader = array();
                    if ($info['copyInfo']['copy_method'] == "1" && trim($info['copyInfo']['copy_to']) != "") {
                        $emails = explode(',', trim($info['copyInfo']['copy_to']));
                        foreach ($emails as $email) {
                            $mailheader[] = 'Bcc: ' . $email;
                        }
                    }
                    if ($info['copyInfo']['copy_method'] == "0" && trim($info['copyInfo']['copy_to']) != "") {
                        $emails = explode(',', trim($info['copyInfo']['copy_to']));
                        foreach ((array)$emails as $email) {
                            $mailheader[] = 'Cc: ' . $email;
                        }
                    }
                    try {
                        $mailer->send($emailsTo,
                            sprintf(__('Aramex shipment #%s created', 'aramex'), $order->get_order_number()),
                            $message, $mailheader);
                    } catch (Exception $ex) {
                        $this->aramex_errors()->add('error', $ex->getMessage());
                    }
                }
                return array($method, 'success');
            }
        } catch (Exception $e) {
            $errors = $e->getMessage();
            return array($method, 'error');
        }
    }

    /**
     * Get errors
     * @return WP_Error  WP Errors
     */
    private function aramex_errors()
    {
        static $wp_error; // Will hold global variable safely
        return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
    }
}