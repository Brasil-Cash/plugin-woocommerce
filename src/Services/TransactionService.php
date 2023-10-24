<?php

namespace Bcpag\Services;

class TransactionService {

    public static function getTransactionById($id){
        global $wpdb;
        $table_name = $wpdb->prefix . 'brasilcash_order_transactions';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE transaction_id = %s", $id);
        $result = $wpdb->get_row($query, ARRAY_A);

        return $result;
    }

}