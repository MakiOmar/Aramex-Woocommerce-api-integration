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
 * Class Aramex_Applyvalidation_Method is a controller for Applyvalidation functionality
 */
class Aramex_Applyvalidation_Method extends Aramex_Helper
{
    /**
     * Model object
     * @var Aramex_Serchautocities_Method_Model
     */
    private $model;

    /**
     * Aramex_Applyvalidation_Method constructor
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
        check_ajax_referer('serchautocities', 'security');
        $address = array();
        $post = $this->formatPost($_POST);
        if (!isset($post['city'])) {
            $post['city'] = "";
        }
        if (!isset($post['post_code'])) {
            $post['post_code'] = "";
        }
        $address['city'] = $post['city'];
        $address['post_code'] = $post['post_code'];
        $address['country_code'] = $post['country_code'];
        $result = $this->model->validateAddress($address);
        if (count($result) > 0 && $result != false) {
            echo json_encode($result);
            die();
        } else {
            echo json_encode(array());
            die();
        }
    }
}
