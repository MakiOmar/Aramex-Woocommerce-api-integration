<?php
/**
 * MO Aramex AWB Manager Class
 * 
 * Handles manual AWB (Air Waybill) number management for orders
 *
 * @package MO_Aramex_Shipping
 * @since 1.0.63
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MO_Aramex_AWB_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_mo_aramex_save_awb', array($this, 'ajax_save_awb'));
        add_action('wp_ajax_mo_aramex_delete_awb', array($this, 'ajax_delete_awb'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Debug: Log what page we're on
        error_log('AWB Manager enqueue_scripts called. Hook: ' . $hook);
        
        // Load on all admin pages for now to ensure it works
        // We'll check for order page in a more reliable way
        global $post, $pagenow;
        
        // Check multiple conditions
        $should_load = false;
        
        // Check 1: Traditional order edit page
        if ($pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit') {
            if (isset($post) && $post->post_type === 'shop_order') {
                $should_load = true;
                error_log('AWB Manager: Loading on traditional order edit page');
            }
        }
        
        // Check 2: HPOS order page
        if ($hook === 'woocommerce_page_wc-orders' || $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders') {
            $should_load = true;
            error_log('AWB Manager: Loading on HPOS order page');
        }
        
        // Check 3: Fallback - if meta box is registered, load the script
        if (!$should_load && in_array($hook, array('post.php', 'woocommerce_page_wc-orders'))) {
            global $post_type;
            if (isset($post_type) && $post_type === 'shop_order') {
                $should_load = true;
                error_log('AWB Manager: Loading via post_type check');
            }
        }
        
        if (!$should_load) {
            error_log('AWB Manager: Script NOT loaded. Hook: ' . $hook . ', Pagenow: ' . $pagenow);
            return;
        }
        
        error_log('AWB Manager: Enqueuing script now!');
        
        wp_enqueue_script(
            'mo-aramex-awb-manager',
            MO_ARAMEX_PLUGIN_URL . 'assets/js/awb-manager.js',
            array('jquery'),
            MO_ARAMEX_VERSION,
            true
        );
        
        wp_localize_script('mo-aramex-awb-manager', 'moAramexAwb', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mo_aramex_awb_nonce'),
            'i18n' => array(
                'saving' => __('Saving...', 'mo-aramex-shipping'),
                'saved' => __('AWB saved successfully!', 'mo-aramex-shipping'),
                'error' => __('Error saving AWB. Please try again.', 'mo-aramex-shipping'),
                'confirm_delete' => __('Are you sure you want to delete this AWB number?', 'mo-aramex-shipping'),
                'deleting' => __('Deleting...', 'mo-aramex-shipping'),
                'deleted' => __('AWB deleted successfully!', 'mo-aramex-shipping'),
            )
        ));
        
        // Add inline styles
        wp_add_inline_style('custom_wp_admin_css', '
            .mo-aramex-awb-editor {
                margin-top: 15px;
                padding: 12px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .mo-aramex-awb-form {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .mo-aramex-awb-input-group {
                display: flex;
                gap: 8px;
                align-items: flex-start;
            }
            .mo-aramex-awb-input {
                flex: 1;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
                font-family: monospace;
                font-size: 13px;
            }
            .mo-aramex-awb-actions {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
            }
            .mo-aramex-awb-message {
                padding: 8px;
                border-radius: 3px;
                font-size: 12px;
                display: none;
            }
            .mo-aramex-awb-message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .mo-aramex-awb-message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .mo-aramex-awb-help {
                font-size: 11px;
                color: #666;
                font-style: italic;
            }
            .mo-aramex-current-awb {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }
        ');
    }
    
    /**
     * AJAX handler to save AWB number
     */
    public function ajax_save_awb() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mo_aramex_awb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mo-aramex-shipping')));
        }
        
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'mo-aramex-shipping')));
        }
        
        // Get and validate input
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $awb_number = isset($_POST['awb_number']) ? sanitize_text_field($_POST['awb_number']) : '';
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID.', 'mo-aramex-shipping')));
        }
        
        if (empty($awb_number)) {
            wp_send_json_error(array('message' => __('AWB number cannot be empty.', 'mo-aramex-shipping')));
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'mo-aramex-shipping')));
        }
        
        // Save AWB number using WooCommerce methods
        $old_awb = $order->get_meta('aramex_awb_no', true);
        $order->update_meta_data('aramex_awb_no', $awb_number);
        $order->save();
        
        // Clear order cache to ensure updated AWB is immediately available
        wp_cache_delete($order_id, 'post_meta');
        wp_cache_delete('order-' . $order_id, 'orders');
        clean_post_cache($order_id);
        
        // Add order note
        $note = '';
        if (empty($old_awb)) {
            $note = sprintf(__('AWB number manually set to: %s', 'mo-aramex-shipping'), $awb_number);
        } else {
            $note = sprintf(__('AWB number updated from %s to %s', 'mo-aramex-shipping'), $old_awb, $awb_number);
        }
        
        $order->add_order_note($note);
        
        // Log the action
        if (function_exists('custom_plugin_log')) {
            custom_plugin_log(sprintf(
                'AWB manually set/updated for order #%d by user %s: %s',
                $order_id,
                wp_get_current_user()->user_login,
                $awb_number
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('AWB number saved successfully!', 'mo-aramex-shipping'),
            'awb_number' => $awb_number,
            'track_url' => 'https://www.aramex.com/ae/en/track/results?source=aramex&ShipmentNumber=' . urlencode($awb_number)
        ));
    }
    
    /**
     * AJAX handler to delete AWB number
     */
    public function ajax_delete_awb() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mo_aramex_awb_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mo-aramex-shipping')));
        }
        
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'mo-aramex-shipping')));
        }
        
        // Get and validate input
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID.', 'mo-aramex-shipping')));
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'mo-aramex-shipping')));
        }
        
        // Get old AWB for logging
        $old_awb = $order->get_meta('aramex_awb_no', true);
        
        // Delete AWB number using WooCommerce methods
        $order->delete_meta_data('aramex_awb_no');
        $order->save();
        
        // Clear order cache to ensure deletion is immediately reflected
        wp_cache_delete($order_id, 'post_meta');
        wp_cache_delete('order-' . $order_id, 'orders');
        clean_post_cache($order_id);
        
        // Add order note
        $note = sprintf(__('AWB number removed: %s', 'mo-aramex-shipping'), $old_awb);
        $order->add_order_note($note);
        
        // Log the action
        if (function_exists('custom_plugin_log')) {
            custom_plugin_log(sprintf(
                'AWB manually deleted for order #%d by user %s: %s',
                $order_id,
                wp_get_current_user()->user_login,
                $old_awb
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('AWB number deleted successfully!', 'mo-aramex-shipping')
        ));
    }
    
    /**
     * Render AWB editor form
     *
     * @param int $order_id Order ID
     * @param string $current_awb Current AWB number
     */
    public static function render_awb_editor($order_id, $current_awb = '') {
        ?>
        <div class="mo-aramex-awb-editor" data-order-id="<?php echo esc_attr($order_id); ?>">
            <h4 style="margin: 0 0 10px 0; font-size: 13px;">
                <?php esc_html_e('Manual AWB Management', 'mo-aramex-shipping'); ?>
            </h4>
            
            <div class="mo-aramex-awb-message"></div>
            
            <form class="mo-aramex-awb-form" data-order-id="<?php echo esc_attr($order_id); ?>">
                <div class="mo-aramex-awb-input-group">
                    <input 
                        type="text" 
                        class="mo-aramex-awb-input" 
                        name="awb_number" 
                        placeholder="<?php esc_attr_e('Enter AWB Number', 'mo-aramex-shipping'); ?>"
                        value="<?php echo esc_attr($current_awb); ?>"
                        pattern="[0-9]+"
                        title="<?php esc_attr_e('AWB number should contain only digits', 'mo-aramex-shipping'); ?>"
                    >
                </div>
                
                <div class="mo-aramex-awb-help">
                    <?php esc_html_e('Enter the Aramex Air Waybill (AWB) number for this shipment.', 'mo-aramex-shipping'); ?>
                </div>
                
                <div class="mo-aramex-awb-actions">
                    <button type="submit" class="button button-primary mo-aramex-save-awb">
                        <span class="dashicons dashicons-yes" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Save AWB', 'mo-aramex-shipping'); ?>
                    </button>
                    
                    <?php if (!empty($current_awb)): ?>
                    <button type="button" class="button button-secondary mo-aramex-delete-awb">
                        <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Delete AWB', 'mo-aramex-shipping'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }
}

// Initialize the AWB manager
new MO_Aramex_AWB_Manager();

