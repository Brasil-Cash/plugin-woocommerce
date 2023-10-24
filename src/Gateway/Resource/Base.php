<?php

namespace Bcpag\Gateway\Resource;

use Analog\Analog;
use Bcpag\Gateway\Enum\ResponseTypeEnum;
use Bcpag\Gateway\Response\ResourceResponse;
use Unirest\Exception;
use Unirest\Request;
use Unirest\Request\Body;

class Base {

    const URL_LIVE = "https://api.brasilcash.com.br/ecommerce";
    const URL_SANDBOX = "https://sandboxapi.brasilcash.com.br/ecommerce";

    protected $settings;
    protected $url;
    protected $version;

    public function __construct($settings, $mode = 'live', $version = 'v1') {
        $this->settings = $settings;
        $this->url = $mode == 'live' ? self::URL_LIVE : self::URL_SANDBOX;
        $this->url .=  "/{$version}";
    }

    public function getUrl() {
        return $this->url;
    }

    protected function getArg($key, array $data = null)
    {
        if (empty($data) || !isset($data[$key])) {
            return null;
        }

        return $data[$key];
    }
   
    protected function get_args(array $properties, array $data)
    {
        foreach ($properties as $property) {
            $args[$property] = call_user_func([$this, 'getArg'], $property, $data);
        }

        return $args;
    }

    public function post($path, array $args) : ResourceResponse {
        $args['metadata'] = json_encode(['module_name' => 'WooCommerce']);

        $response = Request::post(
            $this->getUrl() . $path,
            [
                'Authorization' => 'Bearer ' . $this->settings['private_key'],
                'Content-Type' => 'application/json',
            ],
            Body::Json($args)
        );

        if ($response->code == 200) {
            return new ResourceResponse(ResponseTypeEnum::SUCCESS, (array) $response->body);
        } else if (in_array($response->code, [400, 401, 404, 422])) {
            return new ResourceResponse(ResponseTypeEnum::ERROR, (array) $response->body);
        }

        return new ResourceResponse(ResponseTypeEnum::FAIL, (array) $response->body ?? []);
    }

    public function get($path, array $args = []) : ResourceResponse {
        $args['metadata'] = json_encode(['module_name' => 'WooCommerce']);

        $response = Request::get(
            $this->getUrl() . $path,
            [
                'Authorization' => 'Bearer ' . $this->settings['private_key'],
                'Content-Type' => 'application/json',
            ],
            $args
        );

        if ($response->code == 200) {
            return new ResourceResponse(ResponseTypeEnum::SUCCESS, (array) $response->body);
        } else if (in_array($response->code, [400, 401, 404, 422])) {
            return new ResourceResponse(ResponseTypeEnum::ERROR, (array) $response->body);
        }

        return new ResourceResponse(ResponseTypeEnum::FAIL, (array) $response->body ?? []);
    }

}