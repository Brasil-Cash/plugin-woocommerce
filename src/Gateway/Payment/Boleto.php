<?php

namespace Bcpag\Gateway\Payment;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Resource\Transactions;
use Bcpag\Gateway\Response\ResourceResponse;
use Bcpag\Interfaces\PaymentInterface;
use Bcpag\Services\OrderService;
use Bcpag\Services\RequestService;

class Boleto extends AbstractPayment implements PaymentInterface
{

    const PAYMENT_CODE = 'boleto';

    protected $name = 'Boleto';

    protected $code = self::PAYMENT_CODE;

    protected $requirementsData = [
        
    ];

    /** @var array */
    protected $dictionary = [
        
    ];

    public function getPayRequest(OrderService $orderService, RequestService $requestService) : ResourceResponse
    {
        $data = $this->getDataBase($orderService, $requestService);
        
        $data['boleto'] = [
            'expiration' => date('Y-m-d', strtotime('+4 days')),
            'max_payment_date' => date('Y-m-d', strtotime('+4 days')),
        ];

        $transaction = new Transactions($this->settings);
        return $transaction->create($data);
    }

    public function refund(int $transactionId, int $amount) {
        $transaction = new Transactions($this->settings);
        return $transaction->refund($transactionId, $amount);
    }

    public function transaction($transactionId){
        $transaction = new Transactions($this->settings);
        return $transaction->transaction($transactionId);
    }
}
