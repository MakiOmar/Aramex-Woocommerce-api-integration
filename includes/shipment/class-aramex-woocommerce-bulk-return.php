<?php
/**
 * Aramex Bulk Return Shipment Class
 *
 * @package MO_Aramex_Shipping
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Aramex_Bulk_Return_Method
 */
class Aramex_Bulk_Return_Method
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_the_aramex_bulk_return', array($this, 'run'));
    }

    /**
     * Run the bulk return shipment process
     */
    public function run()
    {
        try {
            custom_plugin_log('=== BULK RETURN SHIPMENT REQUEST STARTED ===');
            
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'aramex-shipment-check' . wp_get_current_user()->user_email)) {
                custom_plugin_log('Nonce verification failed');
                echo json_encode(['error' => 'Security check failed']);
                wp_die();
            }

            // Get selected orders
            $selected_orders = isset($_POST['selected_orders']) ? sanitize_text_field(wp_unslash($_POST['selected_orders'])) : '';
            
            if (empty($selected_orders)) {
                echo json_encode(['error' => 'No orders selected']);
                wp_die();
            }

            $order_ids = array_map('intval', explode(',', $selected_orders));
            custom_plugin_log('Processing return shipment for orders: ' . implode(', ', $order_ids));

            $success_ids = [];
            $failed_ids = [];
            $messages = [];

            foreach ($order_ids as $order_id) {
                try {
                    $result = $this->createReturnPickup($order_id);
                    
                    if ($result['success']) {
                        $success_ids[] = $order_id;
                        $messages[] = "Order #{$order_id}: {$result['message']}";
                    } else {
                        $failed_ids[] = $order_id;
                        $messages[] = "Order #{$order_id}: {$result['error']}";
                    }
                } catch (Exception $e) {
                    $failed_ids[] = $order_id;
                    $messages[] = "Order #{$order_id}: " . $e->getMessage();
                    custom_plugin_log("Return shipment error for order {$order_id}: " . $e->getMessage());
                }
            }

            echo json_encode([
                'success_ids' => $success_ids,
                'failed_ids' => $failed_ids,
                'messages' => $messages
            ]);

        } catch (Exception $e) {
            custom_plugin_log('Bulk return shipment exception: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }

        wp_die();
    }

    /**
     * Create return pickup for a single order
     *
     * @param int $order_id Order ID
     * @return array Result array with success status and message
     */
    private function createReturnPickup($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        // Get AWB number from order meta
        $awb_number = $order->get_meta('aramex_awb_no', true);
        
        if (empty($awb_number)) {
            // Fallback: try to get from order comments
            $comments = $order->get_customer_order_notes();
            foreach ($comments as $comment) {
                if (strpos($comment->comment_content, 'Aramex Shipment Number:') !== false) {
                    preg_match('/Aramex Shipment Number:\s*(\d+)/', $comment->comment_content, $matches);
                    if (isset($matches[1])) {
                        $awb_number = $matches[1];
                        break;
                    }
                }
            }
        }
        
        if (empty($awb_number)) {
            return ['success' => false, 'error' => 'No AWB number found for this order'];
        }

        // Get product group from order meta
        $product_group = $order->get_meta('aramex_product_group', true);
        if (empty($product_group)) {
            $product_group = 'DOM'; // Default to domestic
        }

        // Get client info and settings
        $clientInfo = MO_Aramex_Helper::getRestClientInfo();
        // Add Source parameter for CreatePickup API (Source: 24 for pickup operations)
        $clientInfo['Source'] = 24;
        
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        $helper_info = MO_Aramex_Helper::getInfo($nonce);
        
        $debug_info = [
            'order_id' => $order_id,
            'awb_number' => $awb_number,
            'product_group' => $product_group,
            'customer' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'pickup_address' => $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_country(),
            'store_account' => $clientInfo['AccountNumber'] . ' (' . $clientInfo['AccountEntity'] . ')',
            'sandbox_mode' => isset($helper_info['sandbox_flag']) && (string)$helper_info['sandbox_flag'] === '1' ? 'YES' : 'NO',
            'sandbox_flag_value' => $helper_info['sandbox_flag'] ?? 'NOT SET',
            'client_info' => $clientInfo
        ];
        
        mo_aramex_log_api_call('CreatePickup (Return) - Debug', 0, json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Get pickup location and comments from POST
        $pickup_location = isset($_POST['pickup_location']) ? sanitize_text_field(wp_unslash($_POST['pickup_location'])) : 'Home';
        $pickup_comments = isset($_POST['pickup_comments']) ? sanitize_textarea_field(wp_unslash($_POST['pickup_comments'])) : '';
        $number_of_pieces = isset($_POST['number_of_pieces']) ? intval($_POST['number_of_pieces']) : 1;

        // Calculate pickup times
        $pickup_datetime = time();
        $ready_time = $pickup_datetime;
        $last_pickup_time = $pickup_datetime + (4 * 3600); // 4 hours later
        $closing_time = $pickup_datetime + (4 * 3600);

        // Format dates as /Date(ms+offset)/
        $timezone_offset = get_option('gmt_offset') * 3600;
        $offset_hours = sprintf('%+03d00', get_option('gmt_offset'));
        
        $pickup_date_formatted = '/Date(' . ($pickup_datetime * 1000) . $offset_hours . ')/';
        $ready_time_formatted = '/Date(' . ($ready_time * 1000) . $offset_hours . ')/';
        $last_pickup_formatted = '/Date(' . ($last_pickup_time * 1000) . $offset_hours . ')/';
        $closing_time_formatted = '/Date(' . ($closing_time * 1000) . $offset_hours . ')/';

        // Build pickup request payload
        // For return shipments:
        // - PickupAddress = Customer's address (where we pick up from)
        // - PickupContact = Customer's contact info
        // - The return destination (shipper/receiver) is implied by the ExistingShipments and will be the store's address from settings
        $payload = [
            'Pickup' => [
                'PickupAddress' => [
                    'Line1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
                    'Line2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
                    'Line3' => null,
                    'City' => $order->get_shipping_city() ?: $order->get_billing_city(),
                    'StateOrProvinceCode' => $order->get_shipping_state() ?: $order->get_billing_state(),
                    'PostCode' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
                    'CountryCode' => $order->get_shipping_country() ?: $order->get_billing_country(),
                    'Longitude' => 0.0,
                    'Latitude' => 0.0,
                    'BuildingNumber' => null,
                    'BuildingName' => null,
                    'Floor' => null,
                    'Apartment' => null,
                    'POBox' => null,
                    'Description' => null
                ],
                'PickupContact' => [
                    'Department' => null,
                    'PersonName' => trim(($order->get_shipping_first_name() ?: $order->get_billing_first_name()) . ' ' . ($order->get_shipping_last_name() ?: $order->get_billing_last_name())),
                    'Title' => null,
                    'CompanyName' => $order->get_shipping_company() ?: $order->get_billing_company(),
                    'PhoneNumber1' => $order->get_billing_phone(),
                    'PhoneNumber1Ext' => null,
                    'PhoneNumber2' => null,
                    'PhoneNumber2Ext' => null,
                    'FaxNumber' => null,
                    'CellPhone' => $order->get_billing_phone(),
                    'EmailAddress' => $order->get_billing_email(),
                    'Type' => null
                ],
                'PickupLocation' => $pickup_location,
                'PickupDate' => $pickup_date_formatted,
                'ReadyTime' => $ready_time_formatted,
                'LastPickupTime' => $last_pickup_formatted,
                'ClosingTime' => $closing_time_formatted,
                'Comments' => $pickup_comments,
                'Reference1' => (string)$order_id,
                'Reference2' => null,
                'Vehicle' => null,
                'Shipments' => null,
                'Consignee' => [
                    'AccountNumber' => $clientInfo['AccountNumber'] // Add store's account number in consignee
                ],
                'PickupItems' => [
                    [
                        'ProductGroup' => $product_group,
                        'ProductType' => 'RTC', // Always use RTC for return pickups
                        'NumberOfShipments' => 1,
                        'PackageType' => null,
                        'PaymentType' => 'C', // PaymentType set as "C"
                        'ShipmentWeight' => null,
                        'ShipmentVolume' => null,
                        'NumberOfPieces' => $number_of_pieces,
                        'CashAmount' => null,
                        'ExtraCharges' => null,
                        'ShipmentDimensions' => null,
                        'Comments' => "Return for order #" . $order_id
                    ]
                ],
                'Status' => 'Ready',
                'ExistingShipments' => [
                    [
                        'Number' => $awb_number,
                        'OriginEntity' => $clientInfo['AccountEntity'],
                        'ProductGroup' => $product_group
                    ]
                ],
                'Branch' => null,
                'RouteCode' => null,
                'Dispatcher' => 0
            ],
            'LabelInfo' => null,
            'ClientInfo' => $clientInfo,
            'Transaction' => null
        ];

        // Determine endpoint based on sandbox mode (reuse $helper_info from earlier)
        // sandbox_flag is stored as '1' for yes, '0' for no
        $settings = get_option('woocommerce_mo-aramex_settings', []);
        $is_sandbox = isset($settings['sandbox_flag']) && (string)$settings['sandbox_flag'] === '1';
        
        mo_aramex_log_api_call('CreatePickup (Return) - Sandbox Check', 0, json_encode([
            'helper_info_sandbox_flag' => $helper_info['sandbox_flag'] ?? 'NOT SET',
            'direct_settings_sandbox_flag' => $settings['sandbox_flag'] ?? 'NOT SET',
            'is_sandbox_result' => $is_sandbox ? 'TRUE' : 'FALSE'
        ], JSON_PRETTY_PRINT));
        
        $endpoint = $is_sandbox 
            ? 'https://ws.sbx.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreatePickup'
            : 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreatePickup';
        
        // Log the full request
        mo_aramex_log_api_call('CreatePickup (Return)', 0, json_encode($payload), [
            'endpoint' => $endpoint,
            'json_payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);

        // Make API call
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($payload),
            'timeout' => 45,
            'sslverify' => false
        ]);

        // Handle response
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            custom_plugin_log('Return pickup API error: ' . $error_message);
            mo_aramex_log_api_error('CreatePickup (Return)', $error_message, 0, ['order_id' => $order_id]);
            return ['success' => false, 'error' => $error_message];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        custom_plugin_log('Return pickup API response (HTTP ' . $http_code . '): ' . $body);
        
        mo_aramex_log_api_response('CreatePickup (Return)', $http_code, 0, $body);

        if ($http_code !== 200) {
            return ['success' => false, 'error' => "HTTP {$http_code}: " . $body];
        }

        $json = json_decode($body);

        if (!$json) {
            return ['success' => false, 'error' => 'Invalid JSON response'];
        }

        // Check for errors in response
        if (isset($json->HasErrors) && $json->HasErrors) {
            $error_msg = 'API returned errors';
            if (isset($json->Notifications) && is_array($json->Notifications)) {
                $errors = [];
                foreach ($json->Notifications as $notification) {
                    if (isset($notification->Message)) {
                        $errors[] = $notification->Message;
                    }
                }
                if (!empty($errors)) {
                    $error_msg = implode(', ', $errors);
                }
            }
            return ['success' => false, 'error' => $error_msg];
        }

        // Extract pickup GUID
        $pickup_guid = isset($json->GUID) ? $json->GUID : '';
        $pickup_id = isset($json->ID) ? $json->ID : '';
        
        if (empty($pickup_guid) && empty($pickup_id)) {
            return ['success' => false, 'error' => 'No pickup GUID or ID returned'];
        }

        // Store pickup info as order meta
        $order->update_meta_data('aramex_return_pickup_guid', $pickup_guid);
        $order->update_meta_data('aramex_return_pickup_id', $pickup_id);
        $order->update_meta_data('aramex_return_pickup_date', current_time('mysql'));
        $order->save();

        // Add order note
        $note = sprintf(
            'Aramex return pickup created. GUID: %s, ID: %s, AWB: %s',
            $pickup_guid,
            $pickup_id,
            $awb_number
        );
        $order->add_order_note($note);

        custom_plugin_log("Return pickup created successfully for order {$order_id}. GUID: {$pickup_guid}, ID: {$pickup_id}");

        return [
            'success' => true,
            'message' => "Return pickup created (GUID: {$pickup_guid})"
        ];
    }
}

new Aramex_Bulk_Return_Method();

