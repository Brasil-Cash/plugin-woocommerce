<?php 

namespace Bcpag\Gateway;

use Bcpag\Gateway\Response\ResourceResponse;
use Bcpag\Services\OrderService;
use Bcpag\Services\RequestService;

class Payment {

    private Gateway $gateway;

    protected $paymentInstance;

    public function __construct(
        string $paymentMethod,
        Gateway $gateway = null
    ) {
        if (!$gateway) {
            $gateway = new Gateway;
        }
        $this->gateway = $gateway;
        $this->paymentInstance = $this->gateway->getPaymentInstace($paymentMethod);
    }


    public function getPaymentData(OrderService $order, RequestService $request)  : ResourceResponse
    {
        return $this->paymentInstance->getPayRequest($order, $request,  $this->gateway);
    }

    public function refund($transaction_id, $amount) {
        return $this->paymentInstance->refund($transaction_id, $amount);
    }

    public function checkTransaction($transaction_id) {
        return $this->paymentInstance->transaction($transaction_id);
    }
}