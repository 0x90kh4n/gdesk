/* gDesk - Helpdesk Ticketing Software */

$(function() {

    $.ajaxSetup({ cache: false });

    $('form').submit(function() {
        $(this).find('button[type="submit"]')
            .html('<span class="spinner-border spinner-border-sm" role="status"></span>')
            .prop('disabled', true);
    });

});