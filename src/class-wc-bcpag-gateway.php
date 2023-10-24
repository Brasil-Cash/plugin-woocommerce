<?php

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

    /**
     * Class constructor, more about it in Step 3
     */
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

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->private_key = $this->get_option('private_key');
        $this->enable_credit_card = 'yes' === $this->get_option('enable_credit_card');
        $this->enable_installments = 'yes' === $this->get_option('enable_installments');
        $this->max_installments = $this->get_option('max_installments');
        $this->enable_capture = $this->get_option('enable_capture');
        $this->enable_pix = 'yes' === $this->get_option('enable_pix');
        $this->pix_time_life = $this->get_option('pix_time_life');
        $this->enable_boleto = 'yes' === $this->get_option('enable_boleto');
        $this->descriptor = $this->get_option('descriptor');

        $gateway = new Gateway([
            'enabled' => $this->enabled,
            'testmode' => $this->testmode,
            'private_key' => $this->private_key,
            'enable_credit_card' => $this->enable_credit_card,
            'enable_installments' => $this->enable_installments,
            'max_installments' => $this->max_installments,
            'enable_capture' => $this->enable_capture,
            'enable_pix' => $this->enable_pix,
            'enable_boleto' => $this->enable_boleto,
            'descriptor' => $this->descriptor,
        ]);

        $this->gateway = $gateway;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // You can also register a webhook here
        // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

    }

    public function getGateway() : Gateway {
        return $this->gateway;
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = BcpagAdmin::setting();

    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
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
    
    public function createWebhook(){
        $api_version = 'v1';
        $route_name = 'webhook';
        $webhook_url = rest_url("bcpag-gateway/{$api_version}/{$route_name}");

        $webhooks = new Webhooks($this->gateway->getSettings());
        $webhooks->create([
            'url' => $webhook_url,
            'events' => ['transactions', 'cards', 'links', 'customers']
        ]);
    }

    public function process_payment($orderId)
    {
        $requestService = new RequestService($_POST);
        $orderService = new OrderService(wc_get_order($orderId), $requestService);
        $paymentService = new PaymentService($orderService, $requestService, $this->gateway);
        $result = $paymentService->process();

        if ($result['response'] == ResponseTypeEnum::SUCCESS) {
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

    public function webhook()
    {

        //...

    }
}
