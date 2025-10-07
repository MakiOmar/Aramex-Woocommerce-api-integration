<?php
/**
 * MO Aramex Order Meta Box Class
 * 
 * Adds Aramex shipment information to the order edit page
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.31
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_Order_Meta_Box {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_aramex_meta_box'));
    }
    
    /**
     * Add Aramex shipment meta box to order edit page
     */
    public function add_aramex_meta_box() {
        add_meta_box(
            'mo_aramex_shipment_info',
            __('Aramex Shipment Information', 'mo-aramex-shipping'),
            array($this, 'render_meta_box'),
            'shop_order',
            'side',
            'high'
        );
        
        // Also add for HPOS (High-Performance Order Storage)
        add_meta_box(
            'mo_aramex_shipment_info',
            __('Aramex Shipment Information', 'mo-aramex-shipping'),
            array($this, 'render_meta_box'),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }
    
    /**
     * Render the meta box content
     */
    public function render_meta_box($post_or_order) {
        // Get order ID (works for both legacy and HPOS)
        if (is_a($post_or_order, 'WP_Post')) {
            $order_id = $post_or_order->ID;
        } else {
            $order_id = $post_or_order->get_id();
        }
        
        // Get Aramex shipment data
        $awb_no = get_post_meta($order_id, 'aramex_awb_no', true);
        $label_url = get_post_meta($order_id, 'aramex_label_url', true);
        $product_group = get_post_meta($order_id, 'aramex_product_group', true);
        
        // Display the information
        ?>
        <div class="mo-aramex-shipment-info" style="padding: 10px 0;">
            <?php if (!empty($awb_no)): ?>
                <p>
                    <strong><?php esc_html_e('AWB Number:', 'mo-aramex-shipping'); ?></strong><br>
                    <code style="font-size: 14px;"><?php echo esc_html($awb_no); ?></code>
                    <a href="https://www.aramex.com/ae/en/track/results?source=aramex&ShipmentNumber=<?php echo esc_attr($awb_no); ?>" 
                       target="_blank" 
                       class="button button-small" 
                       style="margin-left: 5px;">
                        <?php esc_html_e('Track', 'mo-aramex-shipping'); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($product_group)): ?>
                <p>
                    <strong><?php esc_html_e('Shipment Type:', 'mo-aramex-shipping'); ?></strong><br>
                    <span class="mo-aramex-badge" style="display: inline-block; padding: 3px 8px; background: #2271b1; color: #fff; border-radius: 3px; font-size: 11px;">
                        <?php echo $product_group === 'DOM' ? esc_html__('Domestic', 'mo-aramex-shipping') : esc_html__('International', 'mo-aramex-shipping'); ?>
                    </span>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($label_url)): ?>
                <p>
                    <strong><?php esc_html_e('Shipping Label:', 'mo-aramex-shipping'); ?></strong><br>
                    <a href="<?php echo esc_url($label_url); ?>" 
                       target="_blank" 
                       class="button button-primary" 
                       style="margin-top: 5px;">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Download Label PDF', 'mo-aramex-shipping'); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (empty($awb_no) && empty($label_url)): ?>
                <p style="color: #999; font-style: italic;">
                    <?php esc_html_e('No Aramex shipment created yet.', 'mo-aramex-shipping'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the meta box
new MO_Aramex_Order_Meta_Box();

