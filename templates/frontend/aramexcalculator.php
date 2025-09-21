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
         *  Render "Calculator" form
         * 
         * @param $data array Info
         * @return string Template
         */
function aramex_display_aramexcalculator_in_frontend($data = array())
{
    if ($data['aramexcalculator'] == 1) {
        ?>
        <button data-popup-open="popup-1" type="button" style="margin-top:10px; margin-bottom:10px;"
                title="Check Aramex Shipping Rate"
                class="button btn-cart aramexcalculator"
        >
            <span><span><?php echo esc_html__('Check Shipping rate. Details', 'aramex'); ?></span></span>
        </button>
        <?php wp_nonce_field('my-nonce'); ?>
        <div class="aramex_popup" style="display:none;" data-popup="popup-1">
            <div class="aramex_popup-inner">
                <form method="post" class="form-horizontal" action="" autocomplete="off" >
                    <h2 style="color: #EA7601;"><?php echo esc_html__('Check Shipping rate. Details:', 'aramex'); ?></h2>
                    <h3><?php echo esc_html__('Shipment Destination', 'aramex'); ?></h3>
                    <div class="form-group"></div>
                    <div class="form-group">
                        <label for="destination_country"
                               class=" col-sm-3 control-label"><?php echo esc_html__('Country', 'aramex'); ?></label>
                        <div class="col-sm-9">
                            <select name="destination_country" class="form-control" id="destination_country">
                                <?php if (count($data['countries']) > 0): ?>
                                    <?php foreach ($data['countries'] as $key => $country):
                                        ?>
                                        <option value="<?php echo $key ?>" <?php
                                        if (isset($data['customer_country'])) {
                                            echo ($data['customer_country'] == $key) ? 'selected="selected"' : '';
                                        } ?>>
                                            <?php echo $country ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo esc_html__('City', 'aramex'); ?></label>
                        <div class="col-sm-9">
                            <input name="destination_city" class="form-control"  id="destination_city" 
                                   value="<?php echo esc_attr(((isset($data['customer_city'])) ? $data['customer_city'] : '')); ?>"/>
                        </div>
                        <div id="destination_city_loading_autocomplete" class="loading_autocomplete"
                             style="display:none;">
                            <img style="height:30px; margin-left:125px;"
                                 src="<?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/assets/img/aramex_loader.gif'); ?>"
                                 alt="<?php echo esc_html__('Loading cities...', 'aramex'); ?>"
                                 title="<?php echo esc_html__('Loading cities...', 'aramex'); ?>"
                                 class="v-middle"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="field fl width-270">
                            <label class="col-sm-3 control-label"><?php echo esc_html__('Zip code',
                                    'aramex'); ?></label>
                            <div class="col-sm-9">
                                <input name="destination_zipcode" class="form-control" id="destination_zipcode"
                                       value="<?php echo esc_html((isset($data['customer_postcode'])) ? $data['customer_postcode'] : ''); ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class = "form-group">
                        <p class="your-aramex-address">Your address details must be entered in order to get shipping calculation.</p>
                    </div>
                    <div class="aramex_field aramex_result_block">
                        <h3 style="display:none; color: #EA7601;"><?php echo esc_html__('Result', 'aramex'); ?></h3>
                        <div class="aramex_result mar-10">
                        </div>
                        <span class="aramex-please-wait" id="payment-please-wait" style="display:none;">
                        <img src="<?php echo esc_url(plugins_url() . '/aramex-shipping-woocommerce/assets/img/preloader.gif'); ?>"
                             alt="<?php echo esc_html__('Please wait...', 'aramex'); ?>"
                             title="<?php echo esc_html__('Please wait...', 'aramex'); ?>"
                             class="v-middle"/> <?php echo esc_html__('Please wait...', 'aramex'); ?>
                    </span>
                    </div>
                    <div class="form-group">
                        <button name="aramex_calc_rate_submit" class="btn-default" type="button"
                                id="aramex_calc_rate_submit"
                                onclick="sendAramexRequest('<?php echo esc_html($data['product_id']); ?>')"><?php echo esc_html__('Calculate',
                                'aramex'); ?>
                        </button>
                    </div>
                </form>
                <a class="aramex_popup-close" data-popup-close="popup-1" href="#">x</a>
            </div>
        </div>
        <?php
        $ajax_nonce = wp_create_nonce("aramexcalculator");
        $ajax_nonce_serchautocities = wp_create_nonce("serchautocities"); ?>
        <script>
            jQuery.noConflict();

            function sendAramexRequest() {

                var chk_city = jQuery('#destination_city').val();
                var chk_postcode = jQuery('#destination_zipcode').val();
                var country_code = jQuery("#destination_country").val();
                var currency = "<?php echo esc_html($data['currency']); ?>";
                var product_id = "<?php  echo esc_html($data['product_id']); ?>";
                var system_base_url = "<?php echo esc_url(plugins_url()); ?>";
                var current_product_quantity = jQuery('.cart .quantity input').val();
                jQuery('.aramex_result_block h3').css("display", "none");
                jQuery('.aramex-please-wait').css("display", "block");
                jQuery('.aramex_result').css("display", "none");

                    var postData = {
                        action: 'the_aramex_calculator',
                        security: '<?php echo $ajax_nonce; ?>',
                        city: chk_city,
                        post_code: chk_postcode,
                        country_code: country_code,
                        product_id: product_id,
                        currency: currency,
                        current_product_quantity: current_product_quantity,
                    };

                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', postData, function(result) {
                        var message = "";
                        var response = jQuery.parseJSON(result);
                        if (response.error) {
                            message = "<p style='color:red;'>" + response.error + "</p>";
                        } else {
                             var error = true;
                                jQuery.each(response, function (index, value) {
                                    if (typeof value.label != 'undefined') {
                                       error = false;   
                                       message = message + "<p style='color: rgb(234, 118, 1);'>" + value.label + ": " + value.amount + " " + value.currency + "</p>";
                                    }
                                });
                            if(error === true){
                            jQuery.each(response, function (index, value) {
                                message = "<p style='color:red;'>" + value + "</p>";
                            });
                        }
                        }
                            
                        jQuery('.aramex_result_block h3').css("display", "block");
                        jQuery('.aramex_result').css("display", "block").html(message);
                        jQuery('.aramex-please-wait').css("display", "none");
                    });    

            }

            (function ($) {
                $(document).ready(function () {
                    $(function () {
                        //----- OPEN
                        $('[data-popup-open]').on('click', function (e) {
                            var targeted_popup_class = $(this).attr('data-popup-open');
                            $('[data-popup="' + targeted_popup_class + '"]').fadeIn(350);
                            e.preventDefault();
                        });

                        //----- CLOSE
                        $('[data-popup-close]').on('click', function (e) {
                            var targeted_popup_class = $(this).attr('data-popup-close');
                            $('[data-popup="' + targeted_popup_class + '"]').fadeOut(350);
                            e.preventDefault();
                        });
                    });

                    var billingAramexCitiesObj;
                    var billing_aramex_cities_temp;
                    var billing_aramex_cities;
                    billingAramexCitiesObj = AutoSearchControls('destination_city', billing_aramex_cities);
                    $("select[name='destination_country']").change(function () {
                        getAllCitiesJson('destination_country');
                    });
                    getAllCitiesJson('destination_country');

                    function AutoSearchControls(type, search_city) {
                        return $('input[name="' + type + '"]')
                            .autocomplete({
                                /*source: search_city,*/
                                minLength: 3,
                                scroll: true,
                                source: function (req, responseFn) {
                                    var re = $.ui.autocomplete.escapeRegex(req.term);
                                    var matcher = new RegExp("^" + re, "i");
                                    var a = $.grep(search_city, function (item, index) {
                                        return matcher.test(item);
                                    });
                                    responseFn(a);
                                },
                                search: function (event, ui) {
                                    $(".ui-autocomplete").css("display", "none");

                                    /* open initializer */
                                    forceDisableNext(type);
                                },
                                response: function (event, ui) {
                                    /* open initializer */
                                    $('#' + type + '_loading_autocomplete').css("display", "none");
                                }
                            }).focus(function () {
                                $(this).autocomplete("search", "");
                            });
                    }

                    function forceDisableNext(type) {
                        $('#' + type + '_loading_autocomplete').show();
                    }

                    function getAllCitiesJson(type) {
                        var system_base_url = "<?php echo(plugins_url()) ?>";
                        var country_code = $("select[name='" + type + "']").val();
                        var url_check = "<?php echo admin_url('admin-ajax.php'); ?>?country_code=" + country_code + "&security=<?php echo esc_html($ajax_nonce_serchautocities); ?>"  + "&action=the_aramex_searchautocities";
                        billing_aramex_cities_temp = '';
                        billingAramexCitiesObj.autocomplete("option", "source", url_check);
                    }
                });
            })(jQuery);
        </script>


        <style>
            .ui-autocomplete {
                z-index: 99999999;
            }

            .content {
                max-width: 800px;
                width: 100%;
                margin: 0px auto;
                margin-bottom: 60px;
            }

            /* Outer */
            .aramex_popup {
                width: 100%;
                height: 100%;
                display: none;
                position: fixed;
                top: 0px;
                left: 0px;
                background: rgba(0, 0, 0, 0.75);
                z-index: 9999;
            }

            /* Inner */
            .aramex_popup-inner {
                padding: 40px;
                position: absolute;
                margin-top:50px;
                left: 0; 
                right: 0; 
                margin-left: auto; 
                margin-right: auto; 
                width: 500px; 
                box-shadow: 0px 2px 6px rgba(0, 0, 0, 1);
                border-radius: 3px;
                background: #fff;
            }

            /* Close Button */
            .aramex_popup-close {
                width: 30px;
                height: 30px;
                padding-top: 4px;
                display: inline-block;
                position: absolute;
                top: 0px;
                right: 0px;
                -webkit-transform: translate(50%, -50%);
                transform: translate(50%, -50%);
                border-radius: 1000px;
                background: rgba(0, 0, 0, 0.8);
                font-family: Arial, Sans-Serif;
                font-size: 20px;
                text-align: center;
                line-height: 100%;
                color: #fff;
            }

            .aramex_popup-close:hover {
                -webkit-transform: translate(50%, -50%) rotate(180deg);
                transform: translate(50%, -50%) rotate(180deg);
                background: rgba(0, 0, 0, 1);
                text-decoration: none;
            }

            .aramex_popup .aramex_field {
                padding: 10px;
            }

            .aramex_popup select {
                padding: 5px;
            }

            .aramex_popup-inner button, .aramex_popup-inner input, .aramex_popup-inner select, .aramex_popup-inner table, .aramex_popup-inner textarea {
                font-family: Arial !important;
            }
            .your-aramex-address{
                margin-top:20px;
            }

        </style>
    <?php 
    }
} ?>