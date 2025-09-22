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
         *  Render "Validator" form
         *
         * @return string Template
         */
function aramex_display_apilocationvalidator_in_account()
{
    require_once __DIR__ . '/../../includes/shipping/class-aramex-woocommerce-shipping.php';
    $settings = new Aramex_Shipping_Method();
    $allowed = $settings->settings['apilocationvalidator_active'];
    $rate_calculator_checkout_page = $settings->settings['rate_calculator_checkout_page'];
    if ($allowed == 1 && $rate_calculator_checkout_page == 1) {
        $ajax_nonce_serchautocities = wp_create_nonce("serchautocities"); ?>
        <script type="text/javascript">
            jQuery.noConflict();
            (function ($) {
                $(document).ready(function () {
                    var type = 'billing';
                    Go(type);
                    var type = 'shipping';
                    Go(type);

                    function Go(type) {
                        var button = '.woocommerce-address-fields .button';
                        var shippingAramexCitiesObj;
                        /* set HTML blocks */
                        jQuery(".woocommerce-address-fields").find('input[name^= "' + type + '_city"]').after('<div id="aramex_loader" style="height:31px; width:31px; display:none;"></div>');
                        /* get Aramex sities */
                        shippingAramexCitiesObj = AutoSearchControls(type, "");
                        jQuery(".woocommerce-address-fields").find('select[name^= "' + type + '_country"]').change(function () {
                            jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').val("");
                            getAllCitiesJson(type, shippingAramexCitiesObj);
                        });
                        getAllCitiesJson(type, shippingAramexCitiesObj);

                        function AutoSearchControls(type, search_city) {
                            return jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]')
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
                                        jQuery('.woocommerce-address-fields .ui-autocomplete').css('display', 'none');
                                        jQuery('.woocommerce-address-fields #aramex_loader').css('display', 'block');
                                    },
                                    response: function (event, ui) {
                                        var temp_arr = [];
                                        jQuery(ui.content).each(function (i, v) {
                                            temp_arr.push(v.value);
                                        });
                                        jQuery('.woocommerce-address-fields #aramex_loader').css('display', 'none');
                                        return temp_arr;
                                    }
                                });
                        }

                        function getAllCitiesJson(type, aramexCitiesObj) {
                            var country_code = jQuery('.woocommerce-address-fields').find('select[name^= "' + type + '_country"]').val();

                        var url_check = "<?php echo admin_url('admin-ajax.php'); ?>?country_code=" + country_code + "&security=<?php echo esc_html($ajax_nonce_serchautocities); ?>"  + "&action=the_aramex_searchautocities";

                            aramexCitiesObj.autocomplete("option", "source", url_check);
                        }

                        /* make validation */
                        bindIvents(type, button);

                        function bindIvents(type, button) {
                            jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').blur(function () {
                                addressApiValidation(type, button);
                            });

                            jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_address_1"]').blur(function () {
                                addressApiValidation(type, button);
                            });
                            jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_postcode"]').blur(function () {
                                addressApiValidation(type, button);
                            });

                        }

                        function addressApiValidation(type, button) {
                            var chk_city = jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').val();
                            var chk_region_id = jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_address_1"]').val();
                            var chk_postcode = jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_postcode"]').val();
                            var country_code = jQuery('.woocommerce-address-fields').find('select[name^= "' + type + '_country"]').val();
                            if (chk_region_id == '' || chk_city == '' || chk_postcode == '') {
                                return false;
                            } else {
                                jQuery(button).prop("disabled", true);
                                var postData = {
                                        action: 'the_aramex_appyvalidation',
                                        city: chk_city,
                                        post_code: chk_postcode,
                                        country_code: country_code,
                                        security: '<?php echo esc_html($ajax_nonce_serchautocities); ?>',

        };
               
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', postData, function(result) {
                                        var response = JSON.parse(result);
                                        if (!(response.suggestedAddresses) && response.message != '' && response.message !== undefined) {
                                            if (response.message.indexOf("City") != -1) {
                                                if (jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').val() != "") {
                                                    if (response.message !== undefined) {
                                                        alert(response.message);
                                                    }
                                                }
                                                jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').val("");
                                            }
                                            if (response.message.indexOf("zip") != -1) {
                                                if (jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_postcode"]').val() != "") {
                                                    if (response.message !== undefined) {
                                                        alert(response.message);
                                                    }
                                                }
                                                jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_postcode"]').val("");
                                            }
                                        } else if (response.suggestedAddresses) {
                                            jQuery('.woocommerce-address-fields').find('input[name^= "' + type + '_city"]').val("");
                                        }
                                        jQuery(button).prop("disabled", false);
                                                     
        });  

                            }
                        }
                    }
                });
            })(jQuery);
        </script>
        <style>
            #aramex_loader {
                background-image: url(<?php echo plugins_url() . '/aramex-shipping-woocommerce/assets/img/aramex_loader.gif'; ?>);
            }

            .ui-autocomplete {
                max-height: 200px;
                overflow-y: auto;
                /* prevent horizontal scrollbar */
                overflow-x: hidden;
                /* add padding to account for vertical scrollbar */
            }

            .required-aramex:before {
                content: '* ' !important;
                color: #F00 !important;
                font-weight: bold !important;
            }
        </style>
    <?php 
    }
}