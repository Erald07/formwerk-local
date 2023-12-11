"use strict";
function leform_pages_activate(_object) {
	var new_page_id = jQuery(_object).closest("li").attr("data-id");
	var form_uid = jQuery(".leform-form").attr("data-id");
	var page_id = jQuery(".leform-form:visible").attr("data-page");
	if (new_page_id == page_id) return;
	jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+page_id+"']").fadeOut(300);
	jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").fadeOut(300, function(){
		jQuery(".leform-form-"+form_uid+"[data-page='"+new_page_id+"']").fadeIn(300);
		jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+new_page_id+"']").fadeIn(300);
		jQuery('html, body').stop().animate({scrollTop: 0}, 300);
		leform_resize();
	});
	return false;
}