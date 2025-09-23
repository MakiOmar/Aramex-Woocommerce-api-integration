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
 * Class Aramex_Serchautocities_Method_Model is a model for Applyvalidation functionality
 */
class Aramex_Serchautocities_Method_Model extends Aramex_Helper
{
    /**
     * Get cities
     *
     * @param string $CountryCode Name of country
     * @param null|string $NameStartsWith Name of city
     * @return array List of cities
     */
    public function fetchCities($CountryCode, $NameStartsWith = null)
    {
        $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        $params = array(
            'ClientInfo' => $info['clientInfo'],
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
            'CountryCode' => $CountryCode,
            'State' => null,
            'NameStartsWith' => $NameStartsWith,
        );

        //SOAP object
        $soapClient = new SoapClient($info['baseUrl'] . 'Location-API-WSDL.wsdl', array('soap_version' => SOAP_1_1, 'cache_wsdl'=>WSDL_CACHE_NONE));
        try {
            $results = $soapClient->FetchCities($params);
            if (is_object($results)) {
                if (!$results->HasErrors) {
                    $cities = isset($results->Cities->string) ? $results->Cities->string : false;
                    return $cities;
                }
            }
        } catch (SoapFault $fault) {
            die('Error : ' . $fault->faultstring);
        }
    }

    /**
     * Validate address
     *
     * @param array $address Address
     * @return array Result from Aramex server
     */
    public function validateAddress($address)
    {
        $info = $this->getInfo(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email));
        $params = array(
            'ClientInfo' => $info['clientInfo'],
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '002',
                'Reference3' => '003',
                'Reference4' => '004',
                'Reference5' => '005'
            ),
            'Address' => array(
                'Line1' => '001',
                'Line2' => '',
                'Line3' => '',
                'City' => $address['city'],
                'StateOrProvinceCode' => '',
                'PostCode' => str_replace(" ","", $address['post_code']),
                'CountryCode' => $address['country_code']
            )
        );

        //SOAP object
        $soapClient = new SoapClient($info['baseUrl'] . 'Location-API-WSDL.wsdl', array('soap_version' => SOAP_1_1, 'cache_wsdl'=>WSDL_CACHE_NONE));
        $reponse = array();
        try {
            $results = $soapClient->ValidateAddress($params);
            if (is_object($results)) {
                if ($results->HasErrors) {
                    $suggestedAddresses = (isset($results->SuggestedAddresses->Address)) ? $results->SuggestedAddresses->Address : "";
                    $message = $results->Notifications->Notification->Message;
                    $reponse = array(
                        'is_valid' => false,
                        'suggestedAddresses' => $suggestedAddresses,
                        'message' => $message
                    );
                } else {
                    $reponse = array('is_valid' => true);
                }
            }
        } catch (SoapFault $fault) {
            die('Error : ' . $fault->faultstring);
        }
        return $reponse;
    }
}
