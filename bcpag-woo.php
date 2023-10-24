<?php
/*
 * Plugin Name: Gateway Brasil Cash - WooCommerce
 * Plugin URI: https://docs.brasilcash.com.br/bcpag/plugins
 * Description: Receba pagamentos online de forma segura no seu ecommerce com a Brasil Cash.
 * Author: Allex Nogue
 * Author URI: https://nogue.dev
 * Version: 1.0.0
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
            $response = $paymentService->refundTransaction($transaction_id);

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

    if (isset($rqd['model']) && $rqd['model'] == 'transactions') {
        $transaction_id = $rqd['payload']['id'];
        $result = TransactionService::getTransactionById($transaction_id);

        if ($result) {
            $order = wc_get_order($result['order_id']);
            $orderService = new OrderService($order);

            if (in_array($orderService->getPaymentMethod(), [PaymentService::PIX, PaymentService::BOLETO])){
                $orderService->verifyStatus();
            }
        }
    }
}

add_action('woocommerce_update_options', 'registerWebhook');
function registerWebhook() {
    $plugin = new WC_Bcpag_Gateway();
    $plugin->createWebhook();
}



// Adicione um campo de parcelamento na página de edição de produtos
// function bcpag_add_product_installments_field() {
//     global $woocommerce, $post;
    
//     // Verifique se é uma página de edição de produto
//     if (get_post_type($post) == 'product') {
//         // Obtenha o valor atual do campo de parcelamento
//         $enable_installments = get_post_meta($post->ID, '_enable_installments', true);
//         $max_installments = get_post_meta($post->ID, '_max_installments', true);
//         // Adicione campos de opção para habilitar as vendas parceladas e definir o número máximo de parcelas
        
//         echo '<div class="options_group">
//             <h3 style="padding-left: 10px;">Brasil Cash</h3>
//                 <p class="form-field">
//                     <label for="enable_installments">Habilitar Vendas Parceladas</label>
//                     <input type="checkbox" id="enable_installments" name="enable_installments" value="1" ' . checked($enable_installments, 1, false) . '>
//                 </p>
//                 <p class="form-field">
//                     <label for="max_installments">Parcelas:</label>
//                     <input type="number" id="max_installments" min="1" max="12" name="max_installments" value="'. esc_attr($max_installments) . '" step="1">
//                 </p>
//             </div>';

//     }
// }

// add_action('woocommerce_product_options_general_product_data', 'bcpag_add_product_installments_field');

// // Salve o valor do campo de parcelamento quando o produto é salvo
// function bcpag_save_product_installments_field($post_id) {
//     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
//         return $post_id;
    
//     if ($post_id == $_POST['post_ID']) {
//         update_post_meta($post_id, '_enable_installments', isset($_POST['enable_installments']) ? 1 : 0);
//         update_post_meta($post_id, '_max_installments', sanitize_text_field($_POST['max_installments']));
//     }
// }

// add_action('woocommerce_process_product_meta', 'bcpag_save_product_installments_field');
