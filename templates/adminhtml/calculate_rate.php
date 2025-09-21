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
         *  Render "Calculate rate" form
         * 
         * @param $order object Order object
         * @return string Template
         */
function aramex_display_rate_calculator_in_admin($order)
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    }
    $countryCollection = WC()->countries->countries;
    $settings = new Aramex_Shipping_Method();
    $city = $settings->settings['city'];
    $postalcode = $settings->settings['postalcode'];
    $state = $settings->settings['state'];
    $country = $settings->settings['country'];
    $allowed_domestic_methods = $settings->form_fields['allowed_domestic_methods']['options'];
    $allowed_international_methods = $settings->form_fields['allowed_international_methods']['options'];
    $dom = $settings->settings['allowed_domestic_methods'];
    $exp = $settings->settings['allowed_international_methods'];
    $order_id = $order->get_id();
    $unit = get_option('woocommerce_weight_unit');
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
    <!-- Aramex Calculate Rate -->
    <div class="back-over"></div>
    <div class="cal-rate-part">
        <div class="cal-form">
            <form method="post" action="" id="calc-rate-form">
                <input name="_wpnonce" id="aramex-shipment-nonce" type="hidden"
                       value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"/>
                <input name="rate_calc_order_id" id="rate-calc-order-id" type="hidden"
                       value="<?php echo esc_attr($order_id); ?>"/>
                <FIELDSET>
                    <legend style="font-weight:bold; padding:0 5px;"><?php echo esc_html__('Calculate Rates',
                            'aramex'); ?></legend>
                    <div class="fields mar-10  aramex_top">
                        <h3><?php echo esc_html__('Shipment Origin', 'aramex'); ?></h3>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Country', 'aramex'); ?> <span class="red">*</span></label>
                                <select name="origin_country" class="arm_country aramex_countries" id="origin_country">
                                    <?php if (count($countryCollection) > 0) {
                                ?>
                                        <?php foreach ($countryCollection as $key => $value) {
                                    ?>
                                            <option value="<?php echo esc_attr($key); ?>"
                                                <?php
                                                if ($country) {
                                                    echo ($country == $key) ? 'selected="selected"' : '';
                                                } ?>>
                                                <?php echo esc_attr($value); ?>
                                            </option>
                                        <?php 
                                } ?>
                                    <?php 
                            } ?>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('City', 'aramex'); ?> <span class="red no-display">*</span></label>
                                <input name="origin_city" id="origin_city" class="aramex_city"
                                       value="<?php echo esc_attr($city); ?>"/>
                                <div id="origin_city_autocomplete" class="am_autocomplete"></div>
                            </div>
                        </div>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Zip code', 'aramex'); ?> <span
                                            class="red no-display">*</span></label>
                                <input name="origin_zipcode" id="origin_zipcode"
                                       value="<?php echo esc_attr($postalcode); ?>"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('State / Province', 'aramex'); ?></label>
                                <input name="origin_state" id="origin_state" value="<?php echo esc_attr($state); ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="fields mar-10  aramex_bottom">
                        <h3><?php echo esc_html__('Shipment Destination', 'aramex'); ?></h3>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Country', 'aramex'); ?> <span class="red">*</span></label>
                                <select name="destination_country" class="arm_country aramex_countries"
                                        id="destination_country">
                                    <?php if (count($countryCollection) > 0): ?>
                                        <?php foreach ($countryCollection as $key => $value): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php if ($order->get_shipping_country()) {
                                echo ($order->get_shipping_country() == $key) ? 'selected="selected"' : '';
                            } ?>>
                                                <?php echo esc_attr($value); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('City', 'aramex'); ?> <span class="red no-display">*</span></label>
                                <input name="destination_city" autocomplete="off" id="destination_city"
                                       class="aramex_city"
                                       value="<?php echo ($order->get_shipping_city()) ? $order->get_shipping_city() : ''; ?>"/>
                                <div id="destination_city_autocomplete" class="am_autocomplete "></div>
                            </div>
                        </div>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Zip code', 'aramex'); ?> <span
                                            class="red no-display">*</span></label>
                                <input name="destination_zipcode" id="destination_zipcode"
                                       value="<?php echo esc_attr(($order->get_shipping_postcode())) ? esc_attr($order->get_shipping_postcode()) : ''; ?>"/>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('State / Province', 'aramex'); ?></label>
                                <input name="destination_state" id="destination_state"
                                       value="<?php echo esc_attr(($order->get_shipping_state())) ? esc_attr($order->get_shipping_state()) : ''; ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="fields mar-10">
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Payment Type', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <select name="payment_type">
                                    <option value="P"><?php echo esc_html__('Prepaid', 'aramex'); ?></option>
                                    <option value="C"><?php echo esc_html__('Collect', 'aramex'); ?></option>
                                    <option value="3"><?php echo esc_html__('Third Party', 'aramex'); ?></option>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Product Type', 'aramex'); ?> <span
                                            class="red">*</span></label>
                                <?php $country1 = ($order->get_shipping_country()) ? $order->get_shipping_country() : ''; ?>
                                <?php $checkCountry = ($country1 == $country) ? true : false; ?>
                                <select name="product_group" id="calc-product-group">
                                    <option <?php echo ($checkCountry == true) ? 'selected="selected"' : ''; ?>
                                            value="DOM"><?php echo esc_html__('Domestic', 'aramex'); ?>
                                    </option>
                                    <option <?php echo ($checkCountry == false) ? 'selected="selected"' : ''; ?>
                                            value="EXP"><?php echo esc_html__('International Express', 'aramex'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Service Type', 'aramex'); ?> <span
                                            class="no-display">*</span></label>
                                <select name="service_type" class="fl" id="service_type">
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
                                            <option <?php echo esc_attr($selected_str); ?>
                                                    value="<?php echo esc_attr($key); ?>"
                                                    id="<?php echo esc_attr($key); ?>"
                                                    class="EXP"><?php echo esc_html($val); ?></option>
                                            <?php

                                        }
                                    } ?>
                                </select>
                            </div>
                            <div class="field fl">
                                <label><?php echo esc_html__('Weight', 'aramex'); ?> <span class="red">*</span></label>
                                <div>
                                    <input name="text_weight" class="fl mar-right-10 width-60"
                                           value="<?php echo number_format($totalWeight, 2); ?>"/>
                                    <select name="weight_unit" class="fl width-60">
                                        <option value="kg" <?php echo ($unit == 'kg') ? 'selected="selected"' : ''; ?> >
                                            <?php echo "kg" ?>
                                        </option>
                                        <option value="lb" <?php echo ($unit == 'lbs') ? 'selected="selected"' : ''; ?>>
                                            <?php echo "lbs" ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix mar-10">
                            <div class="field fl width-270">
                                <label style="width:270px;"><?php echo esc_html__('Number of Pieces:', 'aramex'); ?>
                                    <span class="red ">*</span></label>
                                <input value="1" name="total_count" class="fl"/>
                            </div>

                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Insurance Amount:', 'aramex'); ?> </label>
                                <input value="" name="insurance_amount"
                                       class="fl"/>
                            </div>

                            <div class="field fl width-270">
                                <label><?php echo esc_html__('Preferred Currency Code:', 'aramex'); ?> </label>
                                <input value="<?php echo esc_attr(get_woocommerce_currency()) ?>" name="currency_code"
                                       class="fl"/>
                            </div>
                        </div>
                        <div class="cal-button-part">
                            <button name="aramex_calc_rate_submit" type="button" id="aramex_calc_rate_submit"
                                    class="button-primary"><?php echo esc_html__('Calculate', 'aramex'); ?>
                            </button>
                            <button type="button" class="button-primary"
                                    onclick="myObj.close()"><?php echo esc_html__('Close', 'aramex'); ?></button>
                            <span class="mar-lf-10 red">*<?php echo esc_html__(' are required fields',
                                    'aramex'); ?> </span>
                            <input type="hidden" value="<?php echo esc_attr($order_id); ?>" name="reference"/>
                        </div>
                        <div class="aramex_loader"
                             style="background-image: url(<?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/assets/img/preloader.gif'); ?>); height:60px; margin:10px 0; background-position-x: center; display:none; background-repeat: no-repeat; ">
                        </div>
                        <div class="rate-result mar-10">
                            <h3><?php echo esc_html__('Result', 'aramex'); ?></h3>
                            <div class="result mar-10"></div>
                        </div>
                    </div>
                </FIELDSET>
                <script type="text/javascript">
                    jQuery.noConflict();
                    (function ($) {
                        $(document).ready(function () {
                            $('#aramex_calc_rate_submit').click(function () { // The button type should be "button" and not submit
                                var validator = $('#calc-rate-form').validate({
                                    rules: {
                                        origin_zipcode: {required: true},
                                        destination_city: {required: true}
                                        }
                                    });
                                    if (validator && validator.errorList.length === 0) {
                                        myObj.calcRate();
                                        }
                                    });
                            $("#service_type").chained("#calc-product-group");
                        });
                    })(jQuery);
                </script>
            </form>
        </div>
    </div>

<?php 
} ?>