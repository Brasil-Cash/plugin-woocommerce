<?php

namespace Bcpag\Models;

class Address {
    public string $country = 'br';
    public string $street;
    public string $street_number;
    public string $zipcode;
    public string $state;
    public string $city;
    public string $neighborhood = '';
}