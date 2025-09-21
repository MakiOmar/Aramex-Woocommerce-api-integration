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

if (!function_exists('aramex_admin_notice_permission')){
    function aramex_admin_notice_permission(){
        
        if(isset($_POST['save'])){
            $ioss_num1 = $_POST['woocommerce_aramex_ioss_num'];
            if(isset($ioss_num1) && !empty($ioss_num1)){
                if(preg_match('/^[imIM]{2}[0-9]{10}+$/', $ioss_num1)){
                    $option_group = get_option('woocommerce_aramex_settings');
                    $option_group['ioss_num'] = $ioss_num1;
                    update_option('woocommerce_aramex_settings', $option_group);
                    
                }else{
    ?>
                    <div class="error notice is-dismissible">
                            <p><?php _e( 'Invalid IOSS Format, it should start with [IM] with 10 Digits', 'aramex' ); ?></p>
                    </div> 
    <?php
                    $option_group = get_option('woocommerce_aramex_settings');
                    $option_group['ioss_num'] = "";
                    update_option('woocommerce_aramex_settings', $option_group);
                }
            }
        }

        
        if(isset($_POST['save'])){
            $taxidvat1 = $_POST['woocommerce_aramex_taxidvat'];
            if(isset($taxidvat1) && !empty($taxidvat1)){
                $option_group = get_option('woocommerce_aramex_settings');
                $option_group['taxidvat'] = $taxidvat1;
                update_option('woocommerce_aramex_settings', $option_group);
            }else{
                $option_group = get_option('woocommerce_aramex_settings');
                $option_group['taxidvat'] = "";
                update_option('woocommerce_aramex_settings', $option_group);
            }
        }
    }
}
add_action('admin_notices', 'aramex_admin_notice_permission');

return array(
    'enabled' => array(
        'title' => __('Enable', 'aramex'),
        'type' => 'checkbox',
        'description' => __('Enable Aramex shipping', 'aramex'),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'aramex'),
        'type' => 'text',
        'description' => __('Title to be display on site', 'aramex'),
        'default' => __('Aramex Shipping', 'aramex')
    ),

    'freight' => array(
        'title' => __('Client information', 'aramex'),
        'type' => 'title',
    ),
    'user_name' => array(
        'title' => __('* Email', 'aramex'),
        'type' => 'text',
    ),
    'password' => array(
        'title' => __('* Password', 'aramex'),
        'type' => 'password',
    ),
    'account_pin' => array(
        'title' => __('* Account Pin', 'aramex'),
        'type' => 'text',
    ),
    'account_number' => array(
        'title' => __('* Account Number', 'aramex'),
        'type' => 'text',
    ),
    'account_entity' => array(
        'title' => __('* Account Entity', 'aramex'),
        'type' => 'text',
    ),
    'account_country_code' => array(
        'title' => __('* Account Country Code', 'aramex'),
        'type' => 'text',
    ),
    'allowed_cod' => array(
        'title' => __('COD Account', 'aramex'),
        'type' => 'select',
        'description' => __('Optional account data', 'aramex'),
        'options' => array(
            '0' => __('No', 'aramex'),
            '1' => __('Yes', 'aramex'),
        )
    ),
    'cod_account_number' => array(
        'title' => __('COD Account Number', 'aramex'),
        'type' => 'text',
        'description' => __('Optional account data', 'aramex'),
    ),
    'cod_account_pin' => array(
        'title' => __('COD Account Pin', 'aramex'),
        'type' => 'text',
        'description' => __('Optional account data', 'aramex'),
    ),
    'cod_account_entity' => array(
        'title' => __('COD Account Entity', 'aramex'),
        'type' => 'text',
        'description' => __('Optional account data', 'aramex'),
    ),
    'cod_account_country_code' => array(
        'title' => __('COD Account Country Code', 'aramex'),
        'type' => 'text',
        'description' => __('Optional account data', 'aramex'),
    ),
    'freight1' => array(
        'title' => __('Service Configuration', 'aramex'),
        'type' => 'title',
    ),
    'sandbox_flag' => array(
        'title' => __('Test Mode', 'aramex'),
        'type' => 'select',
        'options' => array(
            '1' => __('Yes', 'aramex'),
            '0' => __('No', 'aramex'),
        )
    ),
    'report_id' => array(
        'title' => __('Report ID', 'aramex'),
        'type' => 'text',
    ),
    'allowed_domestic_methods' => array(
        'title' => __('Allowed Domestic Methods', 'aramex'),
        'type' => 'multiselect',
        'css' => 'width: 350px; height: 150px;',
        'options' => array(
            'BLK' => __('Special: Bulk Mail Delivery', 'aramex'),
            'BLT' => __('Domestic - Bullet Delivery', 'aramex'),
            'CDA' => __('Special Delivery', 'aramex'),
            'CDS' => __('E-commerce/Special Credit Card Delivery', 'aramex'),
            'CGO' => __('Air Cargo (India)', 'aramex'),
            'COM' => __('Special: Cheque Collection', 'aramex'),
            'DEC' => __('Special: Invoice Delivery', 'aramex'),
            'EMD' => __('Early Morning delivery', 'aramex'),
            'FIX' => __('Special: Bank Branches Run', 'aramex'),
            'LGS' => __('Logistic Shipment', 'aramex'),
            'OND' => __('Overnight (Document)', 'aramex'),
            'ONP' => __('Overnight (Parcel)', 'aramex'),
            'P24' => __('Road Freight 24 hours service', 'aramex'),
            'P48' => __('Road Freight 48 hours service', 'aramex'),
            'PEC' => __('Economy Delivery', 'aramex'),
            'PEX' => __('Road Express', 'aramex'),
            'SFC' => __('Surface  Cargo (India)', 'aramex'),
            'SMD' => __('Same Day (Document)', 'aramex'),
            'SMP' => __('Same Day (Parcel)', 'aramex'),
            'SDD' => __('Same Day Delivery', 'aramex'),
            'HVY' => __('Heavy (20kgs and more)', 'aramex'),
            'SPD' => __('Special: Legal Branches Mail Service', 'aramex'),
            'SPL' => __('Special : Legal Notifications Delivery', 'aramex'),
            'LEX' => __('LEX product type', 'aramex'),
        )
    ),
    'allowed_domestic_additional_services' => array(
        'title' => __('Allowed Domestic Additional Services', 'aramex'),
        'type' => 'multiselect',
        'css' => 'width: 350px; height: 150px;',
        'options' => array(
            'AM10' => __('Morning delivery', 'aramex'),
            'CHST' => __('Chain Stores Delivery', 'aramex'),
            'CODS' => __('Cash On Delivery Service', 'aramex'),
            'COMM' => __('Commercial', 'aramex'),
            'CRDT' => __('Credit Card', 'aramex'),
            'DDP' => __('DDP - Delivery Duty Paid - For European Use', 'aramex'),
            'DDU' => __('DDU - Delivery Duty Unpaid - For the European Freight', 'aramex'),
            'EXW' => __('Not An Aramex Customer - For European Freight', 'aramex'),
            'INSR' => __('Insurance', 'aramex'),
            'RTRN' => __('Return', 'aramex'),
            'SPCL' => __('Special Services', 'aramex'),
            'ABX' => __('ABX', 'aramex'),
        )
    ),
    'allowed_international_methods' => array(
        'title' => __('Allowed International Methods', 'aramex'),
        'type' => 'multiselect',
        'css' => 'width: 350px; height: 150px;',
        'options' => array(
            'DPX' => __('Value Express Parcels', 'aramex'),
            'EDX' => __('Economy Document Express', 'aramex'),
            'EPX' => __('Economy Parcel Express', 'aramex'),
            'GDX' => __('Ground Document Express', 'aramex'),
            'GPX' => __('Ground Parcel Express', 'aramex'),
            'IBD' => __('International defered', 'aramex'),
            'PDX' => __('Priority Document Express', 'aramex'),
            'PLX' => __('Priority Letter Express (<.5 kg Docs)', 'aramex'),
            'PPX' => __('Priority Parcel Express', 'aramex'),
            'ABX' => __('ABX', 'aramex'),
            'PXP' => __('Premium Express', 'aramex'),
            'DGX' => __('Dangerous Goods Express', 'aramex'),
            'DGG' => __('Dangerous Goods Ground', 'aramex'),
        )
    ),
    'allowed_international_additional_services' => array(
        'title' => __('Allowed International Additional Services', 'aramex'),
        'type' => 'multiselect',
        'css' => 'width: 350px; height: 150px;',
        'options' => array(
            'AM10' => __('Morning delivery', 'aramex'),
            'CODS' => __('Cash On Delivery', 'aramex'),
            'CSTM' => __('CSTM', 'aramex'),
            'FDAC' => __('FDAC', 'aramex'),
            'FRDM' => __('FRDM', 'aramex'),
            'INSR' => __('Insurance', 'aramex'),
            'NOON' => __('Noon Delivery', 'aramex'),
            'ODDS' => __('Over Size', 'aramex'),
            'RTRN' => __('RTRN', 'aramex'),
            'SIGR' => __('Signature Required', 'aramex'),
            'SPCL' => __('Special Services', 'aramex')

        )
    ),
    'freight2' => array(
        'title' => __('Shipper Details', 'aramex'),
        'type' => 'title',
    ),
    'name' => array(
        'title' => __('Name', 'aramex'),
        'type' => 'text',
    ),
    'email_origin' => array(
        'title' => __('Email', 'aramex'),
        'type' => 'text',
    ),
    'company' => array(
        'title' => __('Company', 'aramex'),
        'type' => 'text',
    ),
    'address' => array(
        'title' => __('Address', 'aramex'),
        'type' => 'text',
    ),
    'country' => array(
        'title' => __('* Country Code', 'aramex'),
        'type' => 'text',
    ),
    'city' => array(
        'title' => __('* City', 'aramex'),
        'type' => 'text',
    ),
    'postalcode' => array(
        'title' => __('* Postal Code', 'aramex'),
        'type' => 'text',
    ),
    'state' => array(
        'title' => __('State', 'aramex'),
        'type' => 'text',
    ),
    'phone' => array(
        'title' => __('Phone', 'aramex'),
        'type' => 'text',
    ),
    'ioss_num' => array(
        'title' => __('IOSS Information', 'aramex'),
        'type' => 'text',
    ),
    'taxidvat' => array(
        'title' => __('Tax ID/VAT/EIN Number', 'aramex'),
        'type' => 'text',
    ),
    'freight3' => array(
        'title' => __('Shipment Email Template', 'aramex'),
        'type' => 'title',
    ),
    'copy_to' => array(
        'title' => __('Shipment Email Copy To', 'aramex'),
        'type' => 'text',
    ),
    'copy_method' => array(
        'title' => __('Shipment Email Copy Method', 'aramex'),
        'type' => 'select',
        'options' => array(
            '1' => __('BBC', 'aramex'),
            '0' => __('Separate Email', 'aramex'),
        )
    ),
    'freight4' => array(
        'title' => __('Api Location Validator', 'aramex'),
        'type' => 'title',
    ),
    'apilocationvalidator_active' => array(
        'title' => __('Enabled', 'aramex'),
        'type' => 'select',
        'options' => array(
            '0' => __('No', 'aramex'),
            '1' => __('Yes', 'aramex'),
        )
    ),
    'freight5' => array(
        'title' => __('Front End Calculator', 'aramex'),
        'type' => 'title',
    ),
    'aramexcalculator' => array(
        'title' => __('Enabled', 'aramex'),
        'type' => 'select',
        'options' => array(
            '0' => __('No', 'aramex'),
            '1' => __('Yes', 'aramex'),
        )
    ),
    'freight6' => array(
        'title' => __('Name of Aramex shipping method on Checkout page', 'aramex'),
        'type' => 'title',
    ),
    'hide_shipping_product1' => array(
        'title' => __('Name', 'aramex'),
        'type' => 'text',
        'description' => __('Name to be display on Checkout page', 'aramex'),
        'default' => __('Aramex', 'aramex')
    ),
    'freight65' => array(
        'title' => __('Aramex rate to be round off to next higher value on Checkout Page', 'aramex'),
        'type' => 'title',
    ),
    'aramex_round' => array(
        'title' => __('Enabled', 'aramex'),
        'type' => 'select',
        'options' => array(
            '1' => __('No', 'aramex'),
            '0' => __('Yes', 'aramex'),
        )
    ),
    'freight7' => array(
        'title' => __('Rate calculator on Checkout page', 'aramex'),
        'type' => 'title',
    ),
    'rate_calculator_checkout_page' => array(
        'title' => __('Enabled', 'aramex'),
        'type' => 'select',
        'options' => array(
            '1' => __('Yes', 'aramex'),
            '0' => __('No', 'aramex'),
        )
    ),
     'freight8' => array(
        'title' => __('Rate calculator on Checkout page only for International shipments', 'aramex'),
        'type' => 'title',
    ),
    'rate_calculator_checkout_page_only_for_international' => array(
        'title' => __('Enabled', 'aramex'),
        'type' => 'select',
        'options' => array(
            '0' => __('No', 'aramex'),
            '1' => __('Yes', 'aramex'),
        )
    ),
);
