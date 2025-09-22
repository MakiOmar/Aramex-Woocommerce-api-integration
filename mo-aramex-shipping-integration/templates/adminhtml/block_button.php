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
         *  Render "Block" button
         *
         * @return string Template
         */
function block_button()
{
    #$aramex_error = WC()->session->get('aramex_error');
}
?>
    <?php 
    $settings = new Aramex_Shipping_Method();
    $rate_calculator_checkout_page = $settings->settings['rate_calculator_checkout_page'];
    if ($rate_calculator_checkout_page == 1) {
        ?>

<script type="text/javascript">
    jQuery.noConflict();
    (function ($) {
        var aramex_error = "<?php echo(esc_js(WC()->session->get('aramex_error'))); ?>";
        var aramex_block = "<?php echo(esc_js(WC()->session->get('aramex_block'))); ?>";
        if(aramex_block !== "1"){
        block_button();
        }
        function block_button() {
            if (aramex_error == true) {
                $("#place_order").prop("disabled", true);
            } else {
                $("#place_order").prop("enable", true);
            }
        }
        $(".woocommerce-error").css("display", "none");

    })(jQuery);
</script>

   <?php 
    }
