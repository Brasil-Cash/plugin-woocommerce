<?php

namespace  Bcpag\Gateway\Payment;

use Bcpag\Services\OrderService;
use Bcpag\Services\RequestService;

abstract class AbstractPayment
{

    protected $name = null;

    protected $code = null;

    protected array $settings;

    protected $requirementsData = [];

    protected $dictionary = [];

    public function getName()
    {
        return $this->name ?? $this->error($this->name);
    }

    public function getMethodCode()
    {
        return $this->code ?? $this->error($this->code);
    }

    public function setSettings(array $settings) {
        $this->settings = $settings;
    }
    
    
    public function getRequirementsData()
    {
        return $this->requirementsData;
    }

    public function getDataBase(OrderService $orderService, RequestService $requestService): array {
        $data = [
            'payment_direct' => true,
            'amount' => $orderService->getTotal(),
            'payment_method' => $requestService->bc_payment_method,
            'async' => true,
            'capture' => true,
            'installments' => $requestService->has('bc_card_installments') ? $requestService->bc_card_installments : 1,
            'items' => $orderService->getItems(),
        ];

        $customer = $orderService->getCustomer();
        $billingAddress = $orderService->getBillingAddress();
        $shippingAddress = $orderService->getShippingAddress();

        if ($customer) {
            $data['customer'] = $customer;
        }

        if ($billingAddress) {
            $data['billing'] = $billingAddress;
        }

        if ($shippingAddress) {
            $data['shipping'] = $shippingAddress;
        }
        
        return $data;

    }

    public function renameFieldsPost(
        $field,
        $formattedPost,
        $arrayFieldKey
    ) {
        foreach ($this->dictionary as $fieldKey => $formatedPostKey) {
            if (in_array($fieldKey, $field)) {
                $field['name'] = $formatedPostKey;
                $formattedPost['fields'][$arrayFieldKey] = $field;
            }
        }
        return $formattedPost;
    }

    private function error($field)
    {
        throw new \Exception(__('Invalid data for payment method: ', 'woo-bcpag-gateway') . $field);
    }
   
}
