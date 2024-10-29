/**
 * Created by ahsuoy on 3/25/2017.
 */
jQuery(document).ready(function($) {

    $('div[data-dismissible] button.notice-dismiss').click(function (event) {
        event.preventDefault();


        var data = {
            'action': 'remove_admin_notice',
            'nonce': remove.nonce
        };
        $.post(ajaxurl, data);
    });

});