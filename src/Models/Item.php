<?php

namespace Bcpag\Models;

Class Item {
    public string $external_id;
    public string $title;
    public int $unit_price;
    public int $quantity = 1;
    public bool $tangible;
}