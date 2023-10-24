<?php

namespace Bcpag\Gateway\Payment;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Resource\Transactions;
use Bcpag\Gateway\Response\ResourceResponse;
use Bcpag\Interfaces\PaymentInterface;
use Bcpag\Services\OrderService;
use Bcpag\Services\RequestService;

class CreditCard extends AbstractPayment implements PaymentInterface
{

    const PAYMENT_CODE = 'credit_card';

    protected $name = 'Cartão de crédito';

    protected $code = self::PAYMENT_CODE;

    protected $requirementsData = [
        'installments',
    ];

    /** @var array */   
    protected $dictionary = [
        'bc_card_installments' => 'installments',
        'bc_card_number' => 'card_number',
        'bc_card_holder_name' => 'card_holder_name',
        'bc_card_cvv' => 'card_cvv',
    ];

    public function getPayRequest(OrderService $orderService, RequestService $requestService) : ResourceResponse
    {
        $data = $this->getDataBase($orderService, $requestService);

        if ($requestService->has("bc_card_id")) {
            $data['card_id'] = $requestService->bc_card_id;
        } else {
            $data['card'] = [
                'card_number' => str_replace([' '], [''], $requestService->bc_card_number),
                'card_holder_name' => $requestService->bc_card_holder_name,
                'card_expiration_date' => $requestService->bc_card_expmonth . $requestService->bc_card_expyear,
                'card_cvv' => $requestService->bc_card_cvv,
            ];
        }
        
        $data['async'] = false;
        $data['capture'] = $this->settings['enable_capture'] == 'automatic';

        $transaction = new Transactions($this->settings);
        $response = $transaction->create($data);

        if ($response->type == ResponseTypeEnum::SUCCESS) {
            $this->saveCard($orderService->getWCOrder()->get_customer_id(), $response->body);
        }
        
        return $response;
    }

    public function refund(int $transactionId, int $amount) {
        $transaction = new Transactions($this->settings);
        return $transaction->refund($transactionId, $amount);
    }

    public function saveCard($customer_id, array $data){
        if (isset($data['card'])) {
            global $wpdb;

            $card_id = $data['card']->card_id;
            $brand = $data['card']->brand;
            $last_digits = $data['card']->card_last_digits;

            $existing_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}brasilcash_user_cards WHERE customer_id = %d AND card_id = %s", $customer_id, $card_id), ARRAY_A);
            if (!$existing_card) {
                $table_name = $wpdb->prefix . 'brasilcash_user_cards';
                $data_to_insert = array(
                    'customer_id' => $customer_id,
                    'card_id' => $card_id,
                    'brand' => $brand,
                    'last_digits' => $last_digits,
                );
    
                $wpdb->insert($table_name, $data_to_insert);
            }

        }
        
    }

    public function transaction($transactionId){
        $transaction = new Transactions($this->settings);
        return $transaction->transaction($transactionId);
    }
}
