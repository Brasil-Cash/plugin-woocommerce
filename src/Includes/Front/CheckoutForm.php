<?php

namespace Bcpag\Includes\Front;


class CheckoutForm
{

    protected $options;

    public function __construct(\WC_Bcpag_Gateway $options = null)
    {
        
        $this->options = (object) $options->settings;
    }

    public function build()
    {
        // Inicializa a variável do HTML
        $html = '';

        // Adicione a descrição, se existir
        if ($this->options->description) {
            // Adicione instruções para o modo de teste, se aplicável
            if ($this->options->testmode) {
                $this->options->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#">documentation</a>.';
                $this->options->description  = trim($this->options->description);
            }
            // Adicione a descrição com parágrafos
            $html .= wpautop(wp_kses_post($this->options->description));
        }

        // Adicione o formulário HTML
        $html .= '
        <fieldset id="wc-' . esc_attr($this->options->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
            ';

        // Inicie o formulário com o gancho de ação
        ob_start();
        do_action('woocommerce_credit_card_form_start', $this->options->id);
        $html .= ob_get_clean();

        // Adicione os campos do formulário
        $html .= '
            <div class="">
                <h5>Formas de pagamento</h5>
                <div class="form-row p-0">';
                
                if ($this->options->enable_credit_card == 'yes') {
                    $html .= '<div class="col-12">
                    <div class="form-check pl-2">
                        <input class="form-check-input mt-1" onchange="bcpagChangeViewArea()" type="radio" id="bcpag_payment_method_credit_card" name="bc_payment_method" value="credit_card" checked>
                        <label class="form-check-label" for="bcpag_payment_method_credit_card">
                            Cartão de Crédito
                        </label>
                    </div>
                </div>';
                    
                }

                if ($this->options->enable_pix == 'yes') {
                    $html .= '<div class="col-12">
                        <div class="form-check pl-2">
                            <input class="form-check-input mt-1" onchange="bcpagChangeViewArea()" type="radio" id="bcpag_payment_method_pix" name="bc_payment_method" value="pix">
                            <label class="form-check-label" for="bcpag_payment_method_pix">
                                PIX
                            </label>
                        </div>
                    </div>';
                    
                }

                if ($this->options->enable_boleto == 'yes') {
                    $html .= '<div class="col-12">
                        <div class="form-check pl-2">
                            <input class="form-check-input mt-1" onchange="bcpagChangeViewArea()" type="radio" id="bcpag_payment_method_boleto" name="bc_payment_method" value="boleto">
                            <label class="form-check-label" for="bcpag_payment_method_boleto">
                                Boleto
                            </label>
                        </div>
                    </div>';
                }

                

            $html .= '</div>
            </div>';

            $html .= $this->buildSavedCards();

            $html .= '<div id="bcpag_payment_method_area_credit_card" > 
                <h5>Cartão de crédito</h5>
                <div class="form-row form-row-wide py-0">
                    <label>Titular do cartão <span class="required">*</span></label>
                    <input id="bcpag_ccName" name="bc_card_holder_name" type="text" autocomplete="off" placeholder="Titular do cartão">
                </div>
                <div class="form-row form-row-wide py-0">
                    <label>Número do cartão <span class="required">*</span></label>
                    <input id="bcpag_ccNo" name="bc_card_number" type="text" autocomplete="off" placeholder="0000 0000 0000 0000">
                </div>
                <div class="form-row form-row-first py-0">
                <label>Data de vencimento <span class="required">*</span></label>
                <div class="p-0"> 
                    <div class="p-0 form-row form-row-first"> 
                        <div class="">';
        $html .= $this->buildMonthSelect();
        $html .= '</div>
                    </div>
                    <div class="p-0 form-row form-row-last"> 
                        ';
        $html .= $this->buildYearSelect();
        $html .= '</div>
                </div>
            </div>
                <div class="form-row form-row-last py-0">
                    <label>Código do Cartão (CVV) <span class="required">*</span></label>
                    <input id="bcpag_cvv" name="bc_card_cvv" type="password" autocomplete="off" placeholder="CVC">
                </div>
                </div>';
        $html .= $this->buildInstallmentsSelect();
        $html .= '<div id="bcpag_customer_area" > 
                <div class="form-row form-row-wide py-0">
                    <label>CPF <span class="required">*</span></label>
                    <input id="bcpag_customer_document_number" name="bc_customer_document_number" type="text" autocomplete="off" placeholder="CPF">
                </div>
            </div>

            <div id="bcpag_payment_method_area_pix" style="display: none;"> 
                <h6>O QRCode para pagamento do PIX ficará disponível no pedido após finalização da compra.</h6>
            </div>

            <div id="bcpag_payment_method_area_boleto" style="display: none;"> 
                <h6>O código de barras para pagamento do Boleto ficará disponível no pedido após finalização da compra.</h6>
            </div>

            
            <div class="clear"></div>
        ';

        // Finalize o formulário com o gancho de ação
        ob_start();
        do_action('woocommerce_credit_card_form_end', $this->options->id);
        $html .= ob_get_clean();

        $html .= '
        </fieldset>
        ';

        echo $html;
    }

    private function buildMonthSelect()
    {
        $html = '<select style="width: 100%; border-color: #e1e1e1; padding: 15px 0;" id="bcpag_expmonth" name="bc_card_expmonth">
                        <option value="">Mês</option>';

        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        foreach ($months as $value => $label) {
            $html .= '<option value="' . sprintf("%02d", $value) . '">' . $label . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function buildYearSelect()
    {
        $html = '<select style="width: 100%; border-color: #e1e1e1; padding: 15px 0;" id="bcpag_expyear" name="bc_card_expyear">
                        <option value="">Ano</option>';

        $currentYear = date('Y');

        for ($i = $currentYear; $i <= $currentYear + 20; $i++) {
            $twoDigitYear = substr($i, -2);
            $html .= '<option value="' . $twoDigitYear . '">' . $i . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function buildInstallmentsSelect()
    {
        $html = '';


        if ($this->options->enable_installments) {  
            $totalCart = WC()->cart->get_total('total');
            $html = '<div class="form-row form-row-wide py-0" id="bc-installments-area">
            <label>Parcelas <span class="required">*</span></label>
            <select style="width: 100%; border-color: #e1e1e1; padding: 15px 0;" id="bcpag_installments" name="bc_card_installments">';

            foreach ($this->options->installment_percentage as $installment => $_tax) {
                $selected = ($installment == 1) ? 'selected' : '';
                $tax = (!empty($_tax) && $_tax > 0) ? 'com juros de ' . $_tax . '%' : 'sem juros';

                $amount = wc_price($totalCart / $installment);

                if (!empty($_tax) && $_tax > 0) {
                    $total = $totalCart;
                    $totalTax = ($total * ($_tax/100));
                    $amount = wc_price(($totalCart + $totalTax) / $installment);
                }

                $html .= '<option value="' . $installment . '" '.$selected.' >' . $installment . 'x de ' . $amount . ' ' . $tax .'</option>';
            }

            $html .= '</select></div>';
        }



        return $html;
    }

    public function buildSavedCards() {
        
        $html = '';
        $user_id = get_current_user_id();
        
        if ($user_id) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'brasilcash_user_cards';
            $cards = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE customer_id = %d", $user_id), ARRAY_A);
        
            if (!empty($cards)) {
                $html = '<div id="bcpag_payment_method_area_cards" >
                    <h5>Meus Cartões</h5>
                ';
                    $html .= '<div class="col-12">';
                        $html .= '<div class="form-check pl-2">';
                        $idx = 1;
                        foreach ($cards as $card) {
                            $card_brand = $card['brand'];
                            $last_digits = $card['last_digits'];
                
                            $check = $idx == 1 ? 'checked' : '';
                            $idx++;

                            $image_url = BCPAG_ROOT . 'assets/images/' . esc_attr($card_brand) . '.png';

                            $html .= '<input class="form-check-input mt-3" type="radio" id="bcpag_card_' . esc_attr($card['card_id']) . '" name="bc_card_id" value="' . esc_attr($card['card_id']) . '" '.$check.'>';
                            $html .= '<label class="form-check-label" for="bcpag_card_' . esc_attr($card['card_id']) . '" style="width: 80%;
                            display: flex;
                            justify-content: start;
                            gap: 10px;
                            cursor: pointer;
                            " >';
                                $html .= '<div class="">';
                                    $html .= '<img class="card-band brand-'. esc_attr($card_brand).'" src="'.$image_url.'" style="widht: 64px">';
                                $html .= '</div>';
                                $html .= '<div class="">';
                                    $html .= esc_html(ucfirst($card_brand)) . '<br>Final: ' . esc_html($last_digits);
                                $html .= '</div>';
                            $html .= '</label>';
                        }
                        
                        $html .= '</div>';
                        $html .= '<div class="col-12 my-4">';
                            $html .= '<button type="button" id="bc-new-card" class="btn btn-primary">Novo cartão</button>';
                        $html .= '</div>';
                    $html .= '</div>';
                $html .= '</div>';
            }
            
        }

        return $html;
    }

}
