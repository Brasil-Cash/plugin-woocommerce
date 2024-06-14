<?php

namespace Bcpag\Gateway\Payment;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Resource\Transactions;
use Bcpag\Gateway\Response\ResourceResponse;
use Bcpag\Interfaces\PaymentInterface;
use Bcpag\Services\OrderService;
use Bcpag\Services\RequestService;

class Pix extends AbstractPayment implements PaymentInterface
{

    const PAYMENT_CODE = 'pix';

    protected $name = 'PIX';

    protected $code = self::PAYMENT_CODE;

    protected $requirementsData = [
        
    ];

    /** @var array */
    protected $dictionary = [
        
    ];

    public function getPayRequest(OrderService $orderService, RequestService $requestService) : ResourceResponse
    {
        $data = $this->getDataBase($orderService, $requestService);

        $pixTimeLife = 15; // 15 min
        $currentTimestamp = current_time('timestamp', true);
        $expirationTimestamp = $currentTimestamp + ($pixTimeLife * 60);
        $expirationDate = date('Y-m-d H:i:s', $expirationTimestamp);
        $data['expiration_date'] = $expirationDate;

        if (isset($this->settings['pix_tax'])) {
            $tax = $this->settings['pix_tax'];
            if (!empty($tax) && $tax > 0) {
                $amount = $data['amount']; 
                $taxAmount = (int) round(($amount * ($tax/100)), 0, PHP_ROUND_HALF_UP);
                $data['amount'] = (int) ($amount + $taxAmount);
            }
        }

        $transaction = new Transactions($this->settings);
        $response = $transaction->create($data);
        
        if ($response->type == ResponseTypeEnum::SUCCESS) {
            
        }
        
        return $response;
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
