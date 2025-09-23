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
include_once __DIR__ . '/class-aramex-woocommerce-serchautocities_model.php';

/**
 * Class Aramex_Serchautocities_Method is a controller for Serchautocities functionality
 */
class Aramex_Serchautocities_Method extends Aramex_Helper
{
    /**
     * Model object
     * @var object Aramex_Serchautocities_Method_Model
     */
    private $model;

    /**
     * Aramex_Serchautocities_Method constructor
     */
    public function __construct()
    {
        $this->model = new Aramex_Serchautocities_Method_Model();
    }

    /**
     * Starting method
     *
     * @return array Result from Aramex server
     */
    public function run()
    {
        if (isset($_GET['backend'])) {
            check_ajax_referer('aramex-shipment-check' . wp_get_current_user()->user_email);
        } else {
            check_ajax_referer('serchautocities', 'security');
        }
        $get = $this->formatPost($_GET);
        $countryCode = $get['country_code'];
        $term = $get['term'];
        $cities = $this->model->fetchCities($countryCode, $term);
        //if (count($cities) > 0 && $cities != false) {
        if ($cities != false) {
            if (is_array($cities) && count($cities) > 0) {
                $cities = array_unique($cities);
            } else {
                $cities_temp = $cities;
                $cities = array();
                $cities[] = $cities_temp;
            }
            $sortCities = array();
            foreach ($cities as $v) {
                $sortCities[] = ucwords(strtolower($v));
            }
            asort($sortCities, SORT_STRING);
            $to_return = array();
            foreach ($sortCities as $val) {
                $to_return[] = $val;
            }
            echo json_encode($to_return);
            die();
        } else {
            echo json_encode(array());
            die();
        }
    }
}