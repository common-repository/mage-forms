jQuery(document).ready(function() {	
	var $mage_form_type = jQuery("input[name=mage_form_type]:checked"),
		$scroll_settings = jQuery('#mage_scroll_settings'),
		$scroll_cast = jQuery('#mage_scroll_cast');
		
	$mage_form_type.live('change',function(){
		var this_value = jQuery(this).val();
		$scroll_settings.find('.inside .pform, .inside .cform').css('display','none');
		$scroll_cast.find('.inside .pform, .inside .cform').css('display','none');
		switch ( this_value ) {
			case 'cform':
				$scroll_cast.find('.cform').css('display','block');
				$scroll_settings.find('.cform').css('display','block');
			break;
			case 'pform':
				$scroll_settings.find('.pform').css('display','block');
				$scroll_cast.find('.pform').css('display','block');
			break;
		}
		//$scrolls.find('.'+this_value).css('display','block');
		//$scroll_cast.find('.'+this_value).css('display','block');
	});
	
	$mage_form_type.trigger('change');
});