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
         *  Render "Traching" form
         *
         * @param $order object Order object
         * @return string Template
         */
function aramex_display_track_in_admin($order)
{
    $get_userdata = get_userdata(get_current_user_id());
    if (!$get_userdata->allcaps['edit_shop_order'] || !$get_userdata->allcaps['read_shop_order'] || !$get_userdata->allcaps['edit_shop_orders'] || !$get_userdata->allcaps['edit_others_shop_orders']
        || !$get_userdata->allcaps['publish_shop_orders'] || !$get_userdata->allcaps['read_private_shop_orders']
        || !$get_userdata->allcaps['edit_private_shop_orders'] || !$get_userdata->allcaps['edit_published_shop_orders']) {
        return false;
    }
    // global $post;
    // $order = new WC_Order($post->ID);
    $order_id = $order->get_id();
    $history = get_comments(array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'approve' => 'approve',
        'type' => 'order_note',
    ));

    $history_list = array();
    foreach ($history as $shipment) {
        $history_list[] = $shipment->comment_content;
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
    <!-- Aramex Tracking -->
    <div id="track_overlay" style="display:none;">
        <div class="track-form" style="display:none;">
            <form method="post" action="" id="track-form">
                <input name="aramex-shipment-nonce" id="aramex-shipment-nonce" type="hidden"
                       value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-nonce')); ?>"/>
                <FIELDSET>
                    <legend style="font-weight:bold; padding:0 5px;"><?php echo esc_html__('Track Aramex Shipment',
                            'aramex'); ?></legend>
                    <input name="_wpnonce" id="aramex-shipment-nonce" type="hidden"
                           value="<?php echo esc_attr(wp_create_nonce('aramex-shipment-check' . wp_get_current_user()->user_email)); ?>"/>
                    <?php if ($last_track != "") {
                                ?>
                        <input name="aramex-track" id="aramex-track-field"
                               value="<?php echo esc_attr($last_track); ?>"/>
                    <?php 
                            } else {
                                ?>
                        <p><?php echo esc_html__('Aramex shipment was not created', 'aramex'); ?></p>
                    <?php 
                            } ?>
                </FIELDSET>
                <div class="aramex_loader"
                     style="background-image: url(<?php echo esc_js(esc_url(plugins_url() . '/aramex-shipping-woocommerce/assets/img/preloader.gif')); ?>); height:60px; margin:10px 0; background-position-x: center; display:none; background-repeat: no-repeat; ">
                </div>
                <div class="track-result mar-10" style="display:none;">
                    <h3><?php echo esc_html__('Result', 'aramex'); ?></h3>
                    <div class="result mar-10"></div>
                </div>
                <button id="aramex_track_submit_id" type="button" name="aramex_track_submit" class="button-primary">
                    <?php echo esc_html__('Track Shipment', 'aramex'); ?>
                </button>
                <button id="track_close" class="button-primary" type="button"><?php echo esc_html__('Close',
                        'aramex'); ?></button>
                <script type="text/javascript">
                    jQuery.noConflict();
                    (function ($) {
                        $(document).ready(function () {
                            $('#track_aramex_shipment').click(function () {
                                $('.track-result').css("display", "none");
                                $('#track_overlay').css("display", "block");
                                $('.track-form').css("display", "block");
                            });
                            $('#aramex_track_submit_id').click(function () {
                                myObj.track();
                            });
                            $('#track_close').click(function () {
                                $('#track_overlay').css("display", "none");
                            });
                        });
                    })(jQuery);
                </script>
            </form>
        </div>
    </div>
<?php 
} ?>