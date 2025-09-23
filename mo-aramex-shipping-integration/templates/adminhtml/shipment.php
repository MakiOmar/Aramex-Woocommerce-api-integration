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
?>
<?php
        /**
         *  Render "Shipment" form
         *
         * @param $order object Order object
         * @return string Template
         */
function aramex_display_order_data_in_admin($order)
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']
    ) {
        return false;
    }

    $order_id = $order->get_id();
    remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));


    $history = get_comments(array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'approve' => 'approve',
        'type' => 'order_note',
    ));
    add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'));

    $history_list = array();
    if (count($history) > 0) {
        foreach ($history as $shipment) {
            $history_list[] = $shipment->comment_content;
        }
    }

    $shipped = false;
    if (count($history_list)) {
        foreach ($history_list as $val) {
            if (strpos($val, "- Order No") !== false) {
                $shipped = true;
                break;
            }
        }
    }
    $aramex_return_button = false;

    if (count($history_list)) {
        foreach ($history_list as $history) {
            $pos = strpos($history, 'Return');
            if ($pos) {
                $aramex_return_button = true;
                break;
            }
            $awbno = strstr($history, "- Order No", true);
            $awbno = trim($awbno, "AWB No.");
            if ($awbno != "") {
                $aramex_return_button = true;
                break;
            }
        }
    } ?>
<!-- Aramex shipment -->
    <div style="clear:both; padding-top:10px;">
        <a class=' button-primary ' style="margin-top:15px; margin-left:15px; display:none; "
           id="create_aramex_shipment"><?php echo esc_html__('Prepare Aramex Shipment', 'aramex'); ?> </a>
        <?php if ($shipped === true) {
        ?>
            <a class=' button-primary ' style="margin-top:15px; margin-left:15px; display:none;"
               id="track_aramex_shipment"><?php echo esc_html__('Track Aramex Shipment', 'aramex'); ?> </a>
        <?php 
    } ?>
        <?php if ($aramex_return_button === true) {
        ?>
            <a class='button-primary' style="margin-top:15px; margin-left:15px; display:none;"
               id="print_aramex_shipment" ><?php echo esc_html__('Print Label', 'aramex'); ?> </a>
        <?php 
    } ?>
    </div>

    <?php
    if (!session_id()) {
        session_start();
    }
    $session = false;
    if (isset($_SESSION['form_data'])) {
        $session = true;
    }

    $countryCollection = WC()->countries->countries;
    //calculating total weight of current order
    $totalWeight = 0;
    $weight = 0;
    $itemsv = $order->get_items();
    if (count($itemsv) > 0) {
        foreach ($itemsv as $itemvv) {
            if ($itemvv['product_id'] > 0) {
                $product = $order->get_product_from_item($itemvv);
                if (!$product->is_virtual()) {
                    $productData =  $product->get_data();
                    if( $product->is_type( 'simple' ) ){
                        // a simple product
                        $weight = $productData['weight'];
                      } elseif( $product->is_type( 'variation' ) || $product->is_type( 'variable' ) ){
                        // a variable product
                        if(empty($productData['weight'])){
                            if(wc_get_product($product->get_parent_id())){
                                $parent_weight = $product->get_parent_data();
                                $weight =  $parent_weight['weight'];
                            }
                        }else{
                            $weight = $productData['weight'];
                        }
                      }
                    $totalWeight += (float)$weight * $itemvv['qty'];
                }
            }
        }
    }
    
    include_once(plugin_dir_path(__FILE__) . '../../includes/shipping/class-aramex-woocommerce-shipping.php');
    $settings = new Aramex_Shipping_Method();
    $account = $settings->settings['account_number'];
    $account_pin = $settings->settings['account_pin'];
    $cod_account_number = $settings->settings['cod_account_number'];
    $cod_account_pin = $settings->settings['cod_account_pin'];
    $name = $settings->settings['name'];
    $email = $settings->settings['email_origin'];
    $company = $settings->settings['company'];
    $address = $settings->settings['address'];
    $country = $settings->settings['country'];
    $city = $settings->settings['city'];
    $postalcode = $settings->settings['postalcode'];
    $state = $settings->settings['state'];
    $phone = $settings->settings['phone'];

    $option = get_option('woocommerce_aramex_settings');
    
    $ioss_num = "";
    if(isset($option['ioss_num'])){
        $ioss_num = $option['ioss_num'];
    }
    if(isset($_SESSION['form_data']['aramex_shipment_ioss_number'])){
        $ioss_num = $_SESSION['form_data']['aramex_shipment_ioss_number'];
    }

    $taxidvat = "";
    if(isset($option['taxidvat'])){
        $taxidvat = $option['taxidvat'];
    }
    if(isset($_SESSION['form_data']['aramex_shipment_shipper_taxidvat'])){
        $taxidvat = $_SESSION['form_data']['aramex_shipment_shipper_taxidvat'];
    }

    $taxidvatreceiver = '';
    if(isset($_SESSION['form_data']['aramex_shipment_receiver_taxidvat'])){
        $taxidvatreceiver = $_SESSION['form_data']['aramex_shipment_receiver_taxidvat'];
    }
    


    $currentUrl = home_url(add_query_arg(null, null));
    $allowed_domestic_methods_all = $settings->form_fields['allowed_domestic_methods']['options'];
    $allowed_domestic_methods = array();
    if(isset($settings->settings['allowed_domestic_methods']) && !empty($settings->settings['allowed_domestic_methods'])){
        foreach ($settings->settings['allowed_domestic_methods'] as $domestic_method) {
            $allowed_domestic_methods[$domestic_method] = $allowed_domestic_methods_all[$domestic_method];
        }
    }
    
    $allowed_international_methods_all = $settings->form_fields['allowed_international_methods']['options'];
    $allowed_international_methods = array();
    if(isset($settings->settings['allowed_international_methods']) && !empty($settings->settings['allowed_international_methods'])){
        foreach ($settings->settings['allowed_international_methods'] as $international_method) {
            $allowed_international_methods[$international_method] = $allowed_international_methods_all[$international_method];
        }
    }
    
    $allowed_domestic_additional_services = $settings->form_fields['allowed_domestic_additional_services']['options'];
    $allowed_domestic_additional_service = array();
    if(isset($settings->settings['allowed_domestic_additional_services']) && !empty($settings->settings['allowed_domestic_additional_services'])){
        foreach ($settings->settings['allowed_domestic_additional_services'] as $domestic_additional_services) {
            $allowed_domestic_additional_service[$domestic_additional_services] = $allowed_domestic_additional_services[$domestic_additional_services];
        }
    }
    
    $allowed_international_additional_services = $settings->form_fields['allowed_international_additional_services']['options'];
    $allowed_international_additional_service = array();
    if(isset($settings->settings['allowed_international_additional_services']) && !empty($settings->settings['allowed_international_additional_services'])){
        foreach ($settings->settings['allowed_international_additional_services'] as $international_additional_services) {
            $allowed_international_additional_service[$international_additional_services] = $allowed_international_additional_services[$international_additional_services];
        }
    }
     
    $allowed_cod = $settings->settings['allowed_cod'];
    $unit = get_option('woocommerce_weight_unit');
    $dom = $settings->settings['allowed_domestic_methods'];
    $exp = $settings->settings['allowed_international_methods'];
    $phone_reciver = "";
    $email_reciver = "";
    $data = $order->get_data();
    if (count($data['meta_data']) > 0) {
        foreach ($data['meta_data'] as $item) {
            if ($item->key == "_shipping_phone") {
                $phone_reciver = $item->value;
            }
            if ($item->key == "_shipping_email") {
                $email_reciver = $item->value;
            }
        }
    }
    $phone_reciver = isset($phone_reciver) ? $phone_reciver : $order->billing_phone;
    $email_reciver = isset($email_reciver) ? $email_reciver : $order->billing_email;
    $payment_method = $data['payment_method'];
    $payment_method_title = $data['payment_method_title']; ?>

    <script>
        jQuery.noConflict();
        (function ($) {
            myObj = {
                printLabelUrl: '',
                wh: $(window).height(),
                ww: $(window).width(),
                shipperCountry: '',
                shipperCity: '',
                shipperZip: '',
                shipperState: '',
                recieverCountry: '',
                recieverCity: '',
                recieverZip: '',
                recieverState: '',
                openWindow: function (param1, param2) {
                    $(param1).css({'visibility': 'hidden', 'display': 'block'});

                    var h = $(param2).height();
                    var w = $(param2).width();
                    var wh = this.wh;
                    var ww = this.ww;
                    if (h >= wh) {
                        h = wh - 20;
                        $(param2).css({'height': (h - 30)});
                    } else {
                        h = h + 30;
                    }

                    var t = wh - h;
                    t = t / 2;
                    var l = ww - w
                    l = l / 2;
                    $('.back-over').fadeIn(200);
                    $(param1).css({
                        'visibility': 'visible',
                        'display': 'none',
                        'height': 'auto',
                        'top': 30 + 'px'
                    }).fadeIn(500);

                },
                openCalc: function () {
                    this.cropValues();
                    this.openWindow('.cal-rate-part', '.cal-form');
                },
                defaultVal: function () {
                    this.shipperCountry = $('aramex_shipment_shipper_country').value;
                    this.shipperCity = $('aramex_shipment_shipper_city').value;
                    this.shipperZip = $('aramex_shipment_shipper_postal').value;
                    this.shipperState = $('aramex_shipment_shipper_state').value;

                    this.recieverCountry = $('aramex_shipment_receiver_country').value;
                    this.recieverCity = $('aramex_shipment_receiver_city').value;
                    this.recieverZip = $('aramex_shipment_receiver_postal').value;
                    this.recieverState = $('aramex_shipment_receiver_state').value;
                },
                cropValues: function () {
                    this.defaultVal();
                    var orginCountry = this.getId('origin_country');
                    this.setSelectedValue(orginCountry, this.shipperCountry);
                    $('origin_city').value = this.shipperCity;
                    $('origin_zipcode').value = this.shipperZip;
                    $('origin_state').value = this.shipperState;

                    var desCountry = this.getId('destination_country');
                    this.setSelectedValue(desCountry, this.recieverCountry);
                    $('destination_city').value = this.recieverCity;
                    $('destination_zipcode').value = this.recieverZip;
                    $('destination_state').value = this.recieverState;
                },
                getId: function (id) {
                    return document.getElementById(id);
                },
                setSelectedValue: function (selectObj, valueToSet) {
                    for (var i = 0; i < selectObj.options.length; i++) {
                        if (selectObj.options[i].value == valueToSet) {
                            selectObj.options[i].selected = true;
                            return;
                        }
                    }
                },
                openPickup: function () {
                    this.defaultVal();
                    var pickupCountry = this.getId('pickup_country');
                    this.setSelectedValue(pickupCountry, this.shipperCountry);
                    $('pickup_city').value = this.shipperCity;
                    $('pickup_zip').value = this.shipperZip;
                    $('pickup_state').value = this.shipperState;
                    $('pickup_address').value = $('aramex_shipment_shipper_street').value;
                    $('pickup_company').value = $('aramex_shipment_shipper_company').value;
                    $('pickup_contact').value = $('aramex_shipment_shipper_name').value;
                    $('pickup_email').value = $('aramex_shipment_shipper_email').value;
                    this.openWindow('.schedule-pickup-part', '.pickup-form');
                },
                close: function () {
                    $('.back-over').fadeOut(500);
                    $('.cal-rate-part, .schedule-pickup-part').fadeOut(200);
                    $('.rate-result').css('display', 'none');
                    $('.pickup-result').css('display', 'none');
                },
                calcRate: function () {
                    $('.aramex_loader').css('display', 'block');
                    $('.rate-result').css('display', 'none');

                  var currentForm = $("#calc-rate-form").serializeArray();
                  var currentFormObject = {};
                  $.each(currentForm,
            function(i, v) {
                currentFormObject[v.name] = v.value;
            });
            var postData = {
			action: 'the_aramex_rate_calculator',
			data:currentFormObject,
            _wpnonce :  currentFormObject['_wpnonce']

		};
               
		jQuery.post(ajaxurl, postData, function(request) {
        var json = jQuery.parseJSON(request);
		if (json.type == 'success') {
            $(".result").html(json.html);
            $('.aramex_loader').css('display', 'none');
        } else {
            var error = "<div class='error'>" + json.error + "</div>";
             $(".result").html(error);
             $('.aramex_loader').css('display', 'none');
            }
         $(".rate-result").show();
		});
                   
                },
                track: function () {
                    
                    $('.aramex_loader').css('display', 'block');
                    $('.track-result').css('display', "none");
                    var currentForm = $("#track-form").serializeArray();
                    var currentFormObject = {};
                    $.each(currentForm,
                    function(i, v) {
                        currentFormObject[v.name] = v.value;
                    });
                      
            var postData = {
			action: 'the_aramex_track',
			data:currentFormObject,
            _wpnonce :  currentFormObject['_wpnonce']

		};
               
		jQuery.post(ajaxurl, postData, function(request) {
        var json = jQuery.parseJSON(request);
                            if (json.type == 'success') {
                                $(".result").html(json.html);
                                $('.aramex_loader').css('display', 'none');
                            } else {
                                var error = "<div class='error'>" + json.error + "</div>";
                                $(".result").html(error);
                                $('.aramex_loader').css('display', 'none');
                            }
                            $(".track-result").show();                       
        });
                       
                },
                schedulePickup: function () {
                    $('.pickup-result').css('display', 'none');
                    $('.aramex_loader').css('display', 'block');
                    var currentForm = $("#pickup-form").serializeArray();
                    var currentFormObject = {};
                    $.each(currentForm,
                    function(i, v) {
                        currentFormObject[v.name] = v.value;
                    });
                      
            var postData = {
			action: 'the_aramex_pickup',
			data : currentFormObject,
            _wpnonce :  currentFormObject['_wpnonce']

		};
               
		jQuery.post(ajaxurl, postData, function(request) {
        var json = jQuery.parseJSON(request);
                            if (json.type == 'success') {
                                $(".pickup-res").html(json.html);
                                $('.aramex_loader').css('display', 'none');
                            } else {
                                var error = "<div class='error'>" + json.error + "</div>";
                                $(".pickup-res").html(error);
                                $('.aramex_loader').css('display', 'none');
                            }
                            $(".pickup-result").show();                    
        });                    
                },
                ajax: function (formId, result1, result2) {
                }
            }
        })(jQuery);
    </script>

    <form></form>
    <div id="aramex_overlay">
        <div id="aramex_shipment_creation">
            <?php

            if (isset($_SESSION['aramex_errors']) && $_SESSION['aramex_errors']->errors > 0) {
                echo '<div class="aramex_errors">';
                // Loop error codes and display errors
                foreach ($_SESSION['aramex_errors']->errors as $key => $error) {
                    if ($key == "error") {
                        foreach ($error as $value) {
                            echo '<span class="error">' . esc_html($value) . '</span><br/>';
                        }
                    } else {
                        foreach ($error as $value) {
                            echo '<span class="success">' . esc_html($value) . '</span><br/>';
                        }
                    }
                }
                echo '</div>';
            } ?>
            <form id="aramex_shipment" method="post"
                  action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                  enctype="multipart/form-data">
                <input type="hidden" name="action" value="the_aramex_shipment"/>
                <input type="hidden" name="aramex_shipment_referer" value="<?php echo esc_attr($currentUrl) ?>"/>
                <input name="_wpnonce" id="aramex-shipment-nonce" type="hidden"
                       value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"/>
                <input name="aramex_shipment_shipper_account" type="hidden" value="<?php echo esc_attr($account); ?>"/>
                <input name="aramex_shipment_shipper_account_pin" type="hidden" value="<?php echo esc_attr($account_pin); ?>"/>
                <input name="aramex_shipment_shipper_account_cod" type="hidden" value="<?php echo esc_attr($cod_account_number); ?>"/>
                <input name="aramex_shipment_shipper_account_pin_cod" type="hidden" value="<?php echo esc_attr($cod_account_pin); ?>"/> 
                
                <input name="aramex_shipment_original_reference" type="hidden"
                       value="<?php echo esc_attr($order_id); ?>"/>
                <FIELDSET class="aramex_shipment_creation_fieldset_big" id="aramex_shipment_creation_general_info">
                    <legend><?php echo esc_html__('Billing Account', 'aramex'); ?> </legend>
                    <div id="general_details" class="aramex_shipment_creation_part">
                        <div class="text_short">
                            <label><?php echo esc_html__('Account', 'aramex'); ?></label>
                            <select class="aramex_all_options" name="aramex_shipment_shipper_account_show">
                                <option value="1"><?php echo esc_html__('Normal Account', 'aramex'); ?></option>
                                <?php if ($allowed_cod == "1") {
                ?>
                                    <option value="2"><?php echo esc_html__('COD Account', 'aramex'); ?></option>
                                <?php 
            } ?>
                            </select>
                            <div class="little_description"><?php echo esc_html__('Taken from Aramex Global Settings',
                                    'aramex'); ?></div>
                            <div class="aramex_clearer"></div>
                        </div>
                        <div id="aramex_shipment_creation_logo">
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment', 'aramex'); ?></label>
                            <select class="aramex_all_options" name="aramex_shipment_info_billing_account"
                                    id="aramex_shipment_info_billing_account_id">
                                <option
                                        value="1">
                                    <?php echo esc_html__('Shipper Account', 'aramex'); ?>
                                </option>
                                <option
                                        value="2">
                                    <?php echo esc_html__('Consignee Account', 'aramex'); ?>
                                </option>
                                <option
                                        value="3">
                                    <?php echo esc_html__('Third Party', 'aramex'); ?>
                                </option>
                            </select>
                            <div id="aramex_shipment_info_service_type_div" style="display: none;"></div>
                        </div>
                        <div class="cal-rate-button" style="float:right;">
                            <button name="aramex_rate_calculate" type="button" id="aramex_rate_calculate"
                                    class='button-primary'
                                    onclick="myObj.openCalc();"><?php echo esc_html__('Calculate Rate', 'aramex'); ?>
                            </button>
                            <button name="aramex_schedule_pickup" type="button" id="aramex_schedule_pickup"
                                    class='button-primary'
                                    onclick="myObj.openPickup();"><?php echo esc_html__('Schedule Pickup', 'aramex'); ?>
                            </button>
                            <?php if ($aramex_return_button === true) {
                                        ?>
                                    <a class='button-primary print_aramex_shipment' 
                                       id="print_aramex_shipment"><?php echo esc_html__('Print Label', 'aramex'); ?> </a>
                                <?php 
                                    } ?>
                        </div>
                </FIELDSET>
                <div id="aramex_messages"></div>
                <!--  Shipper DetailsShipper Details -->
                <FIELDSET class="aramex_shipment_creation_fieldset aramex_shipment_creation_fieldset_left">
                    <legend><?php echo esc_html__('Shipper Details', 'aramex'); ?></legend>
                    <div id="shipper_details" class="aramex_shipment_creation_part">
                        <div class="text_short">
                            <label><?php echo esc_html__('Reference', 'aramex'); ?></label><input class="number"
                                                                                                  type="text"
                                                                                                  name="aramex_shipment_shipper_reference"
                                                                                                  value="<?php echo esc_attr($order_id) ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $name1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_name'] : $name; ?>
                            <label><?php echo esc_html__('Name', 'aramex'); ?> <span class="red">*</span></label><input
                                    type="text" class="required"
                                    id="aramex_shipment_shipper_name"
                                    name="aramex_shipment_shipper_name"
                                    value="<?php echo esc_attr($name1); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $email1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_email'] : $email; ?>
                            <label><?php echo esc_html__('Email', 'aramex'); ?> <span class="red">*</span></label><input
                                    type="text" class="required email"
                                    id="aramex_shipment_shipper_email"
                                    name="aramex_shipment_shipper_email"
                                    value="<?php echo esc_attr($email1); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $company1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_company'] : $company; ?>
                            <label><?php echo esc_html__('Company', 'aramex'); ?></label><input type="text"
                                                                                                id="aramex_shipment_shipper_company"
                                                                                                name="aramex_shipment_shipper_company"
                                                                                                value="<?php echo esc_attr($company1); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $street1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_street'] : $address; ?>
                            <label><?php echo esc_html__('Address', 'aramex'); ?> <span
                                        class="red">*</span></label><textarea rows="4" class="required"
                                                                              cols="26" type="text"
                                                                              id="aramex_shipment_shipper_street"
                                                                              name="aramex_shipment_shipper_street"><?php echo esc_textarea($street1); ?></textarea>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Country', 'aramex'); ?> <span class="red">*</span></label>
                            <select class="aramex_countries validate-select" id="aramex_shipment_shipper_country"
                                    name="aramex_shipment_shipper_country">
                                <?php $country1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_country'] : $country; ?>
                                <?php 
                                    if (count($countryCollection) > 0) {
                                        foreach ($countryCollection as $key => $value) {
                                            ?>
                                        <option value="<?php echo esc_attr($key) ?>" <?php
                                        if ($country1) {
                                            echo ($country1 == $key) ? 'selected="selected"' : '';
                                        } ?> ><?php echo esc_html($value) ?></option>
                                <?php 
                                        } 
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <?php $city1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_city'] : $city; ?>
                            <label><?php echo esc_html__('City', 'aramex'); ?> <span
                                        class="red no-display">*</span></label><input class="aramex_city"
                                                                                      autocomplete="off"
                                                                                      type="text"
                                                                                      id="aramex_shipment_shipper_city"
                                                                                      name="aramex_shipment_shipper_city"
                                                                                      value="<?php echo esc_attr($city1); ?>"/>

                        </div>
                        <div class="text_short">
                            <?php $postalcode1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_postal'] : $postalcode; ?>
                            <label><?php echo esc_html__('Postal Code', 'aramex'); ?> <span
                                        class="red no-display">*</span></label><input class="" type="text"
                                                                                      id="aramex_shipment_shipper_postal"
                                                                                      name="aramex_shipment_shipper_postal"
                                                                                      value="<?php echo esc_attr($postalcode1); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $state1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_state'] : $state; ?>
                            <label><?php echo esc_html__('State', 'aramex'); ?></label><input type="text"
                                                                                              id="aramex_shipment_shipper_state"
                                                                                              name="aramex_shipment_shipper_state"
                                                                                              value="<?php echo esc_attr($state1); ?>"/>
                        </div>
                        <div class="text_short">
                        <label><?php echo esc_html__('Tax ID/VAT/EIN Number', 'aramex'); ?></label><input type="text"
                                                                          id="aramex_shipment_shipper_taxidvat"
                                                                          name="aramex_shipment_shipper_taxidvat"
                                                                              value="<?php echo esc_attr($taxidvat); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $phone1 = ($session) ? $_SESSION['form_data']['aramex_shipment_shipper_phone'] : $phone; ?>
                            <label><?php echo esc_html__('Phone', 'aramex'); ?></label><input
                                    class="required" type="text" id="aramex_shipment_shipper_phone"
                                    name="aramex_shipment_shipper_phone" value="<?php echo esc_attr($phone1); ?>"/>
                        </div>
                    </div>
                </FIELDSET>
                <!--  Receiver Details -->
                <FIELDSET class="aramex_shipment_creation_fieldset aramex_shipment_creation_fieldset_right">
                    <legend><?php echo esc_html__('Receiver Details', 'aramex'); ?></legend>

                    <div class="text_short">
                        <label><?php echo esc_html__('Reference', 'aramex'); ?></label><input class="number" type="text"
                                                                                              id="aramex_shipment_receiver_reference"
                                                                                              name="aramex_shipment_receiver_reference"
                                                                                              value="<?php echo esc_attr($order_id); ?>"/>
                    </div>
                    <div class="text_short">
                        <?php
                        $name1 = ($order->get_shipping_first_name()) ? $order->get_shipping_first_name() : '';
    $name_last1 = ($order->get_shipping_last_name()) ? $order->get_shipping_last_name() : '';
    $name1 = $name1 . " " . $name_last1; ?>
                        <?php $name1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_name'] : $name1; ?>
                        <label><?php echo esc_html__('Name', 'aramex'); ?>
                            <span class="red">*</span></label><input class="required" type="text"
                                                                     id="aramex_shipment_receiver_name"
                                                                     name="aramex_shipment_receiver_name"
                                                                     value="<?php echo esc_attr($name1); ?>"/>
                    </div>
                    <div class="text_short">
                        <?php $email_reciver = ($order->get_billing_email()) ? $order->get_billing_email() : ''; ?>
                        <?php $email_reciver1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_email'] : $email_reciver; ?>
                        <label><?php echo esc_html__('Email', 'aramex'); ?> <span class="red">*</span></label><input
                                class="email required" type="text"
                                id="aramex_shipment_receiver_email"
                                name="aramex_shipment_receiver_email"
                                value="<?php echo esc_attr($email_reciver1); ?>"/>
                    </div>
                    <div class="text_short">
                        <?php $company_name = ($order->get_shipping_company()) ? $order->get_shipping_company() : ''; ?>
                        <?php $company_name = (empty($company_name)) ? $order->get_shipping_first_name() . " " . $order->get_shipping_last_name() : $company_name; ?>
                        <?php $company_name = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_company'] : $company_name; ?>

                        <label><?php echo esc_html__('Company', 'aramex'); ?></label><input type="text"
                                                                                            id="aramex_shipment_receiver_company"
                                                                                            name="aramex_shipment_receiver_company"
                                                                                            value="<?php echo esc_attr($company_name) ?>"/>
                    </div>
                    <div class="text_short">
                        <?php $street = ($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : ''; ?>
                        <?php $street2 = ($order->get_shipping_address_2()) ? $order->get_shipping_address_2() : ''; ?>
                        <?php $street = $street . " " . $street2; ?>
                        <?php $street = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_street'] : $street; ?>
                        <label><?php echo esc_html__('Address', 'aramex'); ?> <span
                                    class="red">*</span></label><textarea class="required" rows="4"
                                                                          cols="26" type="text"
                                                                          id="aramex_shipment_receiver_street"
                                                                          name="aramex_shipment_receiver_street"><?php echo esc_attr($street); ?></textarea>
                    </div>
                    <div class="text_short">
                        <label><?php echo esc_html__('Country', 'aramex'); ?> <span class="red">*</span></label>
                        <?php $country1 = ($order->get_shipping_country()) ? $order->get_shipping_country() : ''; ?>
                        <?php $country1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_country'] : $country1; ?>
                        <select class="aramex_countries" id="aramex_shipment_receiver_country"
                                name="aramex_shipment_receiver_country">
                            <?php
                                if (count($countryCollection) > 0) {
                                    foreach ($countryCollection as $key => $value) {
                            ?>
                                        <option
                                                value="<?php echo $key ?>" <?php echo ($country1 == $key) ? 'selected="selected"' : ''; ?> ><?php echo esc_html($value); ?></option>
                            <?php
                                    }
                                } ?>
                        </select>
                    </div>
                    <div class="text_short">
                        <?php $city1 = ($order->get_shipping_city()) ? $order->get_shipping_city(): ''; ?>
                        <?php $city1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_city'] : $city1; ?>
                        <label><?php echo esc_html__('City', 'aramex'); ?>
                            <span class="red no-display">*</span></label><input class="aramex_city" autocomplete="off"
                                                                                type="text"
                                                                                id="aramex_shipment_receiver_city"
                                                                                name="aramex_shipment_receiver_city"
                                                                                value="<?php echo $city1; ?>"/>
                        <div id="aramex_shipment_receiver_city_autocomplete" class="am_autocomplete"></div>
                    </div>
                    <div class="text_short">
                        <?php $postcode1 = ($order->get_shipping_postcode()) ? $order->get_shipping_postcode() : ''; ?>
                        <?php $postcode1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_postal'] : $postcode1; ?>
                        <label><?php echo esc_html__('Postal Code', 'aramex'); ?> <span class="red no-display">*</span></label><input
                                type="text" class=""
                                id="aramex_shipment_receiver_postal"
                                name="aramex_shipment_receiver_postal"
                                value="<?php echo esc_attr($postcode1); ?>"/>
                    </div>
                    <div class="text_short">
                        <?php $state1 = ($order->get_shipping_state()) ? $order->get_shipping_state() : ''; ?>
                        <?php $state1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_state'] : $state1; ?>
                        <label><?php echo esc_html__('State', 'aramex'); ?></label><input type="text"
                                                                                          id="aramex_shipment_receiver_state"
                                                                                          name="aramex_shipment_receiver_state"
                                                                                          value="<?php echo esc_attr($state1); ?>"/>
                    </div>
                    <div class="text_short">
                        <label><?php echo esc_html__('Tax ID/VAT/EIN Number', 'aramex'); ?></label><input type="text"
                                                                      id="aramex_shipment_receiver_taxidvat"
                                                                      name="aramex_shipment_receiver_taxidvat"
                                                                      value="<?php echo esc_attr($taxidvatreceiver); ?>"/>
                    </div>
                    <div class="text_short">
                        <?php $phone_reciver1 = ($order->get_billing_phone()) ? $order->get_billing_phone() : ''; ?>
                        <?php $phone_reciver1 = ($session) ? $_SESSION['form_data']['aramex_shipment_receiver_phone'] : $phone_reciver1; ?>
                        <label><?php echo esc_html__('Phone', 'aramex'); ?></label><input class="required" type="text"
                                                                                          id="aramex_shipment_receiver_phone"
                                                                                          name="aramex_shipment_receiver_phone"
                                                                                          value="<?php echo esc_attr($phone_reciver1); ?>"/>
                    </div>
                </FIELDSET>

                <!-- Shipment Information -->
                <div class="aramex_clearer"></div>
                <FIELDSET class="aramex_shipment_creation_fieldset_big">
                    <legend><?php echo esc_html__('Shipment Information', 'aramex'); ?></legend>
                    <div id="shipment_infromation" class="aramex_shipment_creation_part">
                        <div class="text_short">
                            <label><?php echo esc_html__('Total weight:', 'aramex'); ?></label>
                            <?php $totalWeight = ($session) ? $_SESSION['form_data']['order_weight'] : $totalWeight; ?>
                            <input type="text" name="order_weight" value="<?php echo esc_attr($totalWeight); ?>"
                                   class="fl width-60 mar-right-10"/>
                            <select name="weight_unit" class="fl width-60" style="height:24px;padding:0px;">
                                <option value="kg" <?php echo ($unit == 'kg') ? 'selected="selected"' : ''; ?> >
                                    <?php echo "kg" ?>
                                </option>
                                <option value="lb" <?php echo ($unit == 'lbs') ? 'selected="selected"' : ''; ?>>
                                    <?php echo "lbs" ?>
                                </option>
                            </select>
                        </div>
                        <div class="text_short">
                            <?php $order_id = ($session) ? $_SESSION['form_data']['aramex_shipment_info_reference'] : $order_id; ?>
                            <label><?php echo esc_html__('Reference', 'aramex'); ?></label><input type="text"
                                                                                                  id="aramex_shipment_info_reference"
                                                                                                  name="aramex_shipment_info_reference"
                                                                                                  value="<?php echo esc_attr($order_id) ?>"/>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Product Group', 'aramex'); ?></label>
                            <?php $country1 = ($order->get_shipping_country()) ? $order->get_shipping_country() : ''; ?>
                            <?php $checkCountry = ($country1 == $country) ? true : false; ?>
                            <?php
                            if (isset($_SESSION['form_data']['aramex_shipment_info_product_group']) && $_SESSION['form_data']['aramex_shipment_info_product_group'] == "DOM") {
                                $checkCountry = true;
                            }
    if (isset($_SESSION['form_data']['aramex_shipment_info_product_group']) && $_SESSION['form_data']['aramex_shipment_info_product_group'] == "EXP") {
        $checkCountry = false;
    } ?>
                            <select class="aramex_all_options" id="aramex_shipment_info_product_group"
                                    name="aramex_shipment_info_product_group">
                                <option <?php echo ($checkCountry == true) ? 'selected="selected"' : ''; ?>
                                        value="DOM"><?php echo esc_html__('Domestic', 'aramex'); ?>
                                </option>
                                <option <?php echo ($checkCountry == false) ? 'selected="selected"' : ''; ?>
                                        value="EXP"><?php echo esc_html__('International Express', 'aramex'); ?>
                                </option>
                            </select>
                            <div id="aramex_shipment_info_product_group_div" style="display: none;"></div>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Service Type', 'aramex'); ?></label>
                            <select class="aramex_all_options" id="aramex_shipment_info_product_type"
                                    name="aramex_shipment_info_product_type">
                                <?php $dom = ($session) ? $_SESSION['form_data']['aramex_shipment_info_product_type'] : $dom; ?>
                                <?php $exp = ($session) ? $_SESSION['form_data']['aramex_shipment_info_product_type'] : $exp; ?>
                                <?php
                                if (count($allowed_domestic_methods) > 0) {
                                    foreach ($allowed_domestic_methods as $key => $val) {
                                        $selected_str = "";
                                        $selected_str = ($dom == $key) ? 'selected="selected"' : ''; ?>
                                        <option <?php echo $selected_str; ?> value="<?php echo esc_attr($key); ?>"
                                                                             id="<?php echo esc_attr($key); ?>"
                                                                             class="DOM"><?php echo esc_html($val); ?></option>
                                        <?php

                                    }
                                } ?>
                                <?php
                                if (count($allowed_international_methods) > 0) {
                                    foreach ($allowed_international_methods as $key => $val) {
                                        $selected_str = "";
                                        if ($exp == $key) {
                                            $selected_str = 'selected="selected"';
                                        } ?>
                                        <option <?php echo $selected_str; ?> value="<?php echo esc_attr($key); ?>"
                                                                             id="<?php echo esc_attr($key); ?>"
                                                                             class="EXP"><?php echo esc_html($val); ?></option>
                                        <?php

                                    }
                                } ?>
                            </select>
                            <div id="aramex_shipment_info_service_type_div" style="display: none;"></div>
                        </div>
                        <input name="aramex_shipment_info_payment_method" type="hidden" value="<?php echo esc_attr($payment_method); ?>"/>
                        <div class="text_short">
                            <label><?php echo esc_html__('Additional Services', 'aramex'); ?></label>
                            <?php $type1 = $settings->settings['allowed_domestic_additional_services']; ?>
                            <?php $type1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_service_type'] : $type1; ?>
                            <select class="aramex_all_options" id="aramex_shipment_info_service_type"
                                    name="aramex_shipment_info_service_type[]" multiple
                                    style="height:120px;"
                            >
                                <option value=""></option>
                                <?php
                                if (count($allowed_domestic_additional_services) > 0) {
                                    $codExist = array_key_exists('CODS', $allowed_domestic_additional_services);
                                    if($payment_method === 'aramex_smart' && $codExist){
                                        unset($allowed_domestic_additional_services['CODS']); 
                                    }

                                    foreach ($allowed_domestic_additional_services as $key => $val) {
                                        ?>
                                        <option
                                            <?php
                                                $setSelected = false;

                                                // if (is_array($type1)) {
                                                //     $setSelected = (in_array($key, $type1)) ? true : false;
                                                // } else {
                                                //     $setSelected = ($type1 == $key) ? true : false;
                                                // }
                                            
                                            
                                                if(array_key_exists($key,$allowed_domestic_additional_service)){
                                                    $setSelected = true;
                                                }

                                                // if($key == "CODS" && $payment_method == "cod")
                                                // {
                                                //     $setSelected = true;
                                                // }
                                                // else if($key == "CODS" && $payment_method != "cod") {
                                                //     $setSelected = false;
                                                // }
                                                $bg_color = "";
                                                if($setSelected) {
                                                    echo 'selected="selected"';
                                                }
                                                else {
                                                    $bg_color = "background-color: #ffffff;";
                                                    echo '';
                                                }

                                            ?>
                                                value="<?php echo esc_attr($key); ?>"
                                                id="dom_as_<?php echo esc_attr($key); ?>"
                                                
                                                class="DOM local" style="<?php echo esc_html($bg_color); ?>"><?php echo esc_html($val); ?></option>
                                        <?php

                                    }
                                } ?>
                                <?php
                                if (count($allowed_international_additional_services) > 0) {
                                    $codExist = array_key_exists('CODS', $allowed_international_additional_services);
                                    if($payment_method === 'aramex_smart' && $codExist){
                                        unset($allowed_international_additional_services['CODS']); 
                                    }
                                    
                                    foreach ($allowed_international_additional_services as $key => $val) {
                                        ?>
                                        <option
                                            <?php
                                                $setSelected = false;

                                                
                                                  // if (is_array($type1)) {
                                                  //     $setSelected = (in_array($key, $type1)) ? true : false;
                                                  // } else {
                                                  //     $setSelected = ($type1 == $key) ? true : false;
                                                  // }

                                                if(array_key_exists($key,$allowed_international_additional_service)){
                                                    $setSelected = true;
                                                }
                                              
                                                //   if($key == "CODS" && $payment_method == "cod")
                                                //   {
                                                //       $setSelected = true;
                                                //   }
                                                //   else if($key == "CODS" && $payment_method != "cod") {
                                                //       $setSelected = false;
                                                //   }

                                                 $bg_color = "";
                                              if($setSelected) {
                                                  echo 'selected="selected"';
                                              }
                                              else {
                                                  echo '';
                                                  $bg_color = "background-color: #ffffff;";
                                              } 
                                              ?>
                                                value="<?php echo esc_attr($key); ?>"
                                                id="exp_as_<?php echo esc_attr($key); ?>"
                                                class="non-local EXP" style="<?php echo esc_html($bg_color); ?>"><?php echo esc_html($val); ?></option>
                                        <?php

                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment Type', 'aramex'); ?></label>
                            <?php $type1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_payment_type'] : ''; ?>
                            <select class="aramex_all_options" id="aramex_shipment_info_payment_type"
                            name="aramex_shipment_info_payment_type">

                            <option value="P" <?php
                            if ($type1 == 'P') {
                                echo 'selected="selected"';
                            } ?>><?php echo esc_html__('Prepaid', 'aramex'); ?>
                            </option>
                            <option value="C" <?php
                            if ($type1 == 'C') {
                                echo 'selected="selected"';
                            } ?>><?php echo esc_html__('Collect', 'aramex'); ?>
                            </option>
                            <option value="3" <?php
                            if ($type1 == '3') {
                                echo 'selected="selected"';
                            } ?>><?php echo esc_html__('Third Party', 'aramex'); ?>
                            </option>
                            </select>
                            <div id="aramex_shipment_info_service_type_div" style="display: none;"></div>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment Option', 'aramex'); ?></label>
                            <select class="" id="aramex_shipment_info_payment_option"
                                    name="aramex_shipment_info_payment_option">
                                <?php $option1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_payment_option'] : ''; ?>
                                <option value=""></option>
                                <option id="ASCC" value="ASCC" <?php
                                if ($option1 == 'ASCC') {
                                    echo 'selected="selected"';
                                } ?> style="display: none;"><?php echo esc_html__('Needs Shipper Account Number to be
                                    filled', 'aramex'); ?>
                                </option>
                                <option id="ARCC" value="ARCC"  <?php
                                if ($option1 == 'ARCC') {
                                    echo 'selected="selected"';
                                } ?>style="display: none;"><?php echo esc_html__('Needs Consignee Account Number to
                                    be filled', 'aramex'); ?>
                                </option>

                                <option id="CASH" value="CASH" <?php
                                if ($option1 == 'CASH') {
                                    echo 'selected="selected"';
                                } ?>><?php echo esc_html__('Cash', 'aramex'); ?>
                                </option>
                                <option id="ACCT" value="ACCT" <?php
                                if ($option1 == 'ACCT') {
                                    echo 'selected="selected"';
                                } ?>><?php echo esc_html__('Account', 'aramex'); ?>
                                </option>
                                <option id="PPST" value="PPST" <?php
                                if ($option1 == 'PPST') {
                                    echo 'selected="selected"';
                                } ?>><?php echo esc_html__('Prepaid Stock', 'aramex'); ?>
                                </option>
                                <option id="CRDT" value="CRDT" <?php
                                if ($option1 == 'CRDT') {
                                    echo 'selected="selected"';
                                } ?>><?php echo esc_html__('Credit', 'aramex'); ?>
                                </option>
                            </select>

                        </div>
                        <div class="text_short">
                            <?php $amount1 = round($order->get_total(), 2);
                            $amount1 = ($payment_method == "cod") ? $amount1 : ""; ?>
                            <?php $amount1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_cod_amount'] : $amount1; ?>
                            <label><?php echo esc_html__('COD Amount', 'aramex'); ?></label><input class="" type="text"
                                                                                                   id="aramex_shipment_info_cod_amount"
                                                                                                   name="aramex_shipment_info_cod_amount"
                                                                                                   value="<?php echo esc_attr($amount1); ?>"/>
                        </div>
                        <div class="text_short">
                            <?php $amount2 = $custom_hidden_value = round($order->get_total(), 2); ?>
                            <?php 
                            if($checkCountry === true) {
                                $amount2 = ""; 
                                if($payment_method != 'aramex_smart'){
                                    $custom_hidden_value = "";
                                } 
                            }        
                            else if($session)
                            {
                                $amount2 = $_SESSION['form_data']['aramex_shipment_info_custom_amount'];
                            }                            
                            ?>
                            <label><?php echo esc_html__('Custom Amount', 'aramex'); ?></label>
                            <input class="" type="text" id="aramex_shipment_info_custom_amount" 
                            name="aramex_shipment_info_custom_amount" value="<?php echo esc_attr($amount2); ?>"/>
                            <input class="" type="hidden" id="aramex_shipment_info_custom_amount_hidden" 
                            name="aramex_shipment_info_custom_amount_hidden" value="<?php echo esc_attr($custom_hidden_value); ?>"/>                                                                                                    
                        </div>

                       <div class="text_short">
                            <?php $cash_additional_amount1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_cash_additional_amount'] : ""; ?>
                            <label><?php echo esc_html__('Cash Additional Amount ', 'aramex'); ?></label><input type="text"
                                                                                                        class=""
                                                                                                        id="aramex_shipment_info_cash_additional_amount"
                                                                                                        name="aramex_shipment_info_cash_additional_amount"
                                                                                                        value="<?php echo esc_attr($cash_additional_amount1); ?>"/>
                      </div>
                         <div class="text_short">
                            <?php $aramex_insurance_amount2 = ($session) ? $_SESSION['form_data']['insurance_amount'] : ""; ?>
                            <label><?php echo esc_html__('Insurance Amount', 'aramex'); ?></label><input type="text"
                                                                                                        id="insurance_amount"
                                                                                                         name="insurance_amount"
                                                                                                         value="<?php echo esc_attr($aramex_insurance_amount2); ?>"
                                                                                                         />
                            <div style="float: left; padding-left: 5px;"></div>
                        </div>           
                        <?php $custom_currency_hidden = ($session) ? $_SESSION['form_data']['aramex_shipment_currency_code_custom_hidden_item'] : get_woocommerce_currency(); ?>
                        <input class="" type="hidden" id="aramex_shipment_currency_code_custom_hidden_item"
                                                  name="aramex_shipment_currency_code_custom_hidden_item"
                                                  value="<?php echo esc_attr($custom_currency_hidden); ?>"/>               
                        <div class="text_short">
                            <?php $code_currency_code_custom1 = ($session) ? $_SESSION['form_data']['aramex_shipment_currency_code'] : get_woocommerce_currency(); ?>
                            <?php $code_currency_code_custom1 =  ($payment_method == "cod") ? $code_currency_code_custom1 : ""; ?>

                            <?php $code_currency_code_custom1 = ($session) ? $_SESSION['form_data']['aramex_shipment_currency_code'] : $code_currency_code_custom1; ?>
                            <label><?php echo esc_html__('COD Currency', 'aramex'); ?></label>
                            <input type="text" class="" id="aramex_shipment_currency_code"
                            name="aramex_shipment_currency_code" value="<?php echo esc_attr($code_currency_code_custom1); ?>"/>
                            
                        </div>
     
                         <div class="text_short">
                            <?php $code_currency_code_custom2  = $custom_hidden_currency = ($session) ?
                            $_SESSION['form_data']['aramex_shipment_currency_code_custom'] : get_woocommerce_currency(); ?>
                            <?php 
                            if($checkCountry === true) {
                                $code_currency_code_custom2 = ""; 
                                if($payment_method != 'aramex_smart'){
                                    $custom_hidden_currency = "";
                                } 
                            }                         
                            ?>
                            <label><?php echo esc_html__('Customs Currency', 'aramex'); ?></label>
                            <input type="text" class=""  id="aramex_shipment_currency_code_custom"
                            name="aramex_shipment_currency_code_custom" value="<?php echo esc_attr($code_currency_code_custom2); ?>"/>
                            <input type="hidden" class=""  id="aramex_shipment_currency_code_custom_hidden"
                            name="aramex_shipment_currency_code_custom_hidden" value="<?php echo esc_attr($custom_hidden_currency); ?>"/>
                        </div>

                        <div class="text_short">
                            
                            <label><?php echo esc_html__('IOSS Information', 'aramex'); ?></label>
                            <input type="text" class=""  id="aramex_shipment_ioss_number"
                            name="aramex_shipment_ioss_number" maxlength="12" value="<?php echo esc_attr($ioss_num); ?>"/>
                        </div>
                    

                        <div id="indianFields" class="text_short" style="display: none;">
                            <div class="text_short">
                                <label>Shipper Tax Number</label>
                                <input type="text" class="" id="ShipperTaxIdVATEINNumber" name="ShipperTaxIdVATEINNumber"/>
                            </div>
                            <div class="text_short">
                                <label>Consignee Tax Number</label>
                                <input type="text" class="" id="ConsigneeTaxIdVATEINNumber" name="ConsigneeTaxIdVATEINNumber"/>
                            </div>
                            <div class="text_short">
                                <label>Tax Paid</label>
                                <select class="" id="TaxPaid" name="TaxPaid">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div id="taxAmountBlock" class="text_short">
                                <label>Tax Amount</label>
                                <input type="text" class="" id="TaxAmount" name="TaxAmount"/>
                            </div>
                            <div class="text_short">
                                <label>Invoice Number</label>
                                <input type="text" class="" id="InvoiceNumber" name="InvoiceNumber"/>
                            </div>
                            <div class="text_short">
                                <label>Invoice Date</label>
                                <input type="date" class="" id="InvoiceDate" name="InvoiceDate"/>
                            </div>
                            <div class="text_short">
                                <label>Exporter Type</label>
                                <select class="" id="ExporterType" name="ExporterType">
                                    <option value="UT">UT</option>
                                    <option value="Bond">Bond</option>
                                    <option value="Not Applicable">Not Applicable</option>
                                </select>
                            </div>
                        </div>

                        <div class="text_short">
                            <?php $comment1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_comment'] : ""; ?>
                            <label><?php echo esc_html__('Comment', 'aramex'); ?></label><textarea rows="4"
                                                                                                   cols="<?php if (strpos($_SERVER['HTTP_USER_AGENT'],
                                                                                                       'Firefox')) {
        ?>29 <?php 
    } else {
        ?>35<?php 
    } ?>"
                                                                                                                       type="text"
                                                                                                   id="aramex_shipment_info_comment"
                                                                                                   name="aramex_shipment_info_comment"><?php echo esc_attr($comment1); ?></textarea>
                        </div>

                        <div class="text_short">
                            <?php $foreignhawb1 = ($session) ? $_SESSION['form_data']['aramex_shipment_info_foreignhawb'] : ''; ?>
                            <label><?php echo esc_html__('Foreign Shipment No', 'aramex'); ?></label><input class=""
                                                                                                            type="text"
                                                                                                            id="aramex_shipment_info_foreignhawb"
                                                                                                            name="aramex_shipment_info_foreignhawb"
                                                                                                            value="<?php echo esc_attr($foreignhawb1); ?>"/>
                        </div>
                        <div class="text_short">
                            <label for="file1"><?php echo esc_html__('Filename 1:', 'aramex'); ?></label>

                            <div id="file1_div" style="float: left;width: 145px;">
                                <input type="file" name="file1" id="file1" size="7">
                            </div>
                            <div style="float: right;">
                                <input type="button" name="filereset" id="filereset" value="Reset"
                                       style="width: 60px;height: 24px;"/>
                            </div>
                        </div>
                        <div class="text_short">
                            <label for="file2"><?php echo esc_html__('Filename 2:', 'aramex'); ?></label>

                            <div id="file2_div" style="float: left;width: 145px;">
                                <input type="file" name="file2" id="file2" size="7">
                            </div>
                            <div style="float: right;">
                                <input type="button" name="file2reset" id="file2reset" value="Reset"
                                       style="width: 60px;height: 24px;"/>
                            </div>
                        </div>
                        <div class="text_short">
                            <label for="file"><?php echo esc_html__('Filename 3:', 'aramex'); ?></label>

                            <div id="file3_div" style="float: left;width: 145px;">
                                <input type="file" name="file3" id="file3" size="7">
                            </div>
                            <div style="float: right;">
                                <input type="button" name="file3reset" id="file3reset" value="Reset"
                                       style="width: 60px;height: 24px;"/>
                            </div>
                        </div>
                        <?php ?>
                        <div class="text_short">
                            <label><?php echo esc_html__('Description', 'aramex'); ?></label>
                            <textarea rows="4" cols="31" type="text" id="aramex_shipment_description"
                                      name="aramex_shipment_description" ><?php
                                if (count($order->get_items()) > 0) {
                                    foreach ($order->get_items() as $item) {
                                        echo esc_textarea(' : ' . trim($item['name']. ' - ' . trim($item['quantity'] .' pcs. '
                                            )));
                                    }
                                } ?>
                                    </textarea>
                            <div id="aramex_shipment_description_div" style="    float: left;
                                         font-size: 11px;
                                         margin-bottom: 5px;
                                         margin-top: 2px;
                                         width: 202px;">
                            </div>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Items Price', 'aramex'); ?></label><input type="text"
                                                                                                    id="aramex_shipment_info_items_subtotal"
                                                                                                    name="aramex_shipment_info_items_subtotal"
                                                                                                    disabled="disabled"
                                                                                                    style="width: 165px; float: left;"
                                                                                                    value="<?php echo esc_attr($order->get_total()); ?>"/>

                         <div style="float: left; padding-left: 5px;"><?php echo esc_html(get_woocommerce_currency()); ?></div>
                        </div>
                        <div class="text_short">
                            <?php $aramex_number_pieces = ($session) ? $_SESSION['form_data']['number_pieces'] : '1'; ?>
                            <label><?php echo esc_html__('Number of Pieces', 'aramex'); ?></label><input type="text"
                                                                                                         name="number_pieces"
                                                                                                         value="<?php echo esc_attr($aramex_number_pieces); ?>"
                                                                                                         style="width: 165px; float: left;"/>
                            <div style="float: left; padding-left: 5px;"></div>
                        </div>

                    </div>
                    <div id="shipment_infromation2" class="aramex_shipment_creation_part">
                        <div class="text_short" id="aramex_shipment_info_items">
                            <div>
                                <div style="margin-bottom: -15px;"><?php echo esc_html__('Items not shipped yet',
                                        'aramex'); ?></div>
                                <br/>
                                <table id="aramex_items_table">
                                    <tr>
                                        <th class="aramex_item_options"><?php echo esc_html__('Action',
                                                'aramex'); ?></th>
                                        <th class="aramex_item_name"><?php echo esc_html__('Name', 'aramex'); ?></th>
                                        <th class="aramex_item_qty"><?php echo esc_html__('Qty', 'aramex'); ?></th>
                                    </tr>
                                    <?php
                                    $itemDetails = [];
                                    $qty = 0;

                                    foreach ($order->get_items() as $item) {
                                        array_push($itemDetails,$item['product_id']);
                                        $product1 = $order->get_product_from_item($item); ?>
                                        <tr id="item<?php echo $item['product_id'] ?>"
                                            class="aramex_item_tobe_shipped">
                                            <td></td>
                                            <td class="aramex_item_name">
                                                        <span
                                                                title="<?php echo esc_attr($item['name']); ?>"><?php echo esc_html(substr($item['name'],
                                                                0, 21)); ?>
                                                            ...</span>
                                                <input type="hidden"
                                                       id="aramex_items_<?php echo esc_attr($item['product_id']); ?>"
                                                       name="aramex_items[<?php echo esc_attr((int)$item['product_id']); ?>]"
                                                       value="<?php echo esc_attr((int)$item['qty']); ?>"/>
                                            </td>
                                            <td class="aramex_item_qty">
                                                <input class="aramex_input_items_qty" type="text"
                                                       name="p_<?php echo esc_attr($item['product_id']); ?>"
                                                       value="<?php echo esc_attr((int)$item['qty']); ?>"/>
                                                <input type="hidden"
                                                        id="aramex_items_Title_<?php echo esc_attr($item['product_id']); ?>"
                                                        name="aramex_items_Title_<?php echo esc_attr($item['product_id']); ?>"
                                                        value="<?php echo esc_attr($product1->get_name()); ?>"/> 
                                                <input type="hidden"
                                                       id="aramex_items_base_price_<?php echo esc_attr($item['product_id']); ?>"
                                                       name="aramex_items_base_price_<?php echo esc_attr($item['product_id']); ?>"
                                                       value="<?php echo esc_attr($product1->get_price()); ?>"/>
                                                <input type="hidden"
                                                       id="aramex_items_base_weight_<?php echo esc_attr($item['product_id']); ?>"
                                                       name="aramex_items_base_weight_<?php echo esc_attr($item['product_id']); ?>"
                                                       value="<?php echo esc_attr($product1->get_weight()); ?>"/>
                                                <input type="hidden"
                                                       id="aramex_items_total_<?php echo esc_attr($item['product_id']); ?>"
                                                       name="aramex_items_total_<?php echo esc_attr($item['product_id']); ?>"
                                                       value="<?php echo esc_attr((int)$item['qty']); ?>"/>

                                            </td>
                                        </tr>
                                        <?php
                                        $qty = $qty + (int)$item['qty'];
                                        } ?>
                                        <?php $itemNumbers = implode(",",$itemDetails); ?>
                                        <input class="" type="hidden" id="item_details"
                                            name="item_details"
                                            value="<?php echo esc_attr($itemNumbers); ?>"/>
                                    
                                    <tr>
                                        <td colspan="2"
                                            style="font-weight: bold;background: none repeat scroll 0% 0% rgb(224, 224, 224);"><?php echo esc_html__('Number of items to be shipped:',
                                                'aramex'); ?>

                                        </td>
                                        <td>
                                                    <span id="items_tobe_shipped_number">
                                                    <?php echo esc_html($qty); ?>
                                                    </span>
                                        </td>
                                    </tr>
                                </table>

                            </div>
                        </div>
                    </div>
                    <div class="aramex_clearer"></div>
                </FIELDSET>
                <div class="aramex_clearer"></div>
                <div style="float: right;margin-bottom: 20px;margin-top: -11px;">
                    <?php
                    if (isset($_SESSION['form_data'])) {
                        unset($_SESSION['form_data']);
                    }
    if (isset($_SESSION['aramex_errors'])) {
        unset($_SESSION['aramex_errors']);
    }
    if ($aramex_return_button) {
        ?>
                                        <input name="aramex_return_shipment_creation_date" type="hidden" value="return"/>
                                        <button id="aramex_return_shipment_creation_submit_id" type="submit"
                                                name="aramex_return_shipment_creation_submit_id"
                                                class="button-primary"><?php echo esc_html__('Return Order', 'aramex'); ?>
                                        </button>

                                    <?php 
    } else {
        ?>
                        <div style="width: 100%;  padding-top:10px; overflow:hidden;">
                            <div style="float: right;font-size: 11px;margin-bottom: 10px;width: 184px;">
                                <input
                                        style="float: left; width: auto; height:16px; display:block;" type="checkbox"
                                        name="aramex_email_customer" value="yes"/>
                                <span style="float: left; margin-top: -2px;"><?php echo esc_html__('Notify customer by email',
                                        'aramex'); ?></span>
                            </div>
                        </div>
                        <div class="aramex_clearer"></div>
                        <input name="aramex_return_shipment_creation_date" type="hidden" value="create"/>
                        <button id="aramex_shipment_creation_submit_id" type="submit"
                                name="aramex_shipment_creation_submit"
                                class="button-primary"><?php echo esc_html__('Create Shipment', 'aramex'); ?>
                        </button>
                    <?php 
    } ?>
                    <button id="aramex_close" class="button-primary" type="button"><?php echo esc_html__('Close',
                            'aramex'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <script>
        jQuery.noConflict();
        (function ($) {
            var aramex_shipment_shipper_name = document.getElementById('aramex_shipment_shipper_name').value;
            var aramex_shipment_shipper_email = document.getElementById('aramex_shipment_shipper_email').value;
            var aramex_shipment_shipper_company = document.getElementById('aramex_shipment_shipper_company').value;
            var aramex_shipment_shipper_street = document.getElementById('aramex_shipment_shipper_street').value;
            var aramex_shipment_shipper_country = document.getElementById('aramex_shipment_shipper_country').value;
            var aramex_shipment_shipper_city = document.getElementById('aramex_shipment_shipper_city').value;
            var aramex_shipment_shipper_postal = document.getElementById('aramex_shipment_shipper_postal').value;
            var aramex_shipment_shipper_state = document.getElementById('aramex_shipment_shipper_state').value;
            var aramex_shipment_shipper_phone = document.getElementById('aramex_shipment_shipper_phone').value;
            var aramex_shipment_receiver_name = document.getElementById('aramex_shipment_receiver_name').value;
            var aramex_shipment_receiver_email = document.getElementById('aramex_shipment_receiver_email').value;
            var aramex_shipment_receiver_company = document.getElementById('aramex_shipment_receiver_company').value;
            var aramex_shipment_receiver_street = document.getElementById('aramex_shipment_receiver_street').value;
            var aramex_shipment_receiver_country = document.getElementById('aramex_shipment_receiver_country').value;
            var aramex_shipment_receiver_city = document.getElementById('aramex_shipment_receiver_city').value;
            var aramex_shipment_receiver_postal = document.getElementById('aramex_shipment_receiver_postal').value;
            var aramex_shipment_receiver_state = document.getElementById('aramex_shipment_receiver_state').value;
            var aramex_shipment_receiver_phone = document.getElementById('aramex_shipment_receiver_phone').value;

            jQuery(document).ready(function ($) {
                $("#aramex_shipment_info_billing_account_id").change(function () {
                    resetShipperDetail(this);
                });
            });

            function resetShipperDetail(el) {
                //alert(el.value);
                var elValue = el.value;
                var flag = 0;
                if (elValue == 2) {

                    document.getElementById('aramex_shipment_shipper_name').value = aramex_shipment_receiver_name;
                    document.getElementById('aramex_shipment_shipper_email').value = aramex_shipment_receiver_email;
                    document.getElementById('aramex_shipment_shipper_company').value = aramex_shipment_receiver_company;
                    document.getElementById('aramex_shipment_shipper_street').value = aramex_shipment_receiver_street;
                    document.getElementById('aramex_shipment_shipper_country').value = aramex_shipment_receiver_country;
                    document.getElementById('aramex_shipment_shipper_city').value = aramex_shipment_receiver_city;
                    document.getElementById('aramex_shipment_shipper_postal').value = aramex_shipment_receiver_postal;
                    document.getElementById('aramex_shipment_shipper_state').value = aramex_shipment_receiver_state;
                    document.getElementById('aramex_shipment_shipper_phone').value = aramex_shipment_receiver_phone;
                    document.getElementById('aramex_shipment_receiver_name').value = aramex_shipment_shipper_name;
                    document.getElementById('aramex_shipment_receiver_email').value = aramex_shipment_shipper_email;
                    document.getElementById('aramex_shipment_receiver_company').value = aramex_shipment_shipper_company;
                    document.getElementById('aramex_shipment_receiver_street').value = aramex_shipment_shipper_street;
                    document.getElementById('aramex_shipment_receiver_country').value = aramex_shipment_shipper_country;
                    document.getElementById('aramex_shipment_receiver_city').value = aramex_shipment_shipper_city;
                    document.getElementById('aramex_shipment_receiver_postal').value = aramex_shipment_shipper_postal;
                    document.getElementById('aramex_shipment_receiver_state').value = aramex_shipment_shipper_state;
                    document.getElementById('aramex_shipment_receiver_phone').value = aramex_shipment_shipper_phone;
                    document.getElementById('aramex_shipment_info_payment_type').value = 'C';
                    flag = 1;
                } else if (elValue == 3) {
                	/*
                    document.getElementById('aramex_shipment_shipper_name').value = "";
                    document.getElementById('aramex_shipment_shipper_email').value = "";
                    document.getElementById('aramex_shipment_shipper_company').value = "";
                    document.getElementById('aramex_shipment_shipper_street').value = "";
                    document.getElementById('aramex_shipment_shipper_country').value = "";
                    document.getElementById('aramex_shipment_shipper_city').value = "";
                    document.getElementById('aramex_shipment_shipper_postal').value = "";
                    document.getElementById('aramex_shipment_shipper_state').value = "";
                    document.getElementById('aramex_shipment_shipper_phone').value = "";
                    */
                    document.getElementById('aramex_shipment_info_payment_type').value = '3';
                    document.getElementById('ASCC').style.display = 'block';
                    document.getElementById('ARCC').style.display = 'block';
                    document.getElementById('CASH').style.display = 'none';
                    document.getElementById('ACCT').style.display = 'none';
                    document.getElementById('PPST').style.display = 'none';
                    document.getElementById('CRDT').style.display = 'none';
                    $('#aramex_shipment_info_payment_option').val("");
                    flag = 2;
                } else {
                    if (flag = 1) {
                        document.getElementById('aramex_shipment_receiver_name').value = aramex_shipment_receiver_name;
                        document.getElementById('aramex_shipment_receiver_email').value = aramex_shipment_receiver_email;
                        document.getElementById('aramex_shipment_receiver_company').value = aramex_shipment_receiver_company;
                        document.getElementById('aramex_shipment_receiver_street').value = aramex_shipment_receiver_street;
                        document.getElementById('aramex_shipment_receiver_country').value = aramex_shipment_receiver_country;
                        document.getElementById('aramex_shipment_receiver_city').value = aramex_shipment_receiver_city;
                        document.getElementById('aramex_shipment_receiver_postal').value = aramex_shipment_receiver_postal;
                        document.getElementById('aramex_shipment_receiver_state').value = aramex_shipment_receiver_state;
                        document.getElementById('aramex_shipment_receiver_phone').value = aramex_shipment_receiver_phone;
                        document.getElementById('aramex_shipment_shipper_name').value = aramex_shipment_shipper_name;
                        document.getElementById('aramex_shipment_shipper_email').value = aramex_shipment_shipper_email;
                        document.getElementById('aramex_shipment_shipper_company').value = aramex_shipment_shipper_company;
                        document.getElementById('aramex_shipment_shipper_street').value = aramex_shipment_shipper_street;
                        document.getElementById('aramex_shipment_shipper_country').value = aramex_shipment_shipper_country;
                        document.getElementById('aramex_shipment_shipper_city').value = aramex_shipment_shipper_city;
                        document.getElementById('aramex_shipment_shipper_postal').value = aramex_shipment_shipper_postal;
                        document.getElementById('aramex_shipment_shipper_state').value = aramex_shipment_shipper_state;
                        document.getElementById('aramex_shipment_shipper_phone').value = aramex_shipment_shipper_phone;
                        document.getElementById('aramex_shipment_info_payment_type').value = 'P';
                        document.getElementById('ASCC').style.display = 'none';
                        document.getElementById('ARCC').style.display = 'none';
                        document.getElementById('CASH').style.display = 'block';
                        document.getElementById('ACCT').style.display = 'block';
                        document.getElementById('PPST').style.display = 'block';
                        document.getElementById('CRDT').style.display = 'block';
                        $('#aramex_shipment_info_payment_option').val("");


                    } else if (flag = 2) {
                        document.getElementById('aramex_shipment_shipper_name').value = aramex_shipment_shipper_name;
                        document.getElementById('aramex_shipment_shipper_email').value = aramex_shipment_shipper_email;
                        document.getElementById('aramex_shipment_shipper_company').value = aramex_shipment_shipper_company;
                        document.getElementById('aramex_shipment_shipper_street').value = aramex_shipment_shipper_street;
                        document.getElementById('aramex_shipment_shipper_country').value = aramex_shipment_shipper_country;
                        document.getElementById('aramex_shipment_shipper_city').value = aramex_shipment_shipper_city;
                        document.getElementById('aramex_shipment_shipper_postal').value = aramex_shipment_shipper_postal;
                        document.getElementById('aramex_shipment_shipper_state').value = aramex_shipment_shipper_state;
                        document.getElementById('aramex_shipment_shipper_phone').value = aramex_shipment_shipper_phone;
                        document.getElementById('aramex_shipment_receiver_name').value = aramex_shipment_receiver_name;
                        document.getElementById('aramex_shipment_receiver_email').value = aramex_shipment_receiver_email;
                        document.getElementById('aramex_shipment_receiver_company').value = aramex_shipment_receiver_company;
                        document.getElementById('aramex_shipment_receiver_street').value = aramex_shipment_receiver_street;
                        document.getElementById('aramex_shipment_receiver_country').value = aramex_shipment_receiver_country;
                        document.getElementById('aramex_shipment_receiver_city').value = aramex_shipment_receiver_city;
                        document.getElementById('aramex_shipment_receiver_postal').value = aramex_shipment_receiver_postal;
                        document.getElementById('aramex_shipment_receiver_state').value = aramex_shipment_receiver_state;
                        document.getElementById('aramex_shipment_receiver_phone').value = aramex_shipment_receiver_phone;
                        document.getElementById('aramex_shipment_info_payment_type').value = 'C';
                        document.getElementById('ASCC').style.display = 'none';
                        document.getElementById('ARCC').style.display = 'none';
                        document.getElementById('CASH').style.display = 'block';
                        document.getElementById('ACCT').style.display = 'block';
                        document.getElementById('PPST').style.display = 'block';
                        document.getElementById('CRDT').style.display = 'block';

                        $('#aramex_shipment_info_payment_option').val("");
                    }
                    flag = 0;
                }
                /* hot fix  P.R */
                $(".aramex_countries").trigger('change');
            }

            $('#aramex_shipment_info_payment_type').change(function () {
                if ($('#aramex_shipment_info_payment_type').val() == "P") {
                    document.getElementById('ASCC').style.display = 'none';
                    document.getElementById('ARCC').style.display = 'none';
                    document.getElementById('CASH').style.display = 'block';
                    document.getElementById('ACCT').style.display = 'block';
                    document.getElementById('PPST').style.display = 'block';
                    document.getElementById('CRDT').style.display = 'block';
                    $('#aramex_shipment_info_payment_option').val("");
                } else {
                    document.getElementById('ASCC').style.display = 'block';
                    document.getElementById('ARCC').style.display = 'block';
                    document.getElementById('CASH').style.display = 'none';
                    document.getElementById('ACCT').style.display = 'none';
                    document.getElementById('PPST').style.display = 'none';
                    document.getElementById('CRDT').style.display = 'none';
                    $('#aramex_shipment_info_payment_option').val("");

                }
            });

            <?php
            if (strpos($currentUrl, "aramexpopup/show")) {
                ?>
            aramexpop();

            function aramexpop() {
                $("#aramex_overlay").css("display", "block");
                $("#aramex_shipment").css("display", "block");
                $("#aramex_shipment_creation").fadeIn(1000);
            }
            <?php

            } ?>

            $("input[name=aramex_shipment_info_shipping_charges]").change(function () {
                var cod_value = parseFloat($("input[name=aramex_shipment_info_shipping_charges]").val()) + parseFloat($("input[name=aramex_shipment_info_items_subtotal]").val());
                $("input[name=aramex_shipment_info_cod_value]").val(cod_value);
            });


            $("#aramex_shipment_info_product_group").change(function () {

                if ($("select[name=aramex_shipment_info_product_group]").val() == 'EXP') {
                    $("select[name=aramex_shipment_info_additional_services] option:selected").removeAttr("selected");
                    $("select[name=aramex_shipment_info_additional_services] .express_service").attr("selected", "selected");
                    $("#aramex_shipment_info_product_type option").hide();


                } else if ($("select[name=aramex_shipment_info_product_group]").val() == 'DOM') {
                    $("select[name=aramex_shipment_info_additional_services] option:selected").removeAttr("selected");
                    $("select[name=aramex_shipment_info_additional_services] .domestic_service").attr("selected", "selected");
                    $("#aramex_shipment_info_product_type option").hide();

                }
                $("#aramex_shipment_info_service_type_div").html($("select[name=aramex_shipment_info_service_type] option:selected").text());
                $("#aramex_shipment_info_additional_services_div").html($("select[name=aramex_shipment_info_additional_services] option:selected").text());

            });
            $("#aramex_shipment_info_product_group_div").html($("select[name=aramex_shipment_info_product_group] option:selected").text());
            $("#aramex_shipment_info_service_type_div").html($("select[name=aramex_shipment_info_service_type] option:selected").text());
            $("#aramex_shipment_info_additional_services_div").html($("select[name=aramex_shipment_info_additional_services] option:selected").text());

            $("#TaxPaid").change(function () {
                if ($("#TaxPaid").val() == 1)
                    $("#taxAmountBlock").fadeIn(500);
                else
                    $("#taxAmountBlock").fadeOut(500);
                    document.getElementById('TaxAmount').value = "";
            })

            $("#aramex_shipment_shipper_country").change(function () {
                if ($("#aramex_shipment_shipper_country").val() == "IN" && $("#aramex_shipment_receiver_country").val() != "IN")
                    $("#indianFields").fadeIn(500);
                else
                    $("#indianFields").fadeOut(500);
            })

            $(document).ready(function () {
                var currentDate = new Date();
                var currentYear = currentDate.getFullYear();
                var currentMonth = currentDate.getMonth() + 1;
                var currentDay = currentDate.getDate();
                $("#InvoiceDate").prop('max', currentYear + "-" + currentMonth + "-" + currentDay);

                if (($('#aramex_messages').html() != "") && ($('.error-msg'))) {
                    $("#aramex_overlay").css("display", "block");
                    $("#aramex_shipment_creation").fadeIn(1000);
                }

                if ($("#aramex_shipment_shipper_country").val() == "IN" && $("#aramex_shipment_receiver_country").val() != "IN")
                    { $("#indianFields").fadeIn(500); }
                $(function () {
                    $("#aramex_shipment_info_pickup_date").datepicker({dateFormat: "yy-mm-dd"});
                    $("#aramex_shipment_info_ready_time").datepicker({dateFormat: "yy-mm-dd"});
                    $("#aramex_shipment_info_last_pickup_time").datepicker({dateFormat: "yy-mm-dd"});
                    $("#aramex_shipment_info_closing_time").datepicker({dateFormat: "yy-mm-dd"});
                });

                $('#filereset').click(function () {
                    $("#file1_div").html($("#file1_div").html());
                });
                $('#file2reset').click(function () {
                    $("#file2_div").html($("#file2_div").html());
                });
                $('#file3reset').click(function () {
                    $("#file3_div").html($("#file3_div").html());
                });

                $("#aramex_shipment_info_product_type").chained("#aramex_shipment_info_product_group");
                $("#aramex_shipment_info_service_type").chained("#aramex_shipment_info_product_group");

                $("#aramex_return_shipment_creation_submit_id").click(function () {
                    $('.loading-mask').css('display', 'block');
                });
                $("#aramex_shipment_creation_submit_id").click(function () {
                    $('.loading-mask').css('display', 'block');
                });

                $('#aramex_shipment_shipper_taxidvat').on('input', function () {
                    var inputValue = $(this).val();
                    var sanitizedValue = inputValue.replace(/[^a-zA-Z0-9]/g, '');
                    var limitedValue = sanitizedValue.slice(0, 15);
                    $(this).val(limitedValue);
                });
                $('#aramex_shipment_receiver_taxidvat').on('input', function () {
                    var inputValue = $(this).val();
                    var sanitizedValue = inputValue.replace(/[^a-zA-Z0-9]/g, '');
                    var limitedValue = sanitizedValue.slice(0, 15);
                    $(this).val(limitedValue);
                });
            });


        })(jQuery);
    </script>

    <style>
        .ui-front {
            z-index: 10000000;
        }

        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
            /* add padding to account for vertical scrollbar */
        }
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            <?php
            if ($aramex_return_button === true) {
                ?>
            jQuery("#aramex_shipment_info_billing_account_id").val(2);
            jQuery("#aramex_shipment_info_billing_account_id").trigger('change');
            <?php 
            } ?>

            <?php
            $settings = new Aramex_Shipping_Method();
    $allowed = $settings->settings['apilocationvalidator_active']; ?>
            var aramex_allow = "<?php echo esc_html($allowed); ?>";
            /* block script running*/
            if (aramex_allow == 0) {
                return false;
            }

            /* billing_aramex_cities and  shipping_aramex_cities */
            var type = '.aramex_shipment_creation_fieldset_left';
            setAutocomplate(type);
            var type = '.aramex_shipment_creation_fieldset_right';
            setAutocomplate(type);

            var type = '.aramex_top';
            setAutocomplate(type);
            var type = '.aramex_bottom';
            setAutocomplate(type);
            var type = '.schedule-pickup-part';
            setAutocomplate(type);

            function setAutocomplate(type) {
                var shippingAramexCitiesObj;
                var shipping_aramex_cities_temp;
                var billing_aramex_cities = '';
                var shipping_aramex_cities = billing_aramex_cities;

                /* set HTML blocks */
                shipping_aramex_cities_temp = shipping_aramex_cities;

                /* get Aramex sities */
                shippingAramexCitiesObj = AutoSearchControls(type, shipping_aramex_cities);
                jQuery(type).find(".aramex_countries").change(function () {
                    getAllCitiesJson(type, shippingAramexCitiesObj);
                });
                getAllCitiesJson(type, shippingAramexCitiesObj);

                function AutoSearchControls(type, search_city) {

                    return jQuery(type).find(".aramex_city")
                        .autocomplete({
                            /*source: search_city,*/
                            minLength: 3,
                            scroll: true,
                            source: function (req, responseFn) {
                                var re = $.ui.autocomplete.escapeRegex(req.term);
                                var matcher = new RegExp("^" + re, "i");
                                var a = jQuery.grep(search_city, function (item, index) {
                                    return matcher.test(item);
                                });
                                responseFn(a);
                            },
                            search: function (event, ui) {
                                /* open initializer */

                            },
                            response: function (event, ui) {
                                var temp_arr = [];
                                jQuery(ui.content).each(function (i, v) {
                                    temp_arr.push(v.value);
                                });

                                return temp_arr;
                            }
                        });
                }

                function getAllCitiesJson(type, aramexCitiesObj) {
                    var country_code = jQuery(type).find(".aramex_countries").val();
                    var _wpnonce = "<?php echo esc_js(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>";


                        var url_check = "<?php echo admin_url('admin-ajax.php'); ?>?country_code=" + country_code + '&_wpnonce=' + _wpnonce+ "&action=the_aramex_searchautocities&backend=backend";





                    shipping_aramex_cities_temp = '';

                    aramexCitiesObj.autocomplete("option", "source", url_check);
                }
            }
        });
     </script>
    <?php
    $history = get_comments(array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'approve' => 'approve',
        'type' => 'order_note',
    ));

    $history_list = array();
    if (count($history) > 0) {
        foreach ($history as $shipment) {
            $history_list[] = $shipment->comment_content;
        }
    }
    $last_track = "";
    if (count($history_list)) {
        foreach ($history_list as $history) {
            $awbno = strstr($history, "- Order No", true);
            $awbno = trim($awbno, "AWB No.");
            if (isset($awbno)) {
                if ((int)$awbno) {
                    $last_track = $awbno;
                    break;
                }
            }
            $awbno = trim($awbno, "Aramex Shipment Return Order AWB No.");
            if (isset($awbno)) {
                if ((int)$awbno) {
                    $last_track = $awbno;
                    break;
                }
            }
        }
    } ?>
              <script type="text/javascript">
                jQuery.noConflict();
                (function ($) {
                    $(document).ready(function () {
                        $('#print_aramex_shipment').click(function () {
                            sendAjax();
                        });
                        $('.print_aramex_shipment').click(function () {
                            sendAjax();
                        });
                        function sendAjax(){
                            var postData = {
                                action: 'the_aramex_print_lable',
                                aramex_printlabel:"<?php echo esc_attr($order_id); ?>",
                                aramex_lasttrack: $('#aramex-lasttrack-field').val(),
                                _wpnonce:"<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"
                            };

                            jQuery.post(ajaxurl, postData, function(request) {
                                window.location.href = request;
                            });                            
                                
                        }
                        $('#printlabel_close').click(function () {
                            $('.printlabel_overlay').css("display", "none");
                        });
                    });
                })(jQuery);

            </script>
<?php 
}
