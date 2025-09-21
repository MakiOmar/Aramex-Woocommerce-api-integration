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
         *  Render "Bulk" form
         *
         * @return string Template
         */
function aramex_display_bulk_in_admin()
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    } ?>
    <div class="order_in_background" style="display:none;">
        <div class="aramex_bulk">
            <p><strong><?php echo esc_html__('Shipment Default Information', 'aramex'); ?> </strong></p>
            <form id="massform">
                <div class="aramex_shipment_creation_part_left">
                    <FIELDSET class="aramex_shipment_creation_fieldset_big">
                        <div class="text_short">
                            <label><strong><?php echo esc_html__('Domestic Product Group',
                                        'aramex'); ?></strong></label>
                            <input class="aramex_all_options" id="aramex_shipment_info_product_group"
                                   name="aramex_shipment_info_product_group_dom" type="hidden" value="DOM"/>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Service Type', 'aramex'); ?></label>
                            <?php
                            include_once(plugin_dir_path(__FILE__) . '../../includes/shipping/class-aramex-woocommerce-shipping.php');
    $settings = new Aramex_Shipping_Method();
    $allowed_domestic_methods = $settings->form_fields['allowed_domestic_methods']['options'];
    $allowed_international_methods = $settings->form_fields['allowed_international_methods']['options'];
    $allowed_domestic_additional_services = $settings->form_fields['allowed_domestic_additional_services']['options'];
    $allowed_international_additional_services = $settings->form_fields['allowed_international_additional_services']['options']; 
    $allowed_domestic_additional_service = array();

    if(isset($settings->settings['allowed_domestic_additional_services']) && !empty($settings->settings['allowed_domestic_additional_services'])){
        foreach ($settings->settings['allowed_domestic_additional_services'] as $domestic_additional_services) {
            $allowed_domestic_additional_service[$domestic_additional_services] = $allowed_domestic_additional_services[$domestic_additional_services];
        }
    }
    
    $allowed_international_additional_service = array();
    if(isset($settings->settings['allowed_international_additional_services']) && !empty($settings->settings['allowed_international_additional_services'])){
        foreach ($settings->settings['allowed_international_additional_services'] as $international_additional_services) {
            $allowed_international_additional_service[$international_additional_services] = $allowed_international_additional_services[$international_additional_services];
        }
    }
    
    ?>
                            <select class="aramex_all_options" id="aramex_shipment_info_product_type"
                                    name="aramex_shipment_info_product_type_dom">
                                <?php
                                
                                if (count($allowed_domestic_methods) > 0) {
                                    foreach ($allowed_domestic_methods as $key => $val) {
                                        ?>
                                        <option value="<?php echo esc_attr($key); ?>"
                                                id="<?php echo esc_attr($key); ?>"><?php echo esc_attr($val); ?></option>
                                        <?php

                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Additional Services', 'aramex'); ?></label>
                            <select class="aramex_all_options" id="aramex_shipment_info_service_type"
                                    name="aramex_shipment_info_service_type_dom">
                                <option value=""></option>

                                <?php
                                if (count($allowed_domestic_additional_services) > 0) {
                                    foreach ($allowed_domestic_additional_services as $key => $val) {
                                        $setSelected = false;
                                        if(array_key_exists($key,$allowed_domestic_additional_service)){
                                            $setSelected = true;
                                        }
                                        ?>
                                
                                        <option 
                                            <?php

                                                    if($setSelected) {
                                                        echo 'selected="selected"';
                                                    }
                                                    else {
                                                        echo '';
                                                    }
                                            ?>
                                            value="<?php echo esc_attr($key); ?>"
                                                id="dom_as_<?php echo esc_attr($key); ?>"><?php echo esc_attr($val); ?></option>
                                        <?php

                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment Type', 'aramex'); ?></label>
                            <select class="aramex_all_options" id="aramex_shipment_info_payment_type"
                                    name="aramex_shipment_info_payment_type_dom">
                                <option value="P"><?php echo esc_html__('Prepaid', 'aramex'); ?></option>
                                <option value="C"><?php echo esc_html__('Collect', 'aramex'); ?></option>
                                <option value="3"><?php echo esc_html__('Third Party', 'aramex'); ?></option>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Currency', 'aramex'); ?></label><br>
                            <input type="text" class="" id="aramex_shipment_currency_code"
                                   name="aramex_shipment_currency_code_dom"
                                   value="<?php echo esc_attr(get_woocommerce_currency()) ?>"/>
                        </div>
                    </FIELDSET>
                </div>
                <div class="aramex_shipment_creation_part_right">
                    <FIELDSET class="aramex_shipment_creation_fieldset_big">
                        <div class="text_short">
                            <label><strong><?php echo esc_html__('International Product Group',
                                        'aramex'); ?></strong></label>
                            <input class="aramex_all_options" id="aramex_shipment_info_product_group"
                                   name="aramex_shipment_info_product_group" type="hidden" value="EXP"/>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Service Type', 'aramex'); ?></label><br/>
                            <select class="aramex_all_options" id="aramex_shipment_info_product_type"
                                    name="aramex_shipment_info_product_type">
                                <?php 
                                    if (count($allowed_international_methods) > 0) {
                                        foreach ($allowed_international_methods as $key => $val) {
                                                ?>
                                        <option value="<?php echo esc_attr($key); ?>"
                                                id="<?php echo esc_attr($key); ?>"><?php echo esc_attr($val); ?></option>
                                <?php 
                                        } 
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Additional Services', 'aramex'); ?></label><br/>
                            <select class="aramex_all_options" id="aramex_shipment_info_service_type"
                                    name="aramex_shipment_info_service_type">
                                <option value=""></option>
                                <?php
                                if (count($allowed_international_additional_services) > 0) {
                                    foreach ($allowed_international_additional_services as $key => $val) {
                                        $setSelected = false;
                                        if(array_key_exists($key,$allowed_international_additional_service)){
                                            $setSelected = true;
                                        }
                                        ?>
                                        <option 
                                                <?php
                                                    if($setSelected) {
                                                          echo 'selected="selected"';
                                                      }
                                                      else {
                                                          echo '';
                                                      }
                                                ?>
                                                value="<?php echo esc_attr($key); ?>"
                                                id="exp_as_<?php echo esc_attr($key); ?>"
                                                class="non-local "><?php echo esc_attr($val); ?></option>
                                        <?php

                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment Type', 'aramex'); ?></label><br/>
                            <select class="aramex_all_options" id="aramex_shipment_info_payment_type"
                                    name="aramex_shipment_info_payment_type">
                                <option value="P"><?php echo esc_html__('Prepaid', 'aramex'); ?></option>
                                <option value="C"><?php echo esc_html__('Collect', 'aramex'); ?></option>
                                <option value="3"><?php echo esc_html__('Third Party', 'aramex'); ?></option>
                            </select>
                            <div id="aramex_shipment_info_service_type_div" style="display: none;"></div>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Payment Option', 'aramex'); ?></label><br/>
                            <select class="" id="aramex_shipment_info_payment_option"
                                    name="aramex_shipment_info_payment_option">
                                <option value=""></option>
                                <option id="ASCC" value="ASCC"
                                        style="display: none;"><?php echo esc_html__('Needs Shipper Account Number to be filled',
                                        'aramex'); ?>
                                </option>
                                <option id="ARCC" value="ARCC"
                                        style="display: none;"><?php echo esc_html__('Needs Consignee Account Number to be filled',
                                        'aramex'); ?>
                                </option>
                                <option id="CASH" value="CASH"><?php echo esc_html__('Cash', 'aramex'); ?></option>
                                <option id="ACCT" value="ACCT"><?php echo esc_html__('Account', 'aramex'); ?></option>
                                <option id="PPST" value="PPST"><?php echo esc_html__('Prepaid Stock',
                                        'aramex'); ?></option>
                                <option id="CRDT" value="CRDT"><?php echo esc_html__('Credit', 'aramex'); ?></option>
                            </select>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Custom Amount', 'aramex'); ?></label><br/>
                            <input class="" type="text" id="aramex_shipment_info_custom_amount"
                                   name="aramex_shipment_info_custom_amount" value=""/>
                        </div>
                        <div class="text_short">
                            <label><?php echo esc_html__('Currency', 'aramex'); ?></label><br/>
                            <input type="text" class="" id="aramex_shipment_currency_code"
                                   name="aramex_shipment_currency_code"
                                   value="<?php echo esc_attr(get_woocommerce_currency()) ?>"/>
                        </div>
                        <div class="aramex_clearer"></div>
                    </FIELDSET>
                </div>
                <div class="aramex_clearer"></div>
                <div class="aramex_result"></div>
                <div class="aramex_clearer"></div>
                <input name="aramex_return_shipment_creation_date" type="hidden" value="create"/>
                <div class="aramex_loader"
                     style="background-image: url(<?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/assets/img/preloader.gif'); ?>); height:60px; margin:10px 0; background-position-x: center; display:none; background-repeat: no-repeat; ">
                </div>
                <div style="float: right;font-size: 11px;margin-bottom: 10px;width: 184px;">
                    <input style="float: left; width: auto; height:16px; display:block;" type="checkbox"
                           name="aramex_email_customer" value="yes"/>
                    <span style="float: left; margin-top: -2px;"><?php echo esc_html__('Notify customer by email',
                            'aramex'); ?></span>
                </div>
            </form>
            <button id="aramex_shipment_creation_submit_id" type="button" class="primary  button-primary "
                    name="aramex_shipment_creation_submit"><?php echo esc_html__('Create
                Bulk Shipment', 'aramex'); ?>
            </button>
            <button class="aramexclose primary  button-primary " type="button "><?php echo esc_html__('Close',
                    'aramex'); ?></button>
        </div>
    </div>
    <script type="text/javascript">
        jQuery.noConflict();
        (function ($) {
            $(document).ready(function () {
				$('.page-title-action').first().after("<a class=' page-title-action' style='margin-left:15px;' id='create_aramex_shipment'><?php echo esc_html__('Bulk Aramex Shipment',
                    'aramex'); ?> </a>");    
            });
            $(document).ready(function () {
                
                $("#create_aramex_shipment").click(function () {
                    $(".aramex_loader").css("display","none");
                    $(".order_in_background").fadeIn(500);
                    $(".aramex_bulk").fadeIn(500);
                });

                $("#aramex_shipment_creation_submit_id").click(function () {
                    aramexsend();
                });

                $(".aramexclose").click(function () {
                    aramexclose();
                });

                $('.baulk_aramex_shipment').click(function () {
                    aramexmass();
                });
            });

            function aramexclose() {
                $(".order_in_background").fadeOut(500);
                $(".aramex_bulk").fadeOut(500);
            }

            function aramexredirect() {
                window.location.reload(true);
            }

            function aramexsend() {
                var selected = [];
                var str = $("#massform").serialize();
                $('.type-shop_order input:checked').each(function () {
                    selected.push($(this).val());
                });
                if (selected.length === 0) {
                    alert("<?php echo esc_html__('Select orders, please', 'aramex'); ?>");
                    $('.aramex_loader').css("display","none");
                    
                    
                }else{
                   $('.popup-loading').css('display', 'block');
                    $('.aramex_loader').css('display', 'block');


                    var _wpnonce = "<?php echo esc_js(wp_create_nonce('aramex-shipment-nonce' . wp_get_current_user()->user_email)); ?>";
                    var currentForm = $("#massform").serializeArray();
                    var currentFormObject = {};
                    $.each(currentForm,
                    function(i, v) {
                        currentFormObject[v.name] = v.value;
                    });
                      
            var postData = {
			action: 'the_aramex_bulk',
			bulk: "bulk",
            selectedOrders: selected, 
            str: currentFormObject,
            _wpnonce :  _wpnonce

		};
               
		jQuery.post(ajaxurl, postData, function(request) {
                        var rr = JSON.parse(request);
                        $('.popup-loading').css('display', 'none');
                        $('.aramex_loader').css('display', 'none');
                        $(".aramex_result").empty().css('disp  lay', 'none');
                        $(".order_in_background").fadeIn(500);
                        $(".aramex_bulk").fadeIn(500);
                        $(".aramex_result").css("display", "block");
                        $(".aramex_result").append(rr.message);
                        $(".aramexclose").click(function () {
                            aramexredirect();
                        });                    
        });       
    
                }
                
                
            }
        })(jQuery);
    </script>
<?php 
} ?>