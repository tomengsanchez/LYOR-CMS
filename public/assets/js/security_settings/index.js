$(function(){
    $('#enable2fa').on('change', function(){
        $('#2fa_expiration').prop('disabled', !$(this).is(':checked'));
    });
});
