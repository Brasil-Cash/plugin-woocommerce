<?php

namespace Bcpag\Gateway\Enum;

class ResponseTypeEnum {
    const SUCCESS = 'success';
    const ERROR = 'error';
    const FAIL = 'fail';

    public static function fromString(string $value): ?string {
        if (in_array($value, [self::SUCCESS, self::ERROR, self::FAIL])) {
            return $value;
        }
        return null;
    }

}