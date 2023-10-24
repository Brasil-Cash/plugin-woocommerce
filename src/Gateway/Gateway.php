<?php

namespace Bcpag\Gateway;

use Bcpag\Interfaces\PaymentInterface;
use ReflectionClass;

class Gateway {
    
    protected array $settings;

    function __construct(array $settings = null) {
        $this->settings = $settings;
    }
    

    public function getPaymentInstace($paymentCode)
    {
        foreach ($this->getPayments() as $class) {
            $payment = new $class;
            if ($payment->getMethodCode() === $paymentCode) {
                $payment->setSettings($this->settings);
                return $payment;
            }
        }
        throw new \Exception(__('Invalid payment method: ', 'woo-bcpag-gateway') . $paymentCode);
    }

    private function getPayments()
    {
        $this->autoLoad();
        $payments = [];
        foreach (get_declared_classes() as $class) {
            try {
                $reflect = new ReflectionClass($class);
                if($reflect->implementsInterface(PaymentInterface::class)) {
                    $explodedFileName = explode(DIRECTORY_SEPARATOR, $reflect->getFileName());
                    $payments[end($explodedFileName)] = $class;
                }
            } catch (\ReflectionException $e) {}
        }
        return $payments;
    }

    private function autoLoad()
    {
        foreach(glob( __DIR__ . '/Payment/*.php') as $file) {
            include_once($file);
        }
    }

    public function getOption(string $option) {
        return isset($this->settings[$option]) ? $this->settings[$option] : null;
    }

    public function getSettings(){
        return $this->settings;
    }

}