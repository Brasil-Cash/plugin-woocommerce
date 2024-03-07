jQuery(document).ready(function($) {
     
    // Remove uma parcela
    // $('.remove-installment').on('click', function(e) {
    //     e.preventDefault();
    //     $(this).closest('.installment-row').remove();
    // });

    $('#woocommerce_bcpag_btn_installments2').on('click', function(e) {
        e.preventDefault();
        var inputJson = $('#woocommerce_bcpag_installments_rules');
        var parent = $(inputJson).parent();
        var nextInstallment = parent.find('.installment-row').length + 1;

        if (nextInstallment < 22) {
            $(parent).append('<div class="installment-row">' +
                                    '<label style="padding: 0 10px;" for="installment_percentage_' + nextInstallment + '">Parcela ' + nextInstallment + ':</label>' +
                                    '<input type="number" value="0" step="0.01" min="0" id="installment_percentage_' + nextInstallment + '" name="installment_percentage[' + nextInstallment + ']" value="" placeholder="Insira a taxa percentual">' +
                                    '<br> <small>Para parcelas sem juros deixe o valor de 0.</small> <br    >' +
                                    '<a style="padding: 0 10px;" href="#" class="remove-installment">Remover Parcela</a>' +
                                '</div>');
        } 
         
        // alert('ajsdsadjsdjsa');
    });
});