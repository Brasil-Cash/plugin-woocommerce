<?php

namespace Bcpag\Services;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Gateway;
use Bcpag\Gateway\Payment;
use Bcpag\Models\Address;
use Bcpag\Models\BillingAddress;
use Bcpag\Models\Customer;
use Bcpag\Models\Document;
use Bcpag\Models\Item;
use Bcpag\Models\ShippingAddress;
use WC_Bcpag_Gateway;
use WC_Customer;

class OrderService
{

    protected $database;
    protected $order;
    protected $request;
    public $customer = null;
    public array $items;

    public function __construct($order, RequestService $request = null)
    {
        global $wpdb;
        $this->database = $wpdb;
        $this->order = $order;
        $this->request = $request;
        $this->customer = $this->fillCustomer($request);
    }

    public function getItems() : array
    {
        $this->items = [];   

        foreach ($this->order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            $bcItem = new Item();
            $bcItem->external_id = $product->get_id();
            $bcItem->title = $item->get_name();
            $bcItem->quantity = $item->get_quantity();
            $bcItem->tangible = empty($item->get_item_downloads());
            $price_string = $product->get_price();
            $price_decimal = floatval($price_string);
            $bcItem->unit_price = $price_decimal * 100;

            $items[] = $bcItem;
        }

        return $items;
    }

    public function getTotal() : int 
    {
        $price_string = $this->order->get_total();
        $price_decimal = floatval($price_string);
        return $price_decimal * 100;
    }

    public function getWCOrder()
    {
        return $this->order;
    }

    public function getCustomer() 
    {
        return $this->customer;
    }

    public function addTransaction($id, $status = 'processing') 
    {
        $table_name = $this->database->prefix . 'brasilcash_order_transactions';
    
        $existing_transaction = $this->getTransactionById($id);
    
        if ($existing_transaction) {
            $data_to_update = array(
                'transaction_status' => $status,
            );
    
            $where = array('transaction_id' => $id);
            $updated = $this->database->update($table_name, $data_to_update, $where);
    
            if ($updated === false) {
            }
        } else {
            $data_to_insert = array(
                'order_id' => $this->order->get_id(), 
                'transaction_id' => $id,  
                'transaction_status' => $status,  
            );
    
            $this->database->insert($table_name, $data_to_insert);
        }
    }
    

    public function updatStatusTransaction($id, $newStatus) 
    {
        $table_name = $this->database->prefix . 'brasilcash_order_transactions';

        $existing_transaction = $this->database->get_row(
            $this->database->prepare("SELECT * FROM $table_name WHERE transaction_id = %s", $id)
        );

        if ($existing_transaction) {
            $this->database->update(
                $table_name,
                array('transaction_status' => $newStatus),
                array('transaction_id' => $id)
            );
        }
    }

    public function getTransactionById($id) 
    {
        return TransactionService::getTransactionById($id);
    }

    public function updateStatus($status, $note = '') 
    {
        $this->order->update_status($status, $note);
        $this->order->save();
    }

    public function completeOrder($transaction_id) 
    {
        $this->order->payment_complete($transaction_id);
        WC()->cart->empty_cart();
    }

    public function setPaymentMethod($paymentMethod) 
    {
        $this->order->update_meta_data('_bc_payment_method', $paymentMethod);
        $this->order->save();
    }

    public function setAdditionalData($data) 
    {
        $this->order->update_meta_data('_bc_additional_data', $data);
        $this->order->save();
    }

    public function setAttempChecks($data) 
    {
        $this->order->update_meta_data('_bc_attempt_verify', $data);
        $this->order->save();
    }

    public function getAttempChecks() 
    {
        return $this->order->get_meta('_bc_attempt_verify', true);
    }

    public function getAdditionalData() 
    {
        return $this->order->get_meta('_bc_additional_data', true);
    }

    public function getPaymentMethod() 
    {
        return $this->order->get_meta('_bc_payment_method', true);
    }

    public function getBillingAddress() 
    {
        if ($this->order) {
            $billing_address = $this->order->get_address('billing');

            $address = new Address();
            $address->street = $billing_address['address_1'];
            $address->street_number = (string) $this->getStreetNumber($billing_address);
            $address->zipcode = preg_replace('/\D/', '',$billing_address['postcode']);
            $address->state = $billing_address['state'];
            $address->city = $billing_address['city'];
            $address->country = $billing_address['country'];
            $address->neighborhood = $billing_address['address_2'] == '' ? $billing_address['address_1'] : $billing_address['address_2'];

            $billingAddress = new BillingAddress();
            $billingAddress->name = $billing_address['first_name'] . ' ' . $billing_address['last_name'];
            $billingAddress->address = $address;

            return $billingAddress;

        }

        return null;
    }

    public function getShippingAddress() 
    {
        if ($this->order) {
            $shipping_address = $this->order->get_address('shipping');
    
            $address = new Address();
            $address->street = $shipping_address['address_1'];
            $address->street_number = (string) $this->getStreetNumber($shipping_address);
            $address->zipcode = preg_replace('/\D/', '', $shipping_address['postcode']);
            $address->state = $shipping_address['state'];
            $address->city = $shipping_address['city'];
            $address->country = $shipping_address['country'];
            $address->neighborhood = $shipping_address['address_2'] == '' ? $shipping_address['address_1'] : $shipping_address['address_2'];
    
            $shippingAddress = new ShippingAddress();
            $shippingAddress->name = $shipping_address['first_name'] . ' ' . $shipping_address['last_name'];
            $shipping_fee = $this->order->get_shipping_total();
            $shippingAddress->fee = (float) $shipping_fee;
            $shippingAddress->address = $address;
    
            return $shippingAddress;
        }
    
        return null;
    }

    public function verifyStatus()
    {
        if (in_array($this->getPaymentMethod(), [PaymentService::PIX, PaymentService::BOLETO])){
            $localTransaction = $this->getTransactionById($this->order->get_transaction_id());
            if ($localTransaction) {
                $plugin = new WC_Bcpag_Gateway();
                $paymentService = new PaymentService($this, null, $plugin->getGateway());
                $paymentService->checkStatus($localTransaction['transaction_id'], $localTransaction['transaction_status']);
            }
    
        }
    }

    private function getStreetNumber($billing_address) 
    {
        $address_1 = $billing_address['address_1'];
        $address_2 = $billing_address['address_2'];
    
        $pattern = '/\d+/';
    
        if (preg_match($pattern, $address_1, $matches)) {
            return $matches[0];
        } elseif (preg_match($pattern, $address_2, $matches)) {
            return $matches[0];
        } else {
            return 0;
        }
    }

    private function fillCustomer(RequestService $request = null) 
    {

        $customer_id = $this->order->get_customer_id();
        $customerData = new WC_Customer($customer_id);

        if (!empty($customerData->get_email()) && $request && $request->has("bc_customer_document_number")) {
            $customer = new Customer();
            $customer->name = $customerData->get_first_name() . ' ' . $customerData->get_last_name();
            $customer->email = $customerData->get_email();
            $customer->external_id = $customerData->get_id();
            $customer->country = 'BR';
            $documentNumber = $request->bc_customer_document_number;
            $customer->setType($documentNumber);
            $document = new Document();
            $document->type = $customer->type == 'individual' ? 'cpf' : 'cnpj';
            $document->number = $documentNumber;
            $customer->addDocument($document);
        
            return $customer;
        }

        return null;
    }

}
