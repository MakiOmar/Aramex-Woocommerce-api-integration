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

jQuery.noConflict();
(function ($) {
    jQuery(document).ready(function ($) {
        // $( "#aramex_overlay" ).insertBefore( $(  "#post" ));
        $("#create_aramex_shipment").insertBefore($("#order_data"));
        $("#create_aramex_shipment").css("display", "inline-block");
        $("#track_aramex_shipment").insertBefore($("#order_data"));
        $("#track_aramex_shipment").css("display", "inline-block");
        $("#print_aramex_shipment").insertBefore($("#order_data"));
        $("#print_aramex_shipment").css("display", "inline-block");

        function aramexpop() {
            $("#aramex_overlay").css("display", "block");
            $("#aramex_shipment").css("display", "block");
            $("#aramex_shipment_creation").fadeIn(1000);
        }

        $("#create_aramex_shipment").click(function () {
            aramexpop();
        });
        $("#aramex_close").click(function () {
            aramex_close();
        });

         $('#woocommerce_aramex_taxidvat').on('input', function () {
            var inputValue = $(this).val();
            var sanitizedValue = inputValue.replace(/[^a-zA-Z0-9]/g, '');
            var limitedValue = sanitizedValue.slice(0, 15);
            $(this).val(limitedValue);
        });
    });

    function aramex_close() {
        $("#aramex_shipment").css("display", "none");
        $("#aramex_overlay").css("display", "none");
    }
})(jQuery);