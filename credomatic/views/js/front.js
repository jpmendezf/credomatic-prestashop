(function ($) {

    window.addEventListener('DOMContentLoaded', function() {
        $('form#credomaticCard').card({
            // a selector or DOM element for the container
            // where you want the card to appear
            container: '.card-wrapper', // *required*
        })
    },true)


    $('#credomaticCard')[0].scrollIntoView(true);

    let confirmation = $('#content-hook_order_confirmation');
    let msj = 'Por favor ingrese su tarjeta para realizar el pago'

    $(confirmation).find('span').text(msj)
    $(confirmation).find('h3').text(msj)

    $(confirmation).find('.mail-sent-info').hide()
    $(confirmation).find('p').hide()

    $('#credomaticCard').submit(function (e) {
        e.preventDefault()

        let $formSend = $('#credomatic')

        let cvv = $('input[name="cvc"]').val()

        let ccNumber = $('input[name="number"]')
        let valNumbercc = $(ccNumber).val()
        valNumbercc = valNumbercc.replace(/\s/g,'')

        if (!valid_credit_card(valNumbercc)){
            $(ccNumber).focus()
            return
        }

        let cardHolder = $('input[name="name"]')
        let expCardHolder = $(cardHolder).val()
        expCardHolder =  expCardHolder.split(' ')

        let firstName
        let lastName

        if (expCardHolder[1]){
            firstName = expCardHolder[0]
            lastName = expCardHolder[1]
        }else{
            $(cardHolder).focus()
            return
        }


        let card_exp = $('input[name="expiry"]')
        let exp = $(card_exp).val()
        exp = exp.replace(/\s/g,'').split('/')

        let month
        let year
        let date_end

        if (exp[1]){
            month = exp[0]
            year = exp[1]
            date_end = month.toString() + year
        }else{
            date_end = exp
        }

        if (year && year.length === 4){
            let year_end = year.substr(-2).toString()
            date_end = month + year_end
        }

        $($formSend).append('<input type="hidden" name="ccnumber" value="'+valNumbercc+'">')
        $($formSend).append('<input type="hidden" name="ccexp" value="'+date_end+'">')
        $($formSend).append('<input type="hidden" name="firstname" value="'+firstName+'">')
        $($formSend).append('<input type="hidden" name="lastname" value="'+lastName+'">')
        $($formSend).append('<input type="hidden" name="cvv" value="'+cvv+'">')
        $($formSend).submit();

    })
})(jQuery)

function valid_credit_card(value) {
    // accept only digits, dashes or spaces
    if (/[^0-9-\s]+/.test(value)) return false;
    // The Luhn Algorithm. It's so pretty.
    var nCheck = 0, nDigit = 0, bEven = false;
    value = value.replace(/\D/g, "");
    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n),
            nDigit = parseInt(cDigit, 10);
        if (bEven) {
            if ((nDigit *= 2) > 9) nDigit -= 9;
        }
        nCheck += nDigit;
        bEven = !bEven;
    }
    return (nCheck % 10) == 0;
}