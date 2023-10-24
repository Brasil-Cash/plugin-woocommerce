<?php

namespace Bcpag\Gateway\Resource;


Class Webhooks extends Base {

    const PATH = '/webhooks';

    function __construct(array $settings) {
        parent::__construct($settings);
    }

    public function create(array $data)
    {
        return $this->post(self::PATH, $data);
    }

    public function webhook(array $data = [])
    {
        return $this->get(self::PATH, $data);
    }

}