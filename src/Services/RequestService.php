<?php

namespace Bcpag\Services;

class RequestService {
    private $data;

    private $ignore = [
        'woocommerce-process-checkout-nonce',
        '_wp_http_referer',
    ];

    public function __construct(array $rawData) {
        $this->data = $this->sanitizeData($rawData);
        
    }

    private function sanitizeData(array $data) {
        $sanitizedData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->ignore)) continue;
            $sanitizedData[$key] = $this->sanitize($value);
        }

        return $sanitizedData;
    }

    private function sanitize($value) {
        return wc_clean($value);
    }

    public function __get($fieldName) {
        if (isset($this->data[$fieldName])) {
            return $this->data[$fieldName];
        }
        
        return null;
    }

    public function has($fieldName) {
        return isset($this->data[$fieldName]);
    }

    public function all(){
        return $this->data;
    }
}
