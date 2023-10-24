<?php

namespace Bcpag\Includes\Admin;

class BcpagOrderTransactions
{

    protected $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }


    public function build()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'brasilcash_order_transactions';
        $transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $this->orderId), ARRAY_A);

        echo '<div class="transaction-block">';
        echo '<h3>Transação Brasil Cash</h3>';

        if (!empty($transactions)) {
            echo '<table style="width: 100%;">';
                echo '<thead style="text-align: left;">';
                    echo '<tr>';
                        echo '<th>ID</th>';
                        echo '<th>Status</th>';
                        echo '<th>Ações</th>';
                    echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($transactions as $transaction) {
                    echo '<tr>';
                    echo '<td>' . $transaction['transaction_id'] . '</td>';
                    echo '<td>' . $transaction['transaction_status'] . '</td>';
                    echo '<td>';
                        echo '<a href="' . esc_url("https://ecommerce.brasilcash.com.br/dashboard/transaction/{$transaction['transaction_id']}") . '" target="_blank">Visualizar</a>';

                        if (in_array($transaction['transaction_status'], ['paid', 'authorized'])) {
                            $estornar_link = admin_url("admin.php?action=refund_transaction&order_id=$this->orderId&transaction_id=".$transaction['transaction_id']);
                            echo ' | <a href="' . $estornar_link . '">Estornar</a>';
                        }

                        echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
            echo '</table>';
        } else {
            echo 'Nenhuma transação encontrada para este pedido.';
        }

        echo '</div>';
    }
}
