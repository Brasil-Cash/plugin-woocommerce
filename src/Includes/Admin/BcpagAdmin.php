<?php

namespace Bcpag\Includes\Admin;

class  BcpagAdmin {

    public static function setting() {
        return [
            'enabled' => [
                'title'       => 'Ativar/Destivar',
                'label'       => 'Ativar Gateway Brasil Cash',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ],
            'title' => [
                'title'       => 'Titulo',
                'type'        => 'text',
                'description' => '',
                'default'     => 'Brasil Cash',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'Descrição',
                'type'        => 'textarea',
                'description' => '',
                'default'     => 'Realize pagamentos de forma segura com a Brasil Cash.',
            ],
            'descriptor' => [
                'title'       => 'Descrição da fatura',
                'type'        => 'text',
                'description' => 'Digite a descrição que aparecerá na fatura do cliente. Máximo 13 caracteres.',
                'default'     => '',
            ],
            'testmode' => [
                'title'       => 'Sandbox',
                'label'       => 'Ativar modo Sandbox',
                'type'        => 'checkbox',
                'description' => 'Utilize o modo sandbox para realizar testes em seu ecommerce. Esse modo não irá debitar valores das transações.',
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'private_key' => [
                'title'       => 'Token',
                'type'        => 'password'
            ],
            'display_erros' => [
                'title'       => 'Mostrar errors',
                'label'       => 'Exibir erros na mensagem',
                'type'        => 'checkbox',
                'description' => 'Utilize esse recurso para fins de debug na sua loja.',
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'pix_settings_title' => [
                'type' => 'title',
                'title' => 'Método de pagamento PIX',
            ],
            'enable_pix' => [
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via PIX',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ],
            'boleto_settings_title' => [
                'type' => 'title',
                'title' => 'Método de pagamento Boleto',
            ],
            'enable_boleto' => [
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via Boleto',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ],
            'credit_card_settings_title' => [
                'type' => 'title',
                'title' => 'Método de pagamento Cartão de Crédito',
            ],
            'enable_credit_card' => [
                'title'       => 'Ativar/Destivar',
                'label'       => 'Receber via Cartão de crédito',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ],
            'enable_capture' => [
                'title'       => 'Captura de Pagamento',
                'label'       => 'Selecione como as transações de cartão de crédito devem ser capturadas.',
                'id'       => 'payment_capture',
                'type'        => 'select',
                'default'  => 'automatic',
                'options'  => [
                    'automatic' => 'Captura Automática',
                    'manual'    => 'Captura Manual',
                ],
                'description' => 'Selecione como as transações de cartão de crédito devem ser capturadas.',
                'desc_tip' => true,
            ],
            'threeDSecure_title' => [
                'type' => 'title',
                'title' => '3DS 2.0 (ThreeDSecure) Cartão de crédito',
            ],
            'enable_threeDSecure' => [
                'title'       => 'Ativar/Destivar',
                'label'       => 'Usar 3DS 2.0 (ThreeDSecure)',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ],
            'threeDSecure_onFailure' => [
                'title'       => 'Captura de Pagamento',
                'label'       => 'Define como prosseguir com a transação caso a autenticação 3DS não obtenha sucesso.',
                'id'       => 'payment_capture',
                'type'        => 'select',
                'default'  => 'automatic',
                'options'  => [
                    'continue' => 'Aceitar - prossegue com a transação financeira mesmo se a autenticação falhar',
                    'decline'    => 'Recusar - não prossegue com a transação financeira caso a autenticação falhar',
                ],
                'description' => 'Define como prosseguir com a transação caso a autenticação 3DS não obtenha sucesso.',
                'desc_tip' => true,
            ],
            'installments_title' => [
                'type' => 'title',
                'title' => 'Parcelamento Cartão de crédito',
            ],
            'enable_installments' => [
                'title'       => 'Parcelamento',
                'label'       => 'Habilitar parcelamento',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ],
            'installments_rules' => [ 
                'type' => 'hidden',
                'default' => '[
                    {
                      "tax": 0.00
                    }
                ]',
            ],
            'btn_installments2' => [
                'title' => 'Parcelas',
                'type' => 'button', 
                'default' => 'Adicionar parcela',
                'description' => 'Personalize o número de parcelas que deseja oferecer para seus pagamentos. Para parcelas sem juros, basta inserir o valor 0.',
            ],  
            'installments_area' => [
                'type' => 'hidden',
            ] 
        ];
    }

}