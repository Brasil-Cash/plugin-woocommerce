<?php

namespace Bcpag\Gateway\Enum;

class TransactionStatusEnum {
    const PROCESSING = 'processing';
    const CANCELED = 'canceled';
    const AUTHORIZED = 'authorized';
    const WAITING_PAYMENT = 'waiting_payment';
    const PAID = 'paid';
    const REFUSED = 'refused';
    const PENDING_REFUND = 'pending_refund';
    const REFUNDED = 'refunded';
}
