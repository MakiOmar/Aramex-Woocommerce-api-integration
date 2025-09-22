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
         *  Render "Schedule" form
         *
         * @param $order object Order object
         * @return string Template
         */
function aramex_display_schedule_pickup_in_admin($order)
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']
    ) {
        return false;
    }
    $countryCollection = WC()->countries->countries;
    $settings = new Aramex_Shipping_Method();
    $city = $settings->settings['city'];
    $postalcode = $settings->settings['postalcode'];
    $country = $settings->settings['country'];
    $allowed_domestic_methods = $settings->form_fields['allowed_domestic_methods']['options'];
    $allowed_international_methods = $settings->form_fields['allowed_international_methods']['options'];
    $dom = $settings->settings['allowed_domestic_methods'];
    $exp = $settings->settings['allowed_international_methods'];
    $order_id = $order->get_id();
    $state = $settings->settings['state'];
    $email = $settings->settings['email_origin'];
    $company = $settings->settings['company'];
    $phone = $settings->settings['phone'];
    $address = $settings->settings['address'];
    $name = $settings->settings['name'];
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

    ?>
<!-- Aramex Schedule PickUp -->
    <div class="schedule-pickup-part">
        <div class="pickup-form">
            <form method="post"
                  action="<?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/includes/shipment/class-aramex-woocommerce-shipment.php'); ?>"
                  id="pickup-form">
                <input name="_wpnonce" id="aramex-shipment-nonce" type="hidden"
                       value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"/>
                <FIELDSET>
                    <legend style="font-weight:bold; padding:0 5px;"><?php echo esc_html__('Schedule Pickup',
                            'aramex'); ?></legend>
                    <div class="fields mar-5">
                        <h3><?php echo esc_html__('Pickup Details', 'aramex'); ?></h3>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Location:', 'aramex'); ?></label>
                                <input readonly="readonly" name="location" id="pickup_location"
                                       value="Reception"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Vehicle Type:', 'aramex'); ?></label>
                                <select name="vehicle" id="pickup_vehicle">
                                    <option value="Bike"><?php echo esc_html__('Small (no specific vehicle required)',
                                            'aramex'); ?></option>
                                    <option value="Car"><?php echo esc_html__('Medium (regular car or small van)',
                                            'aramex'); ?></option>
                                    <option value="Truck"><?php echo esc_html__('Large (large van or truck required)',
                                            'aramex'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Date:', 'aramex'); ?> <span class="red">*</span></label>
                                <input readonly="readonly" name="date" id="pickup_date"
                                       value="<?php echo esc_attr(date("m/d/Y", current_time('timestamp'))); ?>"
                                       class="width-150 fl"/>
                            </div>
                            <div class="field fl f1l">
                                <label><?php echo esc_html__('Ready Time:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="ready_hour" class="width-60 " id="ready_hour">
                                    <?php $time = date("H", current_time('timestamp')); ?>
                                    <?php for ($i = 7; $i < 20; $i++): ?>
                                        <?php $val = ($i < 10) ? "0{$i}" : $i; ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php echo ($time == $i) ? 'selected="selected"' : ''; ?>><?php echo esc_attr($val); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="ready_minute" class="width-60  mar-lf-10" id="ready_minute">
                                    <?php $time = date("i", current_time('timestamp')); ?>
                                    <?php for ($i = 0; $i <= 55; $i = $i + 5): ?>
                                        <?php $val = ($i < 10) ? "0{$i}" : $i; ?>
                                        <option value="<?php echo $val; ?>" <?php echo ($time == $val) ? 'selected="selected"' : ''; ?>><?php echo esc_attr($val); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="clearfix"></div>
                            </div>
                            <div class="field fl mar-lf-10 f1l">
                                <label><?php echo esc_html__('Closing Time:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="latest_hour" class="width-60 fl" id="latest_hour">
                                    <?php $time = date("H", current_time('timestamp')); ?>
                                    <?php $time = $time + 1; ?>
                                    <?php for ($i = 7; $i < 20; $i++): ?>
                                        <?php $val = ($i < 10) ? "0{$i}" : $i; ?>
                                        <option value="<?php echo esc_attr($val) ?>" <?php echo ($time == $val) ? 'selected="selected"' : ''; ?>><?php echo esc_attr($val); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="latest_minute" class="width-60 fl mar-lf-10" id="latest_minute">
                                    <?php $time = date("i", current_time('timestamp')); ?>
                                    <?php for ($i = 0; $i <= 55; $i = $i + 5): ?>
                                        <?php $val = ($i < 10) ? "0{$i}" : $i; ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php echo ($time == $val) ? 'selected="selected"' : ''; ?>><?php echo esc_attr($val); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Reference 1:', 'aramex'); ?></label>
                                <input name="reference" id="pickup_reference"
                                       value="<?php echo esc_attr($order_id); ?>"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Status:', 'aramex'); ?> <span class="red">*</span></label>
                                <select name="status" id="pickup_status">
                                    <option value="Ready"><?php echo esc_html__('Ready', 'aramex'); ?> </option>
                                    <option value="Pending"><?php echo esc_html__('Pending', 'aramex'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Product Group:', 'aramex'); ?> <span class="red">*</span></label>
                                <?php $country1 = ($order->get_shipping_country()) ? $order->get_shipping_country() : ''; ?>
                                <?php $checkCountry = ($country1 == $country) ? true : false; ?>
                                <select name="product_group" id="product_group">
                                    <option <?php echo ($checkCountry == true) ? 'selected="selected"' : ''; ?>
                                            value="DOM"><?php echo esc_html__('Domestic', 'aramex'); ?>
                                    </option>
                                    <option <?php echo ($checkCountry == false) ? 'selected="selected"' : ''; ?>
                                            value="EXP"><?php echo esc_html__('International Express', 'aramex'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Product Type:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="product_type" class="fl" id="product_type">
                                    <?php
                                    if (count($allowed_domestic_methods) > 0) {
                                        foreach ($allowed_domestic_methods as $key => $val) {
                                            $selected_str = "";
                                            $selected_str = ($dom == $key) ? 'selected="selected"' : ''; ?>
                                            <option <?php echo esc_attr($selected_str); ?>
                                                    value="<?php echo esc_attr($key); ?>"
                                                    id="<?php echo esc_attr($key); ?>"
                                                    class="DOM"><?php echo esc_attr($val); ?></option>
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
                                            <option <?php echo esc_attr($selected_str); ?>
                                                    value="<?php echo esc_attr($key); ?>"
                                                    id="<?php echo esc_attr($key); ?>"
                                                    class="EXP"><?php echo esc_attr($val); ?></option>
                                            <?php

                                        }
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Payment Type:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="payment_type">
                                    <option value="P"><?php echo esc_html__('Prepaid', 'aramex'); ?></option>
                                    <option value="C"><?php echo esc_html__('Collect', 'aramex'); ?></option>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Weight', 'aramex'); ?> <span class="red">*</span></label>
                                <div>
                                    <input name="text_weight" id="text_weight"
                                           class="fl mar-right-10 width-60"
                                           value="<?php echo esc_attr(number_format($totalWeight, 2)); ?>"/>
                                    <select name="weight_unit" class="fl width-60">
                                        <option value="kg">kg</option>
                                        <option value="lbs">lbs</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label class="width-150"><?php echo esc_html__('Number of Pieces:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <input class="requried-entry" name="no_pieces" id="no_pieces"
                                       value="1"/>
                            </div>
                            <div class="field fl">
                                <label class="width-150"><?php echo esc_html__('Number of Shipments:', 'aramex'); ?>
                                    <span class="red">*</span></label>
                                <input name="no_shipments" class="requried-entry"
                                       id="no_shipments" value="1"/>
                            </div>
                        </div>

                    </div>
                    <div class="fields mar-10">
                        <h3><?php echo esc_html__('Address Information', 'aramex'); ?></h3>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Company:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <input name="company" id="pickup_company"
                                       value="<?php echo esc_attr($company); ?>"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Contact:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <input name="contact" class="requried-entry" id="pickup_contact"
                                       value="<?php echo esc_attr($name); ?>"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Phone:', 'aramex'); ?> <span class="red">*</span></label>
                                <input name="phone" id="pickup_phone" class="requried-entry"
                                       value="<?php echo esc_attr($phone); ?>"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Extension:', 'aramex'); ?></label>
                                <input name="ext" id="pickup_ext" value=""/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field">
                                <label><?php echo esc_html__('Mobile:', 'aramex'); ?> <span class="red">*</span></label>
                                <input name="mobile" id="mobile" value=""
                                       class="width-full required-entry"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field">
                                <label><?php echo esc_html__('Address:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <input name="address" id="address"
                                       value="<?php echo esc_attr($address); ?>"
                                       class="width-full required-entry"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Country:', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="country" id="pickup_country" class="aramex_countries">
                                    <?php if (count($countryCollection) > 0) {
                                        ?>
                                        <?php foreach ($countryCollection as $key => $value) {
                                            ?>
                                            <option value="<?php echo esc_attr($key) ?>"
                                                <?php
                                                if ($country) {
                                                    echo ($country == $key) ? 'selected="selected"' : '';
                                                } ?>>
                                                <?php echo esc_attr($value) ?>
                                            </option>
                                        <?php 
                                        } ?>
                                    <?php 
                                    } ?>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('State/Prov:', 'aramex'); ?></label>
                                <input name="state" id="pickup_state"
                                       value="<?php echo esc_attr($state); ?>"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('City:', 'aramex'); ?> <span
                                            class="red no-display">*</span></label>
                                <input name="city" id="city" class="aramex_city" autocomplete="off"
                                       value="<?php echo esc_attr($city); ?>"/>
                                <div id="pickup_city_autocomplete" class="am_autocomplete"></div>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Post Code:', 'aramex'); ?> <span
                                            class="red no-display">*</span></label>
                                <input name="zip" id="pickup_zip" class=" required-entry"
                                       value="<?php echo esc_attr($postalcode); ?>"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field">
                                <label><?php echo esc_html__('Email:', 'aramex'); ?> <span class="red">*</span></label>
                                <input name="email" id="email" value="<?php echo esc_attr($email); ?>"
                                       class="width-full required-entry"/>
                            </div>
                        </div>
                        <div class="clearfix mar-5">
                            <div class="field">
                                <label><?php echo esc_html__('Comments:', 'aramex'); ?></label>
                                <input name="comments" id="pickup_comments" value=""
                                       class="width-full"/>
                            </div>
                        </div>
                        <div class="cal-button-part">
                            <button name="aramex_pickup_submit" type="button" class='button-primary'
                                    id="aramex_pickup_submit">Submit
                            </button>
                            <button type="button" class='button-primary'
                                    onclick="myObj.close()"><?php echo esc_html__('Close', 'aramex'); ?></button>
                            <span class="mar-lf-10 red">* <?php echo esc_html__('are required fields',
                                    'aramex'); ?></span>
                            <input type="hidden" value="<?php echo esc_attr($order_id); ?>"
                                   name="order_id"/>
                        </div>
                        <div class="aramex_loader"
                             style="background-image: url(<?php echo plugins_url() . '/aramex-shipping-woocommerce/assets/img/preloader.gif'; ?>); height:60px; margin:10px 0; background-position-x: center; display:none; background-repeat: no-repeat; ">

                        </div>
                        <div class="pickup-result mar-10">
                            <h3><?php echo esc_html__('Result', 'aramex'); ?></h3>
                            <div class="pickup-res mar-10"></div>
                        </div>
                    </div>
                </FIELDSET>
            </form>
            <script type="text/javascript">
                jQuery.noConflict();
                (function ($) {
                    $(document).ready(function () {
                        $("#product_type").chained("#product_group");
                        var H = '<?php echo esc_js(date("H", current_time('timestamp'))); ?>';
                        var M = '<?php echo esc_js(date("i", current_time('timestamp'))); ?>';
                        var D = '<?php echo esc_js(date("m/d/Y", current_time('timestamp'))); ?>';
                        $('#pickup_date').datepicker({
                            dateFormat: 'mm/dd/yy'
                        });
                        $('#aramex_pickup_submit').click(function () {
                            var validator =$('#pickup-form').validate({
                                        rules: {
                                            mobile: {required: true,},
                                            address: {required: true,},
                                            city: {required: true,},
                                            email: {required: true,},
                                            text_weight: {required: true,},
                                            no_pieces: {required: true,},
                                            no_shipments: {required: true,}
                                        }
                                    }
                                )
                            if (validator && validator.errorList.length === 0) {
                                var rH = $('#ready_hour').val();
                                    var lH = $('#latest_hour').val();
                                    var rM = $('#ready_minute').val();
                                    var lM = $('#latest_minute').val();
                                    var error = false;
                                    var rDate = $('#pickup_date').val();
                                    if (rDate == '' || rDate == null) {
                                        alert("<?php echo esc_html__('Pickup Date should not empty', 'aramex'); ?>");
                                        return;
                                    }
                                    rDate = rDate.split("/");
                                    cDate = D.split("/");
                                    var isCheckTime = false;
                                    if (rDate[2] < cDate[2]) {
                                        error = true;
                                    } else if (rDate[2] == cDate[2]) {

                                        if (rDate[0] < cDate[0]) {
                                            error = true;
                                        } else if (rDate[0] == cDate[0]) {
                                            if (rDate[1] < cDate[1]) {
                                                error = true;
                                            } else if (rDate[1] == cDate[1]) {
                                                if (rH < H) {
                                                    alert("<?php echo esc_html__('Ready Time should be greater than Current Time',
                                                        'aramex'); ?>");
                                                    return;
                                                } else if (rH == H && rM < M) {
                                                    alert("<?php echo esc_html__('Ready Time should be greater than Current Time',
                                                        'aramex'); ?>");
                                                    return;
                                                }
                                                isCheckTime = true;
                                            }
                                        }
                                    }
                                    if (error) {
                                        alert("<?php echo esc_html__('Pickup Date should be greater than Current Date',
                                            'aramex'); ?>");
                                        return;
                                    }
                                    if (isCheckTime) {
                                        if (lH < rH) {
                                            error = true;
                                        } else if (lH <= rH && lM <= rM) {
                                            error = true;
                                        }
                                        if (error) {
                                            alert("<?php echo esc_html__('Closing Time always greater than Ready Time',
                                                'aramex'); ?>");
                                            return;
                                        }
                                    }
                                    if (validator.errorList.length === 0) {
                                        myObj.schedulePickup();
                                    }
                                    return false;
                            }
                        });
                    });
                })(jQuery);
            </script>
        </div>
    </div>
<?php 
} ?>