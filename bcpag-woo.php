<?php
/*
 * Plugin Name: Gateway Brasil Cash - WooCommerce
 * Plugin URI: https://docs.brasilcash.com.br/bcpag/plugins
 * Description: Receba pagamentos online de forma segura no seu ecommerce com a Brasil Cash.
 * Author: Brasil Cash
 * Author URI: https://brasilcash.com.br
 * Version: 1.4.2
 */

use Analog\Analog;
use Bcpag\Includes\Admin\BcpagOrderTransactions;
use Bcpag\Includes\Front\MyCards;
use Bcpag\Includes\Front\ThankyouPage;
use Bcpag\Services\OrderService;
use Bcpag\Services\PaymentService;
use Bcpag\Services\RequestService;
use Bcpag\Services\TransactionService;

require 'vendor/autoload.php';

define('BCPAG_ROOT', plugin_dir_url(__FILE__));


function bcpag_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Bcpag_Gateway';
    return $gateways;
}

add_action('plugins_loaded', 'bcpag_register_gateway', 99);
function bcpag_register_gateway() {
    if (class_exists('WC_Payment_Gateway')) {
        include_once 'src/class-wc-bcpag-gateway.php';
        add_filter('woocommerce_payment_gateways', 'bcpag_add_gateway_class');
    }
}

function migration() {
    try {
        global $wpdb;
        $table_name_transactions = $wpdb->prefix . 'brasilcash_order_transactions';
        $table_name_cards = $wpdb->prefix . 'brasilcash_user_cards';

        $sqlCreateTransactionTable = "CREATE TABLE IF NOT EXISTS $table_name_transactions (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT,
            transaction_id VARCHAR(255),
            transaction_status VARCHAR(255),
            PRIMARY KEY (id)
        )";

        $sqlCreateCardsTable = "CREATE TABLE IF NOT EXISTS $table_name_cards (
            id INT NOT NULL AUTO_INCREMENT,
            customer_id INT,
            card_id VARCHAR(255),
            brand VARCHAR(25),
            last_digits VARCHAR(20),
            PRIMARY KEY (id)
        )";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sqlCreateTransactionTable);
        dbDelta($sqlCreateCardsTable);
    } catch (\Exception $e) {
       
    }
}
register_activation_hook(__FILE__, 'migration');

function bcpag_add_transaction_block_to_order_details($order) {
    $order_id = $order->get_id();
    $block = new BcpagOrderTransactions($order_id);
    $block->build();
}
add_action('woocommerce_admin_order_data_after_billing_address', 'bcpag_add_transaction_block_to_order_details');

add_action('admin_init', 'process_estorno_transaction');
function process_estorno_transaction() {
    if (isset($_GET['action']) && $_GET['action'] == 'refund_transaction') {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';

        if ($order_id && $transaction_id) {
            $plugin = new WC_Bcpag_Gateway();
            $requestService = new RequestService($_GET);
            $orderService = new OrderService(wc_get_order($order_id), $requestService);
            $paymentService = new PaymentService($orderService, $requestService, $plugin->getGateway());
            $response = $paymentService->refundTransaction();

            wp_redirect(admin_url("post.php?post=$order_id&action=edit&refund_message=".$response['response']));
            exit();

        }

        wp_redirect(admin_url("post.php?post=$order_id&action=edit&refund_message=fail"));
        exit();
        
    }
}

add_action('admin_notices', 'display_refund_message');
function display_refund_message() {
    if (isset($_GET['refund_message'])) {
        $message = $_GET['refund_message'];
        
        switch ($message) {
            case 'success':
                echo '<div class="notice notice-success is-dismissible"><p>O estorno foi bem-sucedido.</p></div>';
                break;
            case 'error':
                echo '<div class="notice notice-error is-dismissible"><p>O estorno falhou. Por favor, revise os dados e tente novamente.</p></div>';
                break;
            case 'fail':
                echo '<div class="notice notice-error is-dismissible"><p>O estorno falhou. Entre em contato com a Brasil Cash para obter assistência.</p></div>';
                break;
        }
    }
}

add_action('woocommerce_thankyou', 'customize_thankyou_page', 10, 1);
function customize_thankyou_page($order_id) {
    $thankyouPage = new ThankyouPage(wc_get_order($order_id));
    $thankyouPage->build();

}

add_action('init', 'bcpag_add_endpoint');
function bcpag_add_endpoint() {
    add_rewrite_endpoint('bc-cards', EP_ROOT | EP_PAGES );
}

add_filter ( 'woocommerce_account_menu_items', 'bcpag_menu_my_cards', 40);
function bcpag_menu_my_cards($menu_links){
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'bc-cards' => __('Meus Cartões', 'woo-bcpag-gateway') )
	+ array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

add_action( 'woocommerce_account_bc-cards_endpoint', 'bcpag_my_cards_endpoint_content' );
function bcpag_my_cards_endpoint_content() {
    $myCards = new MyCards();
	$myCards->build();
}

add_action('init', 'bcpag_add_delete_card_endpoint');
function bcpag_add_delete_card_endpoint() {
    add_rewrite_endpoint('delete-card', EP_PAGES);
}

add_action('woocommerce_account_delete-card_endpoint', 'bcpag_delete_card_endpoint_content');
function bcpag_delete_card_endpoint_content() {
    $requestService = new RequestService($_GET);
    $myCards = new MyCards();
	$myCards->delete($requestService);
}

add_action('rest_api_init', 'register_webhook_route');
function register_webhook_route() {
    register_rest_route('bcpag-gateway/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'handle_webhook_request',
    ));
}

function handle_webhook_request($request) {
    $rqd = $request->get_json_params();

    if (isset($rqd['model']) && $rqd['event'] == 'transaction.status_changed') {
        $transaction_id = $rqd['payload']['id'];
        $result = TransactionService::getTransactionById($transaction_id);
        if ($result) {
            $order = wc_get_order($result['order_id']);
            $orderService = new OrderService($order);
            $orderService->verifyStatus();
        }
    }
}

add_action('woocommerce_update_options', 'registerWebhook');
function registerWebhook() {
    $plugin = new WC_Bcpag_Gateway();
    $plugin->createWebhook();
}

add_action('init', 'threeDSecureRedirectTemplate');
function threeDSecureRedirectTemplate() {
    $current_url = isset($_SERVER['REQUEST_URI']) ? esc_url($_SERVER['REQUEST_URI']) : home_url('/');
    if (strpos($current_url, '/bcpag/') !== false) { 

        $matches = [];
        preg_match('/\/bcpag\/threeDSecure\/(\d+)\/(\w+)\/?$/', $current_url, $matches);
        $order_id = isset($matches[1]) ? $matches[1] : 0;
        $action = isset($matches[2]) ? $matches[2] : '';
        
        if (isset($order_id) && in_array($action, ['fail', 'success'])) {
            $order = wc_get_order($order_id);
            
            error_log(json_encode([
                'threeDSecureWebhook' => $action,
                'order' => $order_id,
            ]));
            
            $thankyou_url = $order->get_checkout_order_received_url();

            if ($action === 'success') {
                $message = urlencode("Obrigado por sua compra! Seu pedido foi aprovado com sucesso.");
            } elseif ($action === 'fail') {
                $message = urlencode("Lamentamos, mas a verificação 3DSecure falhou. Seu pedido não pôde ser processado.");
            } 

            $thankyou_url = add_query_arg('custom_thankyou_message', $message, $thankyou_url);
            wp_redirect($thankyou_url);
            exit;
        }
    }
}

add_filter('woocommerce_thankyou_order_received_text', 'custom_thankyou_message', 10, 2);
function custom_thankyou_message($message, $order) {
    if (! $order || ! is_a($order, 'WC_Order')) {
        return $message;
    }

    if (isset($_GET['custom_thankyou_message'])) { 
        $custom_message = sanitize_text_field($_GET['custom_thankyou_message']);
        $message = $custom_message; 
    }else { 
        $message = __( 'Thank you. Your order has been received.', 'woocommerce' );
    }

    return $message;
}