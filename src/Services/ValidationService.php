<?php

namespace Bcpag\Services;

class ValidationService {

    
    public static function required(RequestService $request, $field) : bool {
        return ($request->has($field) && !empty(trim($request->$field)));
    }
    
    public static function lenght(RequestService $request, $field, int $lenght) : bool  {
        return $lenght == strlen($request->$field);
    }

    public static function minLenght(RequestService $request, $field, int $lenght) : bool  {
        return strlen($request->$field) >= $lenght;
    }

    public static function maxLenght(RequestService $request, $field, int $lenght) : bool  {
        return strlen($request->$field) <= $lenght;
    }

}