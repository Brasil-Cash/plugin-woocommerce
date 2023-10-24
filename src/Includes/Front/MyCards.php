<?php

namespace Bcpag\Includes\Front;

use Bcpag\Services\RequestService;

class MyCards
{
    protected $cards;
    protected $database;

    public function __construct()
    {
        global $wpdb;
        $this->database = $wpdb;
    }

    public function build()
    {
        $html = ' ';
        $user_id = get_current_user_id();

        if ($user_id) {
            $table_name = $this->database->prefix . 'brasilcash_user_cards';
            $cards = $this->database->get_results($this->database->prepare("SELECT * FROM $table_name WHERE customer_id = %d", $user_id), ARRAY_A);

            if (!empty($cards)) {
                $html = '<div id="bcpag_payment_method_area_cards">
                    <h5>Meus Cartões</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bandeira</th>
                                <th>Final</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>';

                foreach ($cards as $card) {
                    $card_brand = $card['brand'];
                    $last_digits = $card['last_digits'];
                    $delete_link = wc_get_account_endpoint_url('delete-card') . '?card_id=' . esc_attr($card['id']);
                    $html .= '<tr>
                                <td>'.  ucfirst(esc_attr($card_brand)) .'</td>
                                <td>' . esc_html($last_digits) . '</td>
                                <td>
                                    <a href="' . $delete_link . '">Deletar</a>
                                </td>
                            </tr>';
                }

                $html .= '</tbody>
                    </table>
                </div>';
            }
        }

        echo $html;
    }

    public function delete(RequestService $request)
    {
        if ($request->has('card_id')) {
            $table_name = $this->database->prefix . 'brasilcash_user_cards';
            $card_id = $request->card_id; 
            $user_id = get_current_user_id(); 

            $user_id_of_card = $this->database->get_var($this->database->prepare("SELECT customer_id FROM $table_name WHERE id = %s", $card_id));

            if ($user_id_of_card == $user_id) {
                $this->database->delete($table_name, ['id' => $card_id], ['%s']);

                wp_redirect(wc_get_account_endpoint_url('bc-cards'));
                exit;
            } else {
                exit;
            }
        }
    }
}
