<?php

namespace Bcpag\Gateway\Resource;


Class Transactions extends Base {

    const PATH = '/transactions';

    function __construct(array $settings) {
        parent::__construct($settings);
    }

    public function create(array $data)
    {
        return $this->post(self::PATH, $data);
    }

    public function refund(int $transaction_id, $amount)
    {
        return $this->post(self::PATH . "/{$transaction_id}/refund", ['async' => false, 'amount' => $amount]);
    }

    public function transaction(int $transaction_id)
    {
        return $this->get(self::PATH . "/{$transaction_id}");
    }

}