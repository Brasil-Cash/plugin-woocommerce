<?php

namespace Bcpag\Includes\Front;

use Bcpag\Services\PaymentService;
use Bcpag\Services\OrderService;
use WC_Order;
use chillerlan\QRCode\QRCode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGenerator;

class ThankyouPage
{

    protected $order;
    protected $orderService;

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
        $this->orderService = new OrderService($order);
    }

    public function build()
    {
        $paymentMethod = $this->orderService->getPaymentMethod();
        if (in_array($paymentMethod, [PaymentService::PIX, PaymentService::BOLETO])) {

            echo '<div class="block-title" style="margin-bottom: 15px;">';
            echo '<h2 class="woocommerce-order-details__title">' . __("Aguardando pagamento", "woo-bcpag-gateway") . '</h2>';
            echo '<p class="bc-qrcode-description">' . __('O seu pedido foi gerado e está aguardando pagamento, <strong>caso não haja pagamento em até 10min, o pedido será cancelado automaticamente.</strong>', "woo-bcpag-gateway") . '</p>';
            echo '</div>';

            if ($paymentMethod == PaymentService::PIX) {
                $additionalData = $this->orderService->getAdditionalData();
                $additionalData = json_decode($additionalData, true);

                $pixQrCode = $additionalData['pix_qr_code'];
                echo '<div class="box" style="width: 100%; margin-bottom: 15px;">
                    <div class="bc-qrcode-section" style="width: 100%; display: flex; flex-direction: row;">
                        <div style="width: 30%; padding: 10px;" class="bc-qrcode-section-display">
                            <img src="' . (new QRCode)->render($pixQrCode) . '" alt="QR Code Pix" style="max-width: 100%; height: auto; display: block; margin: 0 auto; box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; border-radius: 5px;" class="bc-qrcode-image">
                        </div>
                        <div style="width: 70%; padding: 10px;" class="bc-qrcode-section-information">
                            <p class="bc-pix-text-copy-cta1"><strong>' . __('Pagar com PIX é fácil e rápido!') . '</strong></p>
                            <p class="bc-pix-text-copy-cta2">' . __('COPIE OU ESCANEIE O QR CODE') . '</p>
                            <p class="bc-pix-text-copy-cta3">' . __('Ao copiar o código, abra o aplicativo cadastrado no PIX e realize seu pagamento de forma rápida.') . '</p>
                            <button style="appearance: button;
                            backface-visibility: hidden;
                            background-color: #405cf5;
                            border-radius: 6px;
                            border-width: 0;
                            box-shadow: rgba(50, 50, 93, .1) 0 0 0 1px inset,rgba(50, 50, 93, .1) 0 2px 5px 0,rgba(0, 0, 0, .07) 0 1px 1px 0;
                            box-sizing: border-box;
                            color: #fff;
                            cursor: pointer;
                            font-family: -apple-system,system-ui,\'Segoe UI\',Roboto,\'Helvetica Neue\',Ubuntu,sans-serif;
                            font-size: 100%;
                            height: 44px;
                            line-height: 1.15;
                            margin: 12px 0 0;
                            outline: none;
                            overflow: hidden;
                            padding: 0 25px;
                            position: relative;
                            text-align: center;
                            text-transform: none;
                            transform: translateZ(0);
                            transition: all .2s,box-shadow .08s ease-in;
                            user-select: none;
                            -webkit-user-select: none;
                            touch-action: manipulation;" class="action primary bc-pix-text-copy-cta-button" onclick="copyToClipboard(\''.$pixQrCode.'\');">' . __('Copy') . '</button>
                            <div id="message"></div>
                        </div>
                    </div>
                </div>';
            }

            if ($paymentMethod == PaymentService::BOLETO) {
                $additionalData = $this->orderService->getAdditionalData();
                $additionalData = json_decode($additionalData, true);

                $boleto = $additionalData['boleto'];
                $generator = new BarcodeGeneratorHTML();

                echo '<div class="box" style="width: 100%; margin-bottom: 15px;">
                    <div class="bc-qrcode-section" style="width: 100%; display: flex; flex-direction: row;">
                        <div style="width: 60%; padding: 10px;" class="bc-qrcode-section-display">
                            '. $generator->getBarcode($boleto['barcode_line'], BarcodeGenerator::TYPE_CODE_128) .'
                        </div>
                        <div style="width: 40%; padding: 10px;" class="bc-qrcode-section-information">
                            <p class="bc-pix-text-copy-cta2">' . __('COPIE OU ESCANEIE O CÓDIGO DE BARRAS') . '</p>
                            <p class="bc-pix-text-copy-cta3">' . __('Ao copiar o código, abra o aplicativo e realize seu pagamento de forma rápida.') . '</p>
                            <button style="appearance: button;
                            backface-visibility: hidden;
                            background-color: #405cf5;
                            border-radius: 6px;
                            border-width: 0;
                            box-shadow: rgba(50, 50, 93, .1) 0 0 0 1px inset,rgba(50, 50, 93, .1) 0 2px 5px 0,rgba(0, 0, 0, .07) 0 1px 1px 0;
                            box-sizing: border-box;
                            color: #fff;
                            cursor: pointer;
                            font-family: -apple-system,system-ui,\'Segoe UI\',Roboto,\'Helvetica Neue\',Ubuntu,sans-serif;
                            font-size: 100%;
                            height: 44px;
                            line-height: 1.15;
                            margin: 12px 0 0;
                            outline: none;
                            overflow: hidden;
                            padding: 0 25px;
                            position: relative;
                            text-align: center;
                            text-transform: none;
                            transform: translateZ(0);
                            transition: all .2s,box-shadow .08s ease-in;
                            user-select: none;
                            -webkit-user-select: none;
                            touch-action: manipulation;" class="action primary bc-pix-text-copy-cta-button" onclick="copyToClipboard(\''.$boleto['barcode_line'].'\');">' . __('Copy') . '</button>
                            <div id="message"></div>
                        </div>
                        
                    </div>
                </div>';
            }
        }
    }
}
