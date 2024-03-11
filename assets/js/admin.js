jQuery(document).ready(function($) {
    $('#woocommerce_bcpag_btn_installments2').val('Adicionar parcela');
    var areaInstallments = $('#woocommerce_bcpag_installments_area').parent();
    $(areaInstallments).append('<div></div>');  

    window.bcPagGetJsonData = function () {
        var inputJson = $('#woocommerce_bcpag_installments_rules');
        var inputJsonVal = $(inputJson).val();
        var json;

        if (inputJsonVal) {
            json = JSON.parse(inputJsonVal);
        } else {
            json = [];
        }
        return json;
    }

    window.bcPagUpdateDataInstallment = function (position, inputTax) {
        if (typeof position !== 'number' || position < 0) {
            console.error('Posição inválida.');
            return;
        }

        var json = bcPagGetJsonData();

        if (position >= json.length) {
            console.error('Posição fora do limite.');
            return;
        }

        json[position].tax = $(inputTax).val();
        $('#woocommerce_bcpag_installments_rules').val(JSON.stringify(json));
    }

    window.bcPagBuildInstallmentsInputs = function (json) {
        var parent = $('#woocommerce_bcpag_installments_area').parent().find('div');
        $(parent).empty();
        
        for (let i = 0; i < json.length; i++) {
            let installment = json[i];
            let nextInstallment = i + 1;
            $(parent).append(`
                <div class="installment-row" style="display: flex;
                flex-direction: row;
                justify-content: start;
                gap: 10px;
                margin-bottom: 15px;
                align-items: center;
                max-width: 700px;">
                    <h4 style="margin: 0; text-align:center;" >Parcela ${nextInstallment}:</h4>
                    <div style="width: 50%;">
                        <div class="input-group" style="display: flex; align-content: stretch;">
                            <span class="input-group-addon" style="background: #eee;
                            border: 1px solid #ccc;
                            padding: 0.5em 1em;
                            min-width: 20%;">
                                Juros (%)
                            </span>
                            <input onkeyup="bcPagUpdateDataInstallment(${i}, this)" style="" type="number" step="0.01" min="0" id="installment_percentage_${nextInstallment}" name="installment_percentage[${nextInstallment}]" value="${installment.tax}" placeholder="Insira a taxa percentual">
                        </div>
                        
                    </div>
                    <button style="padding: 0 10px;" type="button" onclick="bcPagRemoveInstallment(${i})" class="remove-installment">Remover Parcela</button>
                </div>
            `);
        }
    }

    window.bcPagRemoveInstallment = function (position) {
        if (typeof position !== 'number' || position < 0) {
            console.error('Posição inválida.');
            return;
        }

        var json = bcPagGetJsonData();
    
        if (position >= json.length) {
            console.error(position, json.length, 'Posição fora do limite.');
            return;
        }
    
        json.splice(position, 1);
        $('#woocommerce_bcpag_installments_rules').val(JSON.stringify(json));
        bcPagBuildInstallmentsInputs(json);
    }

    $('#woocommerce_bcpag_btn_installments2').on('click', function(e) {
        e.preventDefault();
        
        var json = bcPagGetJsonData();
        
        if (json.length < 21) {
            json.push({ "tax": 0.00 });
            $('#woocommerce_bcpag_installments_rules').val(JSON.stringify(json));
            bcPagBuildInstallmentsInputs(json);
        }
        
    });

    var json = bcPagGetJsonData();
    bcPagBuildInstallmentsInputs(json);
});