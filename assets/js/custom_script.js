jQuery(function(){
	jQuery('.field').each(function(){
	if(jQuery('> p > label > span',this).hasClass('required')){
		jQuery('> .acf-input-wrap > input',this).attr('required','');
		jQuery('> textarea',this).attr('required','');
	}
	});
});
