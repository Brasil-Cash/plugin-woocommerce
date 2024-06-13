
const bcErrorCallback = function( data ) {
    console.log( data )
}

jQuery(document).ready(function($) {
    $('#bcpag_payment_method_area_credit_card').hide();
    $('#bcpag_payment_method_area_pix').hide();
    $('#bcpag_payment_method_area_boleto').hide();

    window.bcpagChangeViewArea = () => {
        $('#bcpag_payment_method_area_credit_card').hide();
        $('#bcpag_payment_method_area_pix').hide();
        $('#bcpag_payment_method_area_boleto').hide();
        $('#bc-installments-area').hide();
        $('#bcpag_payment_method_area_cards').hide();
        

        var selectedValue = $('input[name="bc_payment_method"]:checked').val();
        
        if (selectedValue === 'credit_card') {
            if ($('#bcpag_payment_method_area_cards').length) {
                $('#bcpag_payment_method_area_cards').show();
                $('#bcpag_payment_method_area_credit_card').hide();
                $('#bc-installments-area').show();

                $('#bc-new-card').parent().show();

                $('#bc-new-card').click(function() {
                    $('#bcpag_payment_method_area_cards').hide();
                    $('#bcpag_payment_method_area_credit_card').show();
                    $(this).parent().hide();
                });

            } else {
                $('#bcpag_payment_method_area_credit_card').show();
                $('#bc-installments-area').show();
            }

            
        } else if (selectedValue === 'pix') {
            $('#bcpag_payment_method_area_pix').show();
        } else if (selectedValue === 'boleto') {
            $('#bcpag_payment_method_area_boleto').show();
        }
    }

    window.copyToClipboard = (pixCode) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(pixCode).then(() => {
                document.getElementById('message').innerText = "Código PIX copiado com sucesso!";
            }).catch(err => {
                document.getElementById('message').innerText = "Erro ao copiar o código PIX.";
            });
        } else {
            // Fallback para navegadores que não suportam navigator.clipboard
            var textarea = document.createElement('textarea');
            textarea.value = pixCode;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                document.getElementById('message').innerText = "Código PIX copiado com sucesso!";
            } catch (err) {
                document.getElementById('message').innerText = "Erro ao copiar o código PIX.";
            }
            document.body.removeChild(textarea);
        }
    }
});