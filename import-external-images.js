/**
* External Images admin javascript functions
*/
	
/**
* Begin the process of re-sizing all of the checked images
*/
function vr_external_images_import_images() {
	
	vr_import_images_start_timer();
	window.vr_import_images_one_minute = '';
	window.vr_import_images_three_minute = '';
	window.vr_import_images_five_minute = '';
	window.vr_import_images_ten_minute = '';
							
	var target = jQuery('#vr_import_posts'); 
	jQuery('#vr_posts_list').fadeOut('3000');
	
	target.html('');
	target.show();
	//jQuery(document).scrollTop(target.offset().top);

	var data = {
		action: 'vr_external_image_get_backcatalog_ajax'
	};
	
	jQuery.post( ajaxurl, data , function(response) {
		// alert('Got this from the server: ' + response);

		var results = JSON.parse(response);
		var posts_to_process = results['posts'];
		var insert = '<div id="processing"><p class="howto">Importing images from '+ posts_to_process.length +' posts...</p><h2>Please Wait...</h2></div>';
		insert += '<div id="vr_import_process" style="padding:2px 2px 10px; margin:10px 0;background: none repeat scroll 0 0 #C5E5F5;border: 1px solid #298CBA;"></div>';
		target.html(insert);
		
		target.slideDown();
		// recurse
		vr_external_images_import_all(posts_to_process,0);
	});
}

/** 
* recursive function for resizing images
*/
function vr_external_images_import_all(posts,next_post) {
	
	if (next_post >= posts.length) {
		return vr_external_images_import_complete();
	}
	
	var target = jQuery('#vr_import_process');
	
	var data = {
		action: 'vr_external_image_import_all_ajax',
		vr_import_images_post: posts[next_post]
	};
	
	
	jQuery.post( ajaxurl, data , function(response) {
		// alert(window.vr_import_images_start_time + ' -- ' + current_time);
		// alert('Got this from the server: ' + response);
		console.log(response);
		var result = JSON.parse(response);

		target.prepend('<div style="padding:2px 30px 0 10px;">'+ result +'</div>');
		target.slideDown();

		// recurse
		vr_external_images_import_all(posts,next_post+1);
	});
	
}

/**
* fired when all images have been resized
*/
function vr_external_images_import_complete() {
	var target = jQuery('#vr_import_process'); 
	jQuery('#vr_import_posts #processing').fadeOut();
	target.prepend('<div style="color:#21759B; margin: 0 0 10px; padding: 5px 10px; background-color: #FFFFE0; border:1px solid #E6DB55;">IMPORT COMPLETE</div>');
	target.animate(
		{ scrollTop: target.height() }, 
		500
	);
}

function vr_import_images_start_timer()	{
	var d = new Date();
	window.vr_import_images_start_time = d.getTime();
}
