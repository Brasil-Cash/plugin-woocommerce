<?php

namespace Bcpag\Gateway\Response;

use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Unirest\Response;

class ResourceResponse {
    public string $type;
    public array $body;

    function __construct(string $responseType, array $body = null) {
        $this->type = $responseType;
        $this->body = $body;
    }

}