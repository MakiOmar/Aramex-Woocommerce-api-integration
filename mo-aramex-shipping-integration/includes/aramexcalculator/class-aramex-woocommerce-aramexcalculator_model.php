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
 * Class Aramex_Aramecalculator_Method_Model is a model for Aramecalculator functionality
 */
class Aramex_Aramecalculator_Method_Model extends Aramex_Helper
{
    /**
     * Aramex rate calculator
     * 
     * @param array $post Address
     * @return array Rates of Aramex methods
     */
    public function rateCalculator($post)
    {
        $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        $destinationCountry = isset($post['country_code'])? $post['country_code']: "";
        $destinationCity = isset($post['city'])?$post['city']:"" ;
        $destinationZipcode = isset($post['post_code'])?$post['post_code']:"";
        $productId = $post['product_id'];
        $product = new WC_Product($productId);
        $weight = $product->get_weight();
        $currency = $post['currency'];
        $form_fields = include(__DIR__ . '../../shipping/data-aramex-settings.php');
        $settings = get_option('woocommerce_aramex_settings');
        $allowed_methods = array();
        $international_methods = array();
        $domestic_methods = array();
        if ($info['clientInfo']['country'] == $destinationCountry) {
            $product_group = 'DOM';
            $domestic_methods = $form_fields['allowed_domestic_methods']['options'];
            foreach ($domestic_methods as $cod => $title) {
                if ($settings['allowed_domestic_methods'] != "") {
                    foreach ($settings['allowed_domestic_methods'] as $value) {
                        if ($value == $cod) {
                            $allowed_methods[$cod] = $title;
                        }
                    }
                }
            }
        } else {
            $product_group = 'EXP';
            $allowed_methods = array();
            $international_methods = $form_fields['allowed_international_methods']['options'];
            foreach ($international_methods as $cod => $title) {
                if ($settings['allowed_international_methods'] != "") {
                    foreach ($settings['allowed_international_methods'] as $value) {
                        if ($value == $cod) {
                            $allowed_methods[$cod] = $title;
                        }
                    }
                }
            }
        }
        $itemDetails = array();
        $product_data = $product->get_data();
        $product_name = $product_data['name'];
        $product_weight = $product_data['weight'];
        $product_length = $product_data['length'];
        $product_width = $product_data['width'];
        $product_price = 0;
        $product_height = $product_data['height'];
        $regular_price = $product_data['regular_price'];
        $sale_price = $product_data['sale_price'];
        $product_weight_unit = get_option('woocommerce_weight_unit');
        $CurrencyCode = get_woocommerce_currency();
        $product_id = $product_data['id'];
        $product_unit = get_option('woocommerce_dimension_unit');
        $current_product_quantity = isset($post['current_product_quantity'])?$post['current_product_quantity']:"" ;
    
        if(!empty($sale_price)){
            $product_price = $sale_price;
        }else{
            $product_price = $regular_price;
        }

        array_push($itemDetails,[
            'Quantity' =>  $current_product_quantity,
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
                    'Unit' => $product_unit
                ),
            ],
               
        ]);

        $response = array();
        $OriginAddress = array(
            'StateOrProvinceCode' => $info['clientInfo']['state'],
            'City' => $info['clientInfo']['city'],
            'PostCode' => str_replace(" ","",$info['clientInfo']['postalcode']),
            'CountryCode' => $info['clientInfo']['country'],
        );
        $DestinationAddress = array(
            'StateOrProvinceCode' => "",
            'City' => $destinationCity,
            'PostCode' => str_replace(" ","",$destinationZipcode),
            'CountryCode' => $destinationCountry,
        );
        $ShipmentDetails = array(
            'PaymentType' => 'P',
            'ProductGroup' => $product_group,
            'ProductType' => '',
            'Dimensions' => array(
                'Length' => $product_length,
                'Width' => $product_width,
                'Height' => $product_height,
                'Unit' => $product_unit
            ),
            'ActualWeight' => array('Value' => $weight, 'Unit' => get_option('woocommerce_weight_unit')),
            // 'ChargeableWeight' => array('Value' => $weight, 'Unit' => get_option('woocommerce_weight_unit')),
            'NumberOfPieces' => 1,
            'Items' => [
                'ShipmentItem' =>  $itemDetails,
            ],
        );

        $params = array(
            'ClientInfo' => $info['clientInfo'],
            'OriginAddress' => $OriginAddress,
            'DestinationAddress' => $DestinationAddress,
            'ShipmentDetails' => $ShipmentDetails,
            'PreferredCurrencyCode' => $currency
        );

        //SOAP object
        $soapClient = new SoapClient($info['baseUrl'] . 'aramex-rates-calculator-wsdl.wsdl');
        $priceArr = array();
        foreach ($allowed_methods as $m_value => $m_title) {
            $params['ShipmentDetails']['ProductType'] = $m_value;
            if ($m_value == "CDA") {
                $params['ShipmentDetails']['Services'] = "";
            } else {
                $params['ShipmentDetails']['Services'] = "";
            }
            try {
                $results = $soapClient->CalculateRate($params);
                if ($results->HasErrors) {
                    if (count($results->Notifications->Notification) > 1) {
                        foreach ($results->Notifications->Notification as $notify_error) {
                            $priceArr[$m_value] = ('Aramex: ' . $notify_error->Code . ' - ' . $notify_error->Message) . ' ';
                        }
                    } else {
                        $priceArr[$m_value] = ('Aramex: ' . $results->Notifications->Notification->Code . ' - ' . $results->Notifications->Notification->Message) . ' ';
                    }
                    $response['type'] = 'error';
                } else {
                    $response['type'] = 'success';
                    $priceArr[$m_value] = array(
                        'label' => $m_title,
                        'amount' => $results->TotalAmount->Value,
                        'currency' => $results->TotalAmount->CurrencyCode
                    );
                }
            } catch (Exception $e) {
                $response['type'] = 'error';
                $priceArr[$m_value] = $e->getMessage();
            }
        }

        print json_encode($priceArr);
        die();
    }
}
