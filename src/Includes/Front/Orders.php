<?php

namespace Bcpag\Includes\Front;

use WC_Order;

class Orders {

    protected $order;

    public function __construct(WC_Order $order) {
        $this->order = $order;
    }

    public function build() {
        echo 'judite';
    }

}