(function($) {
    $.QueryString = (function(a) {
        if (a == "") return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p=a[i].split('=');
            if (p.length != 2) continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    })(window.location.search.substr(1).split('&'))
})(jQuery);
// http://stackoverflow.com/a/3855394

jQuery(document).ready(function($) {
  if ($('.acffef-thankyou-msg').length > 0) { // if there is only one
    var acffef_ty_animate_target_id = $.QueryString.submitted.split('-')[1];
    jQuery('html, body').animate({
      scrollTop: jQuery('#thankyou-msg-'+acffef_ty_animate_target_id).offset().top - 150
    }, 2000);
    acffef_ty_animate_target_id = null; // garbage man, take 'em
  }
});
jQuery(document).ready(function(){
jQuery('.acf-form-submit .acf-button').on('click',function(){
   if(jQuery('.acf-spinner').css('display') == 'block')
{
  jQuery('.acf-form-submit .acf-button').attr("disabled", true);
}
 });
 jQuery('#im-global-contact textarea').after('<input type ="hidden" name="message1" value="form1">')
});
