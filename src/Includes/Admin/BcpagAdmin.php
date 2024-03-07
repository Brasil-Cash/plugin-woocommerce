<?php

namespace Bcpag\Includes\Admin;

class  BcpagAdmin {

    public static function setting() {
        return array(
            'enabled' => array(
                'title'       => 'Ativar/Destivar',
                'label'       => 'Ativar Gateway Brasil Cash',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Titulo',
                'type'        => 'text',
                'description' => '',
                'default'     => 'Brasil Cash',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Descrição',
                'type'        => 'textarea',
                'description' => '',
                'default'     => 'Realize pagamentos de forma segura com a Brasil Cash.',
            ),
            'descriptor' => array(
                'title'       => 'Descrição da fatura',
                'type'        => 'text',
                'description' => 'Digite a descrição que aparecerá na fatura do cliente. Máximo 13 caracteres.',
                'default'     => '',
            ),
            'testmode' => array(
                'title'       => 'Sandbox',
                'label'       => 'Ativar modo Sandbox',
                'type'        => 'checkbox',
                'description' => 'Utilize o modo sandbox para realizar testes em seu ecommerce. Esse modo não irá debitar valores das transações.',
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'private_key' => array(
                'title'       => 'Token',
                'type'        => 'password'
            ),
            'credit_card_settings_title' => array(
                'type' => 'title',
                'title' => 'Método de pagamento Cartão de Crédito',
            ),
            'enable_credit_card' => array(
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via Cartão de crédito',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'enable_installments' => array(
                'title'       => 'Parcelamento',
                'label'       => 'Habilitar parcelamento',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'max_installments' => array(
                'title'       => 'Número Máximo de Parcelas',
                'type'        => 'number',
                'description' => 'Configure o número máximo de parcelas disponíveis no checkout.',
                'type'        => 'select',
                'default'     => 1,
                'options'     => array(
                    1  => '1 parcela',
                    2  => '2 parcelas',
                    3  => '3 parcelas',
                    4  => '4 parcelas',
                    5  => '5 parcelas',
                    6  => '6 parcelas',
                    7  => '7 parcelas',
                    8  => '8 parcelas',
                    9  => '9 parcelas',
                    10  => '10 parcelas',
                    11  => '11 parcelas',
                    12  => '12 parcelas',
                ),
                
            ), 
            'btn_installments2' => [
                'title' => 'Parcelas',
                'type' => 'button', 
                'default' => 'Adicionar regra de parcela',
            ],
            'installments_rules' => array( 
                'type' => 'hidden',
                'css' => '',
                'std' => '',
                'desc' => __('JavaScript to handle dynamic addition of installment fields.'),
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                ),
            ),
            'enable_capture' => array(
                'title'       => 'Captura de Pagamento',
                'label'       => 'Selecione como as transações de cartão de crédito devem ser capturadas.',
                'id'       => 'payment_capture',
                'type'        => 'select',
                'default'  => 'automatic',
                'options'  => array(
                    'automatic' => 'Captura Automática',
                    'manual'    => 'Captura Manual',
                ),
                'description' => 'Selecione como as transações de cartão de crédito devem ser capturadas.',
                'desc_tip' => true,
            ),
            'pix_settings_title' => array(
                'type' => 'title',
                'title' => 'Método de pagamento PIX',
            ),
            'enable_pix' => array(
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via PIX',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'boleto_settings_title' => array(
                'type' => 'title',
                'title' => 'Método de pagamento Boleto',
            ),
            'enable_boleto' => array(
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via Boleto',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
        );
    }

}