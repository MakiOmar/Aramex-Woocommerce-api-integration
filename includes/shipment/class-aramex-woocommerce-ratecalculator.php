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

include_once __DIR__ . '../../core/class-aramex-helper.php';

/**
 * Controller for Rate Calculator functionality
 */
class Aramex_Ratecalculator_Method extends Aramex_Helper
{

    /**
     * Starting method
     *
     * @return mixed|string|void
     */
    public function run()
    {
        check_admin_referer('aramex-shipment-check' . wp_get_current_user()->user_email);
        $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        $postArray = $this->formatPost($_POST);
        $post = $postArray['data'];
        $account = $info['clientInfo']['AccountNumber'];
        $response = array();
        
        try {
            $country_code = $info['clientInfo']['country'];
            $count_object = new WC_Countries();
            $countries = $count_object->get_countries();
            foreach ($countries as $key => $value) {
                if ($key == $country_code) {
                    $countryName = $value;
                }
            }
            
            $order_id = $post['rate_calc_order_id'];
            $order = wc_get_order($order_id);
            $itemsv = $order->get_items();
            $itemDetails = array();
            $dimensions_lengths = array();
            $dimensions_widths = array();
            $dimensions_height = 0;
            $dimensions_unit = '';
            foreach ($itemsv as $itemvv) {
                if ($itemvv['product_id'] > 0) {
                    $product_id = $itemvv['product_id'];

                    // Get product length
                    $product_length = get_post_meta($product_id, '_length', true);

                    // Get product width
                    $product_width = get_post_meta($product_id, '_width', true);

                    // Get product height
                    $product_height = (float) get_post_meta($product_id, '_height', true);
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

                    // Get product weight unit (default is 'kg' for kilograms)
                    $product_weight_unit = get_option('woocommerce_weight_unit');

                    $dimensions_height += $product_height;
                    $dimensions_unit = $product_unit;
                    array_push($dimensions_widths,$product_width);
                    array_push($dimensions_lengths,$product_length);
                    array_push($itemDetails,[
                        'Quantity' =>  $itemvv['qty'],
                        'Weight' => [
                            'Value' => $product_weight,
                            'Unit' => $product_weight_unit
                        ],
                        'GoodsDescription' => trim($itemvv['name']),
                        'CustomsValue' => [
                            'Value' => $product_price,
                            'CurrencyCode' => $CurrencyCode
                        ],
                        'PiecesDimensions' => [
                            'Dimensions' =>  array(
                                'Length' => $product_length,
                                'Width' => $product_width,
                                'Height' => $product_height,
                                'Unit' => $product_unit
                            ),
                        ],
                           
                    ]);
                }
            }

            $countryName = ($countryName) ? $countryName : "";
            $dimensions_length = max($dimensions_lengths);
            $dimensions_width = max($dimensions_widths);
            $params = array(
                'ClientInfo' => $info['clientInfo'],
                'Transaction' => array(
                    'Reference1' => $post['reference']
                ),
                'OriginAddress' => array(
                    'StateOrProvinceCode' => html_entity_decode($post['origin_state']),
                    'City' => html_entity_decode($post['origin_city']),
                    'PostCode' => str_replace(" ","",$post['origin_zipcode']),
                    'CountryCode' => $post['origin_country']
                ),
                'DestinationAddress' => array(
                    'StateOrProvinceCode' => html_entity_decode($post['destination_state']),
                    'City' => html_entity_decode($post['destination_city']),
                    'PostCode' => str_replace(" ","", $post['destination_zipcode']),
                    'CountryCode' => $post['destination_country'],
                ),
                'ShipmentDetails' => array(
                    'PaymentType' => $post['payment_type'],
                    'ProductGroup' => $post['product_group'],
                    'ProductType' => $post['service_type'],
                    'Dimensions' => array(
                        'Length' => $dimensions_length,
                        'Width' => $dimensions_width,
                        'Height' => $dimensions_height,
                        'Unit' => $dimensions_unit
                    ),
                    'ActualWeight' => array('Value' => $post['text_weight'], 'Unit' => $post['weight_unit']),
                    // 'ChargeableWeight' => array('Value' => $post['text_weight'], 'Unit' => $post['weight_unit']),
                    'NumberOfPieces' => $post['total_count'],
                    'Items' => [
                        'ShipmentItem' =>  $itemDetails,
                    ],
                    'InsuranceAmount' =>  array(
                        'Value' => $post['insurance_amount'],
                        'CurrencyCode' =>$post['currency_code'],
                    )
                ),
                'PreferredCurrencyCode' => $post['currency_code'],
               
            );
            //SOAP object

            $soapClient = new SoapClient($info['baseUrl'] . 'aramex-rates-calculator-wsdl.wsdl',
                array("trace" => true, 'cache_wsdl' => WSDL_CACHE_NONE));
            try {

                custom_plugin_log('Before CalculateRate API call in backend.');
                custom_plugin_log('CalculateRate API Request in backend.'.print_r($params, true));

                $results = $soapClient->CalculateRate($params);

                custom_plugin_log('CalculateRate API Response in backend.'.print_r($results, true));

                if ($results->HasErrors) {
                    if (count((array)$results->Notifications->Notification) > 1) {
                        $error = "";
                        if(is_array($results->Notifications->Notification)){
                            foreach ($results->Notifications->Notification as $notify_error) {
                                $error .= 'Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message . "<br>";
                            }
                        }else{
                            $error .= 'Aramex: ' . $results->Notifications->Notification->Code . ' - ' . $results->Notifications->Notification->Message . "<br>";
                        }
                        $response['error'] = $error;
                    } else {
                        $response['error'] = 'Aramex: ' . $results->Notifications->Notification->Code . ' - ' . $results->Notifications->Notification->Message;
                    }
                    $response['type'] = 'error';
                } else {
                    $response['type'] = 'success';
                    $amount = "<p class='amount'>" . $results->TotalAmount->Value . " " . $results->TotalAmount->CurrencyCode . "</p>";
                    $text = __('Local taxes - if any - are not included. Rate is based on account number ',
                            'aramex') . $account . __(" in ", 'aramex') . $countryName;
                    $response['html'] = $amount . $text;
                }
            } catch (Exception $e) {
                $response['type'] = 'error';
                $response['error'] = $e->getMessage();
            }
        } catch (Exception $e) {
            $response['type'] = 'error';
            $response['error'] = $e->getMessage();
        }
        print json_encode($response);
        die();
    }
}
