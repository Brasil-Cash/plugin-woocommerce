<?php

use Bcpag\Gateway\Enum\TransactionStatusEnum;
use Bcpag\Includes\Admin\BcpagAdmin;
use Bcpag\Includes\Front\CheckoutForm;
use Bcpag\Services\OrderService;
use Bcpag\Services\PaymentService;
use Bcpag\Services\RequestService;
use Bcpag\Services\ValidationService;
use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Gateway;
use Bcpag\Gateway\Resource\Webhooks;

class WC_Bcpag_Gateway extends WC_Payment_Gateway
{
    CONST ID = "bcpag";
    CONST ICON = "https://cdn.bcpag.com.br/bcpag/Logo-Brasil-Cash.png";
    CONST TITLE = "Gateway Brasil Cash";
    CONST DESCRIPTION = "Receba pagamentos online de forma segura no seu ecommerce.";

    protected Gateway $gateway;

    protected $testmode;
    protected $display_erros;
    protected $private_key;
    protected $enable_credit_card;
    protected $enable_installments;
    protected $enable_capture;
    protected $enable_pix;
    protected $pix_time_life;
    protected $pix_tax;
    protected $enable_boleto;
    protected $boleto_tax;
    protected $descriptor;
    protected $installment_percentage;
    protected $useThreeDSecure = false;
    protected $useThreeDSecure_onFailure = 'decline';


    public function __construct()
    {

        $this->id = self::ID; 
        $this->icon = self::ICON;
        $this->has_fields = true;
        $this->method_title = self::TITLE;
        $this->method_description = self::DESCRIPTION;

        $this->supports = array(
            'products'
        );

        $this->init_form_fields();

        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->display_erros = 'yes' === $this->get_option('display_erros');
        $this->private_key = $this->get_option('private_key');
        $this->enable_credit_card = 'yes' === $this->get_option('enable_credit_card');
        $this->enable_installments = 'yes' === $this->get_option('enable_installments');
        $this->enable_capture = $this->get_option('enable_capture');
        $this->enable_pix = 'yes' === $this->get_option('enable_pix');
        $this->pix_tax = $this->get_option('pix_tax');
        $this->pix_time_life = $this->get_option('pix_time_life');
        $this->enable_boleto = 'yes' === $this->get_option('enable_boleto');
        $this->boleto_tax = $this->get_option('boleto_tax');
        $this->descriptor = $this->get_option('descriptor');
        $this->installment_percentage = $this->get_option('installment_percentage');
        $this->useThreeDSecure = 'yes' === $this->get_option('enable_threeDSecure');
        $this->useThreeDSecure_onFailure = $this->get_option('threeDSecure_onFailure');

        $options = [
            'enabled' => $this->enabled,
            'testmode' => $this->testmode,
            'display_erros' => $this->display_erros,
            'private_key' => $this->private_key,
            'enable_credit_card' => $this->enable_credit_card,
            'enable_installments' => $this->enable_installments,
            'installment_percentage' => $this->installment_percentage,
            'useThreeDSecure' => $this->useThreeDSecure,
            'useThreeDSecure_onFailure' => $this->useThreeDSecure_onFailure,
            'enable_capture' => $this->enable_capture,
            'enable_pix' => $this->enable_pix,
            'enable_boleto' => $this->enable_boleto,
            'pix_tax' => $this->pix_tax,
            'boleto_tax' => $this->boleto_tax,
            'descriptor' => $this->descriptor,
        ];

        $gateway = new Gateway($options);

        $this->gateway = $gateway;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));

    }

    public function process_admin_options()
    {
        parent::process_admin_options();
        $this->save_installment_settings();
    }

    protected function save_installment_settings()
    {
        $installment_percentages = isset($_POST['installment_percentage']) ? $_POST['installment_percentage'] : array();
        $this->update_option('installment_percentage', $installment_percentages);
    }

    public function getGateway() : Gateway {
        return $this->gateway;
    }

    public function init_form_fields()
    {
        $this->form_fields = BcpagAdmin::setting();
    }

    public function payment_fields()
    {

        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }

        if ('no' === $this->enabled) {
            return;
        }

        $checkoutForm = new CheckoutForm($this);
        $checkoutForm->build();
    }

    public function payment_scripts()
    {
        if( ! is_cart() && ! is_checkout() && ! isset( $_GET[ 'pay_for_order' ] ) ) {
            return;
        }

        if( 'no' === $this->enabled ) {
            return;
        }

        wp_enqueue_script('woocommerce_bcpag', plugin_dir_url(__FILE__) . '../assets/js/checkout.js', array('jquery'), microtime(true), true);
        wp_enqueue_style('woocommerce_bcpag_style', plugin_dir_url(__FILE__) . '../assets/css/checkout.css', array('woocommerce-general'), microtime(true), 'all');
    }

    public function enqueue_admin_script() { 
        wp_enqueue_script('woocommerce_bcpag', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), microtime(true), true); 
    }

    public function validate_fields()
    {
        $request = new RequestService($_POST);
        $errors = [];
    
        if (!$request->has("bc_payment_method")) {
            $errors[] = 'Selecione um método de pagamento!';
        } else if ($request->bc_payment_method == 'credit_card' && !$request->has("bc_card_id")) {
            if (!ValidationService::required($request, 'bc_card_holder_name')) {
                $errors[] = 'Titular do cartão é obrigatório!';
            }
    
            if (!ValidationService::required($request, 'bc_card_number')) {
                $errors[] = 'Número do cartão é obrigatório!';
            }
    
            if (!ValidationService::required($request, 'bc_card_expmonth')) {
                $errors[] = 'Mês de vencimento é obrigatório!';
            }
    
            if (!ValidationService::required($request, 'bc_card_expyear')) {
                $errors[] = 'Ano de vencimento é obrigatório!';
            }

            if (!ValidationService::required($request, 'bc_card_cvv')) {
                $errors[] = 'CVV é obrigatório!';
            }
    
            if ($this->enable_installments) {
                if (!ValidationService::required($request, 'bc_card_installments')) {
                    $errors[] = 'Parcelas do cartão é obrigatório!';
                }
            }
            
        }
    
        if (!ValidationService::required($request, 'bc_customer_document_number')) {
            $errors[] = 'Documento é obrigatório!';
        } elseif (!ValidationService::minLenght($request, 'bc_customer_document_number', 11)) {
            $errors[] = 'Tamanho de documento inválido!';
        }
    
        if (!empty($errors)) {
            foreach ($errors as $error) {
                wc_add_notice($error, 'error');
            }
            return false;
        }
    
        return true;
    }
    
    public function createWebhook()
    {
        $api_version = 'v1';
        $route_name = 'webhook';
        $webhook_url = rest_url("bcpag-gateway/{$api_version}/{$route_name}");

        $webhooks = new Webhooks($this->gateway->getSettings());

        $webhook_id = get_option('woocommerce_bcpag_webhook_id');

        if (!$webhook_id) {
            $responseWebhook = $webhooks->create([
                'url' => $webhook_url,
                'events' => ['transactions', 'cards', 'links', 'customers']
            ]);

            error_log(json_encode(['responseWebhook' => $responseWebhook]));
            
            if (isset($responseWebhook->type) && $responseWebhook->type == 'success') { 
                update_option('woocommerce_bcpag_webhook_id', $responseWebhook->body['id']);
            } else { 
                error_log(json_encode(['error' => 'BCPAG: Erro ao criar o webhook', 'responseWebhook' => $responseWebhook]));
                return;
            }
        }

        error_log(json_encode(['webhook_id' => $webhook_id]));
    }

    public function process_payment($orderId)
    {
        $requestService = new RequestService($_POST);
        $orderService = new OrderService(wc_get_order($orderId), $requestService); 
        $paymentService = new PaymentService($orderService, $requestService, $this->gateway);
        $result = $paymentService->process();

        if ($result['response'] == ResponseTypeEnum::SUCCESS) {
            $responseBody = $result['data'];

            error_log(json_encode([
                'responseBody' => $responseBody,
                'useThreeDSecure' => $this->useThreeDSecure,
            ]));

            if (
                $this->useThreeDSecure &&
                $responseBody['status'] == TransactionStatusEnum::REQUEST_AUTHENTICATION
            ) {
                return [
                    'result' => 'success',
                    'redirect' => $responseBody['threeDSecure']->url,
                ];
            }

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($orderService->getWCOrder()),
            );
        } else if ($result['response'] == ResponseTypeEnum::ERROR){
            wc_add_notice($result['message'], 'error');
            return;
        }else {
            wc_add_notice('Please try again.', 'error');
            return;
        }

    }
}