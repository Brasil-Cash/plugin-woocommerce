<?php

namespace Bcpag\Services;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Enum\TransactionStatusEnum;
use Bcpag\Gateway\Gateway;
use Bcpag\Gateway\Payment;

class PaymentService {

    protected $order;
    protected $request;
    protected $gateway;

    CONST CREDIT_CARD = 'credit_card';
    CONST PIX = 'pix';
    CONST BOLETO = 'boleto';


    public function __construct(OrderService $order, RequestService $request = null, Gateway $gateway = null) {
        $this->order = $order;
        $this->request = $request;
        $this->gateway = $gateway;
    }

    public function process() {
        $request = $this->request;
        $order = $this->order;
        $gateway = $this->gateway;

        if ($request == null || $gateway == null) {
            return;
        }

        $settings = $this->gateway->getSettings();

        $payment = new Payment($request->bc_payment_method, $gateway);
        $order->setPaymentMethod($request->bc_payment_method);
        $response = $payment->getPaymentData($order, $request);

        $endOfMessage = '';

        if ($settings['display_erros']) { 
            $endOfMessage = json_encode([
                'response' => $response
            ]);
        }

        error_log(json_encode(['endOfMessage' => $endOfMessage, 'response' => $response]));

        switch ($response->type) {
            case ResponseTypeEnum::SUCCESS:
                return $this->resolveStatus($response, $endOfMessage);
            case ResponseTypeEnum::ERROR:
                return $this->resolveError($response, $endOfMessage);
            default:
                return [
                    'response' => ResponseTypeEnum::FAIL,
                    'message' => __('Error ao processar pagamento, entre em contato com a loja virtual. ' . $endOfMessage, 'woo-bcpag-gateway'),
                ];
        }
    }

    public function refundTransaction() 
    {
        if ($this->request->has('transaction_id')) {
            $payment = new Payment($this->order->getPaymentMethod(), $this->gateway);
            $response = $payment->refund($this->request->transaction_id, $this->order->getTotal());

            switch ($response->type) {
                case ResponseTypeEnum::SUCCESS:
                    return $this->resolveStatus($response);
                case ResponseTypeEnum::ERROR:
                    return [
                        'response' => ResponseTypeEnum::ERROR,
                        'message' => __('Falha ao estornar, revise os dados e tente novamente.', 'woo-bcpag-gateway'),
                    ];
            
                default:
                    return [
                        'response' => ResponseTypeEnum::FAIL,
                        'message' => __('Falha ao estornar, entre em contato com a loja virtual.', 'woo-bcpag-gateway'),
                    ];
            }
    
        }
    }

    public function checkStatus($transaction_id, $localStatus)
    {
        $payment = new Payment($this->order->getPaymentMethod(), $this->gateway);
        $response = $payment->checkTransaction($transaction_id);
        $attempt = $this->order->getAttempChecks() ?? 1 ;

        switch ($response->type) {
            case ResponseTypeEnum::SUCCESS:
                $transaction = $response->body;

                if ($attempt <= 15) {
                    $attempt++;
                    $this->order->setAttempChecks($attempt);
                    if ($localStatus != $transaction['status']) {
                        $this->resolveStatus($response);
                    }
                } else {
                    $this->order->updateStatus('cancelled', __('Pedido cancelado por falta de pagamento', 'woo-bcpag-gateway'));
                }

                break;
        }
    }

    public function getOrder()
    {
        return $this->order;
    }

    protected function resolveError($response, $endOfMessage = '')
    {
        $body = $response->body;
        $message = __('Falha ao processar pagamento, entre em contato com a loja virtual.', 'woo-bcpag-gateway');

        if (isset($body['errors'])) {
            $message = '';

            foreach ($body['errors'] as $error) {
                $message .= __($error, 'woo-bcpag-gateway') . '<br>';
            }
        }

        return [
            'response' => ResponseTypeEnum::ERROR,
            'message' => $message,
            'data' => $body
        ];

    }

    protected function resolveStatus($response, $endOfMessage = '')
    {

        $body = $response->body;

        switch($body['status']) {
            case TransactionStatusEnum::PAID:
            case TransactionStatusEnum::AUTHORIZED:
            case TransactionStatusEnum::WAITING_PAYMENT:
            case TransactionStatusEnum::REQUEST_AUTHENTICATION:

                if ($this->order->getPaymentMethod() == self::PIX) {
                    $this->order->setAdditionalData(json_encode([
                        'pix_qr_code' => $body['pix_qr_code'],
                        'pix_expiration_date' => $body['pix_expiration_date'],
                        'pix_additional_fields' => $body['pix_additional_fields'],
                    ]));
                } else if ($this->order->getPaymentMethod() == self::BOLETO) {
                    $this->order->setAdditionalData(json_encode([
                        'boleto' => $body['boleto']
                    ]));
                }

                $this->order->addTransaction($body['id'], $body['status']);

                if ($body['status'] != TransactionStatusEnum::REQUEST_AUTHENTICATION) {
                    $this->order->completeOrder($body['id']);
                }

                return [
                    'response' => ResponseTypeEnum::SUCCESS,
                    'message' => __('Pago com sucesso. ' . $endOfMessage, 'woo-bcpag-gateway'),
                    'data' => $body
                ];
            case TransactionStatusEnum::REFUSED:
                return [
                    'response' => ResponseTypeEnum::ERROR,
                    'message' => $body['refused_reason']->reason . ' ' . $endOfMessage,
                    'data' => $body
                ];
            case TransactionStatusEnum::PENDING_REFUND:
            case TransactionStatusEnum::REFUNDED:
                $this->order->updatStatusTransaction($body['id'], $body['status'] ?? TransactionStatusEnum::REFUNDED);
                $this->order->updateStatus('refunded', __('Transação reembolsada: ' . $body['id'], 'woo-bcpag-gateway'));
                return [
                    'response' => ResponseTypeEnum::SUCCESS,
                    'message' => "Transação reembolsada",
                    'data' => $body
                ];
            case TransactionStatusEnum::CANCELED:
                $this->order->updateStatus('canceled', __('Transação Cancelada: ' . $body['id'], 'woo-bcpag-gateway'));
                return [
                    'response' => ResponseTypeEnum::SUCCESS,
                    'message' => "Transação Cancelada",
                    'data' => $body
                ];
        }

    }

}