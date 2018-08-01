<?php
/*
Plugin Name: Import External Images 2
Plugin URI:  https://github.com/VR51/import-external-images-2
Version: 2.0.6
Description: Examines the text of posts and pages and makes local copies of all the external images linked though IMG tags, adding them as media attachments to the post or page.
Author: VR51, Marty Thornley
Author URI: https://github.com/VR51/import-external-images-2
License: GPLv2 or later
*/

/*

Original fork is at https://github.com/VR51/import-external-images.

Based on Import External Images by Marty Thornley
https://github.com/MartyThornley/import-external-images

Which is based on Add Linked Images To Gallery v1.4 by Randy Hunt
http://www.bbqiguana.com/wordpress-plugins/add-linked-images-to-gallery/

See plugin fork information at the Github address for development history.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* Used for debugging. */
/*
function phpAlert($msg) {
		echo '<script type="text/javascript">alert("' . $msg . '")</script>';
}
*/

define( 'VR_EXTERNAL_IMAGES_DIR' , plugin_dir_path( __FILE__ ) );
define( 'VR_EXTERNAL_IMAGES_URL' , plugins_url( basename( dirname( __FILE__ ) ) ) );

define( 'VR_EXTERNAL_IMAGES_ALLOW_BULK_MESSAGE' , false );

$external_image_count = 0;

$images_count_custom = get_option('vr_external_image_images_count_custom', '200');
if ( $images_count_custom <= 0 || $images_count_custom >= 201 ) {
	$images_count_custom = 200;
}

$posts_count_custom = get_option('vr_external_image_posts_count_custom', '500');
if ( $posts_count_custom <= 0 || $posts_count_custom >= 501 ) {
	$posts_count_custom = 500;
}

require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

add_action( 'admin_menu', 'vr_external_image_menu' );
add_action( 'admin_init', 'vr_external_image_admin_init' );
add_action( 'admin_head' , 'vr_external_images_bulk_resize_admin_javascript' );
add_action( 'admin_notices', 'vr_external_images_bulk_resize_message' , 90 );

// ajax actions
add_action( 'wp_ajax_vr_external_image_get_backcatalog_ajax', 'vr_external_image_get_backcatalog_ajax' );
add_action( 'wp_ajax_vr_external_image_import_all_ajax', 'vr_external_image_import_all_ajax' );


function vr_external_image_admin_init () {		
	global $pagenow;

	register_setting( 'vr_external_image' , 'vr_external_image_whichimgs', 'vr_external_image_images_count_custom', 'vr_external_image_posts_count_custom' );
	
	add_option( 'vr_external_image_images_count_custom', '50' );
	add_option( 'vr_external_image_posts_count_custom', '50' );

	if ( $pagenow == 'post.php' ) {
		add_action( 'post_submitbox_misc_actions', 'vr_import_external_images_per_post' );
		add_action( 'save_post', 'vr_external_image_import_images' );	
	}

	add_filter( 'attachment_link' , 'vr_force_attachment_links_to_link_to_image' , 9 , 3 );		

}

function vr_external_images_bulk_resize_message(){
	global $pagenow;

	if ( VR_EXTERNAL_IMAGES_ALLOW_BULK_MESSAGE ) {		
		$message = '<h4>Please Resize Your Images</h4>';
		$message .= '<p>You may want to resize large images on you previous site before importing images. It will help save bandwidth during the import and prevent the import from crashing.';
		$message .= '<p>You can <a href="https://en-gb.wordpress.org/plugins/regenerate-thumbnails/">download the "Regernate Thumbnails" plugin here.</a></p>';

		if ( $pagenow == 'upload.php' && isset( $_GET['page'] ) && $_GET['page'] == 'vr_external_image' ) {
			echo '<div class="updated fade">';
			echo $message;
			echo '</div>';
		}
	}
}

/**
*
*  AJAX FUNCTIONS
*
**/

/**
* Output the javascript needed for making ajax calls into the header
**/
function vr_external_images_bulk_resize_admin_javascript() {
	echo "<script type=\"text/javascript\" src=\"".VR_EXTERNAL_IMAGES_URL."/import-external-images.js\" ></script>\n";
}
	

/**
* Used in import-external-images.js
**/
function vr_external_image_get_backcatalog_ajax() {
	$posts_to_import = vr_external_image_get_backcatalog();	
	
	if(!empty($posts_to_import)) {
		$response['success'] = true;
		$response['posts'] = $posts_to_import;
	} else {
		$response['success'] = false;
		$response['posts'] = array();
	}

	echo json_encode( $response );
	die();
}

/**
* Used in import-external-images.js
**/
function vr_external_image_import_all_ajax() {
			
	global $wpdb;
	
	$post_id = intval( $_POST['vr_import_images_post'] );
	$response = array();
	
	if (!$post_id) {
		$results = array(
			'success' => false,
			'message' => 'Missing ID Parameter'
		);
		
		echo json_encode($results);
		die();
	}
	
	$post = get_post($post_id);
	
	// do what we need with image...
	$response = vr_external_image_import_images( $post_id , true );
	$postTitle = $post->post_title;
	$postID = $post->ID;
	$postGUID = $post->guid;
	$postView = admin_url('post.php?post=' . $postID . '&action=edit');

	if ($response) {
		$results = '<strong>' . $postTitle . ':</strong> Images imported successfully and image links localised (<a href="' . $postGUID . '" class="button-link" target="_blank">View</a> | <a href="' . $postView . '" class="button-link" target="_blank">Edit</a>).';
	} else {
		$results = "<strong>$postTitle:</strong> No images imported. Check whether they still exist!";
	}

	echo json_encode($results);
	die();
}


/**
*
*	Process Images
*
**/


function vr_force_attachment_links_to_link_to_image( $link , $id ) {

	$object = get_post( $id );

	$mime_types = array( 
		'image/png',
		'image/jpeg',
		'image/jpeg',
		'image/jpeg',
		'image/gif',
		'image/bmp',
		'application/pdf'
	);

	// if this post does not exists on this site, return empty string
	if ( ! $object )
		return '';

	if ( $object && in_array( $object->post_mime_type , $mime_types ) && $object->guid != '' )
		$link = $object->guid;

	return $link;

}

function vr_external_image_menu() {
	add_media_page( 'Import Images', 'Import Images', 'edit_theme_options', 'vr_external_image', 'vr_external_image_options' );
}

/*
* Meta Boxes for hiding pages from main menu
*/
function vr_import_external_images_per_post() {

	$external_images = vr_external_image_get_img_tags( $_GET['post'] );
	$images_count_custom = get_option('vr_external_image_images_count_custom', '200');

	$html = '';
	$images = '';
	$pdfs = '';

	if ( is_array( $external_images ) && count( $external_images ) > 0 ) {

		$html = '<div class="misc-pub-section " id="vr_external-images" style="background-color: #FFFFE0; border-color: #E6DB55;">';
		$html .= '<h4>You have ('.count( $external_images ).') files that can be imported and link updates that can be made.</h4>';
		
		foreach ( $external_images as $external_image ) {

			if( strtolower(pathinfo($external_image, PATHINFO_EXTENSION)) == 'pdf') {
				$cutlen = strlen( $external_image ) < 40  ? strlen( $external_image ) : -40;

				$pdfs .= '<li><small>...' . substr( $external_image, $cutlen) . '</small></li>';
			}
			else {
				$images .= '<li><img style="margin: 3px; max-width:50px;" src="'.$external_image.'" /></li>';	
			}

	}

	$html .= "<style>.image-list li {display: inline;}</style><ul class='image-list'>$images</ul>";
	
	if( strlen( $pdfs ) ) {
		$html .= '<strong>PDFs to Import:</strong>';
		$html .= '<ul class="pdf-list">' . $pdfs . '</ul>';
	}

	$html .= 	'<input type="hidden" name="vr_import_external_images_nonce" id="vr_import_external_images_nonce" value="'.wp_create_nonce( 'vr_import_external_images_nonce' ).'" />';
	$html .= 	'<p><input type="checkbox" name="vr_import_external_images" id="vr_import_external_images" value="vr_import-'.$_GET['post'].'" /> Import External Media?</p>';	
	$html .= 	'<p class="howto">Only ' . $images_count_custom . ' image and link changes will be made at a time to keep things from taking too long.</p>';

	$html .= 	'</div>';
	}
	echo $html;

}

function vr_is_allowed_file( $file ) {
	$file = strtok($file, '?'); //strip off querystring

	$allowed = array( '.jpg' , '.jpe', '.jpeg', '.png', '.bmp' , '.gif',  '.pdf' );
	
	foreach ( $allowed as $ext ) {
		$c = strlen($ext);
		if ( substr( strtolower($file), -$c ) == $ext ) {
			return true;
		}
	}

	return false; 

}

function vr_external_image_import_images( $post_id , $force = false ) {

	global $pagenow;

	if ( get_transient( 'vr_saving_imported_images_' .$post_id ) )
		return;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	if ( isset($_REQUEST['vr_import_external_images_nonce']) ) {
		if ( $force == false && !wp_verify_nonce( $_REQUEST['vr_import_external_images_nonce'] , 'vr_import_external_images_nonce' ) ) 
		return;
	}

	if ( $force == false && $pagenow != 'post.php' )
		return;

	if ( $force == false && $pagenow == 'post.php' && !isset( $_POST['vr_import_external_images'] ) )
		return;

	if (wp_is_post_revision($post_id)) 
		return;

	wp_set_post_lock( $post_id );
	
	$post = get_post($post_id);
	$replaced = false;
	$content = $post->post_content;
	$imgs = vr_external_image_get_img_tags($post_id); // Array of external image URLs
	$images_count_custom = get_option('vr_external_image_images_count_custom', 200);
	
	// $debugNew = array();
	
	$count = 0;
	for ( $i=0; $i<count($imgs); $i++ ) {
		if ( isset($imgs[$i]) && vr_is_allowed_file($imgs[$i]) && $count < $images_count_custom ) {
			$new_img = vr_external_image_sideload( $imgs[$i], $post_id ); // $new_img = Localhost URI of the downloaded image
			if ( $new_img ) {
				$content = str_replace( $imgs[$i], $new_img, $content );
				$replaced = true;
				$count++;
				// $debugNew[] .= $imgs[$i];
			}
		}
	}
	
	if ( $replaced ) {
		set_transient( 'vr_saving_imported_images_'.$post_id , 'true' , HOUR_IN_SECONDS );
		$update_post = array();
		$update_post['ID'] = $post_id;
		$update_post['post_content'] = $content;
		wp_update_post($update_post);
		_fix_attachment_links( $post_id );
		$response = true;
	} else {
		$response = false;
	}
	
	return $response;
	
	// $debugNew = implode( " : ", $debugNew);
	// return $debugNew;
	
}

/*
* Handle importing of external image
* Most of this taken from WordPress function 'media_sideload_image'
* https://developer.wordpress.org/reference/functions/media_sideload_image/
* @param string $file The URL of the image to download
* @param int $post_id The post ID the media is to be associated with
* @param string $desc Optional. Description of the image
* @return string - just the image url on success, false on failure	
*/	
function vr_external_image_sideload( $file, $post_id, $desc = null ) {

		if ( ! empty( $file ) ) {

				// Set variables for storage, fix file filename for query strings.
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|pdf)[\?]?.*\b/i', $file, $matches );
				if ( ! $matches ) {
						return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
				}

				//prepend http/https if necessary (fix for urls that start with //)
				$downloadUrl = $file;
				if (substr($downloadUrl, 0, 2) == '//') {
					$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http");
					$downloadUrl = $scheme.':'.$downloadUrl;
				}

				$file_array = array();
				$file_array['name'] = basename( strtok($matches[0], '?') );
				$file_array['tmp_name'] = download_url( $downloadUrl );

				// If error storing temporarily, unlink.
				if ( is_wp_error( $file_array['tmp_name'] ) ) {
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
					return false;
				}

				$desc = $file_array['name'];
				// Do the validation and storage stuff.
				$id = media_handle_sideload( $file_array, $post_id, $desc );

				// If error storing permanently, unlink.
				if ( is_wp_error( $id ) ) {
						@unlink( $file_array['tmp_name'] );
						return false;
				// If attachment id was requested, return it early.
				} else {
					$src = wp_get_attachment_url( $id );
				}

		}

		// Finally, check to make sure the file has been saved, then return the HTML.
		if ( ! empty( $src ) ) {
		return $src;
			} else {
		return false;
	}
}


function vr_external_image_get_img_tags( $post_id ) {
	$post = get_post( $post_id );
	$w = get_option( 'vr_external_image_whichimgs' );
	$s = home_url();

	$excludes = get_option( 'vr_external_image_excludes' );
	$excludes = explode( ',' , $excludes );


	$result = array();
	preg_match_all( '/<img[^>]* src=[\'"]?([^>\'" ]+)/' , $post->post_content , $matches );
	preg_match_all( '/<a[^>]* href=[\'"]?([^>\'" ]+)/' , $post->post_content , $matches2 );

	$matches[0] = array_merge( $matches[0] , $matches2[0] );
	$matches[1] = array_merge( $matches[1] , $matches2[1] );

	for ( $i=0; $i<count($matches[0]); $i++ ) {
		$uri = $matches[1][$i];
		$uriCheck = strtok($uri, '?'); //strip the querystring if it has one
		$url_parts = parse_url($uriCheck);
		$path_parts = pathinfo($url_parts['path']);

		// check all excluded urls
		if ( is_array( $excludes ) ) {
			foreach( $excludes as $exclude ) {
				$trim = trim( $exclude );
				if ( $trim !='' && strpos( $uriCheck , $trim ) != false )
					$uriCheck = '';
			}
		}

		//only check FQDNs
		if ( $uriCheck != '' && preg_match( '/^https?:\/\//' , $uri ) ) {
			//make sure it's external
			if ( $s != substr( $uriCheck , 0 , strlen( $s ) ) && ( !isset( $mapped ) || $mapped != substr( $uriCheck , 0 , strlen( $mapped ) ) ) ) {
				$path_parts['extension'] = (isset($path_parts['extension'])) ? strtolower($path_parts['extension']) : false;
				if ( $path_parts['extension'] == 'gif' || $path_parts['extension'] == 'jpg' ||  $path_parts['extension'] == 'jpeg' || $path_parts['extension'] == 'bmp' || $path_parts['extension'] == 'png' || $path_parts['extension'] == 'pdf') {
						$result[] = $uri;
				}
			}
		}
	}
	//print_r( $matches );
	$result = array_unique($result);
	return $result;
}

function vr_external_image_backcatalog() {

	$numberposts = get_option('vr_external_image_posts_count_custom', '500');
	$posts = get_posts( array( 'numberposts' => $numberposts, 'post_type' => 'any', 'post_status' => 'any' ) );
	echo '<h4>Processing Posts...</h4>';

	$count = 0;

	$before = '<form style="padding: 10px; margin: 20px 20px 0 0; float: left;" action="" method="post" name="vr_external_image-backcatalog">';
	$resubmit = '<input type="hidden" value="backcatalog" name="action">
		<input class="button-primary" type="submit" value="Process More Posts">';
	$after = '</form>';

	foreach( $posts as $post ) {

		try {
			$imgs = vr_external_image_get_img_tags($post->ID);
			if ( is_array( $imgs ) && count( $imgs ) > 0 ) {

				$count += count( $imgs );

				echo '<p>Post titled: "<strong>'.$post->post_title . '</strong>" - ';
				vr_external_image_import_images( $post->ID , true );
				echo count( $imgs ) . ' Images processed</p>';

			}
		} catch (Exception $e) {
			echo '<em>An error occurred</em>.</p>';
		}
	}

	if($done_message) {
		echo $before;
		echo $done_message;
		echo $resubmit;
		echo $after;
	} else {
		echo '<p>Finished processing past posts!</p>';
	}
}

function vr_external_image_get_backcatalog() {

	/**
	*	Get list of all posts.
	*	Test posts for externally hosted images.
	*	Add posts with external images into $posts_to_import array.
	*	Return array to wherever called.
	**/

	$posts = get_posts( array( 'numberposts' => -1, 'post_type' => 'any', 'post_status' => 'any' ) );
	$numberposts = get_option('vr_external_image_posts_count_custom', '500');
	$count_posts = 0;
	$posts_to_import = array();
	foreach( $posts as $post ) {
		$count_images = 0;

		try {
			$imgs = vr_external_image_get_img_tags($post->ID);

			if ( is_array( $imgs ) && count( $imgs ) > 0 ) {
				$count_images += count( $imgs );
				if ($numberposts >= 1) {
					$numberposts --;
					$posts_to_import[] = $post->ID;
				}
				$count_posts ++;
			}
		} catch (Exception $e) {
			echo '<em>An error occurred</em>.</p>';
		}
	}

	return $posts_to_import;
}

/**
*	Admin Page Display
**/

function vr_external_image_options() {
	$_cats  = '';
	$_auths = '';
	?>

	<style type="text/css">
		#vr_import_posts #processing { background: url(/wp-admin/images/spinner.gif) top left transparent no-repeat; padding: 0 0 0 23px; }
	</style>

	<div class="wrap" style="overflow:hidden; display:inline-block; height:100%;height">
		<div class="icon32" id="icon-generic"><br></div>
		<h2>Import External Images</h2>

		<?php 
			if ( isset( $_POST['action'] ) && $_POST['action'] == 'backcatalog' ) {

				echo '<div id="message" class="updated fade to-remove" style="background-color:rgb(255,251,204); overflow: hidden; margin: 0 0 10px 0">';
				vr_external_image_backcatalog();
				echo '</div>';

			} elseif ( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {
				update_option('vr_external_image_whichimgs', esc_html( $_POST['vr_external_image_whichimgs'] ) );
				update_option('vr_external_image_excludes', esc_html( $_POST['vr_external_image_excludes'] ) );
				update_option('vr_external_image_images_count_custom', esc_html( $_POST['vr_external_image_images_count_custom'] ) );
				update_option('vr_external_image_posts_count_custom', esc_html( $_POST['vr_external_image_posts_count_custom'] ) );

				echo '<div id="message" class="updated fade" style="background-color:rgb(255,251,204);"><p>Settings updated.</p></div>';
			} 
		?>


		<form name="vr_external_image-options" method="post" action="" style="padding: 20px; margin: 20px 20px 0 0 ; float: left; display:block; background: #f6f6f6; border: 1px solid #e5e5e5; ">
			<?php settings_fields('vr_external_image'); ?>
			
			<h2>Options</h2>
			<div style="width: 50%;display:inline;float:left;">
				<p class="howto">We will download externally hosted images found within this site's posts so that those images are hosted locally on this site's web server. Links to externally hosted images will be updated by this process too.</p>
				<h3>How many images and posts to process</h3>
				<p>The import process might stop if there are too many images and posts to process. Select lower values to process per run to improve the import process.</p>
				<p><label for="vr_external_image_images_count_custom">Images per Post</label><br>
					<input type="number" name="vr_external_image_images_count_custom" min="1" max="200" value="<?php echo (get_option('vr_external_image_images_count_custom')); ?>">
				</p>
				<p class="howto">Default is 200. Maximum is 200.</p>

				<p><label for="vr_external_image_posts_count_custom">Posts per Run</label><br>
					<input type="number" name="vr_external_image_posts_count_custom" min="1" max="500" value="<?php echo (get_option('vr_external_image_posts_count_custom')); ?>">
				</p>
				<p class="howto">Default is 500. Maximum is 500.</p>
			</div>

			<div style="width: 50%;display:inline;float:left;">
				<h3>Which external IMG links to process</h3>
				<p>By default, all images hosted on any external site are processed. Use these options to ignore images from certain domains.</p>
				<p>
				<label for="myradio1">
					<input id="myradio1" type="radio" name="vr_external_image_whichimgs" value="all" <?php echo (get_option('vr_external_image_whichimgs')!='exclude'?'checked="checked"':''); ?> /> All images
				</label>
				</p>
				
				<p>
				<label for="myradio2">
					<input id="myradio2" type="radio" name="vr_external_image_whichimgs" value="exclude" <?php echo (get_option('vr_external_image_whichimgs')=='exclude'?'checked="checked"':''); ?> /> Exclude images by domain
				</label>
				</p>
				
				<p><label for="myradio2">Domains to exclude (comma separated):</label></p>
				<p class="howto">Example: smugmug.com, flickr.com, picassa.com, photobucket.com, facebook.com</p>
				<p><textarea style="height:90px; width: 90%;" id="vr_external_image_excludes" name="vr_external_image_excludes"><?php echo ( get_option('vr_external_image_excludes') != '' ? get_option('vr_external_image_excludes') : '' ); ?></textarea></p>

				<div class="submit">
					<input type="hidden" name="vr_external_image_update" value="action" />
					<input type="submit" name="submit" class="button-primary" value="Save Changes" />
				</div>
			</div>
		</form>

		<div id="vr_import_all_images" style="float:left; margin:0px; padding:20px; display:block;">

		<h2 style="margin-top: 0px;">Process all posts</h2>

			<?php

				$posts = get_posts( array( 'numberposts' => -1, 'post_type' => 'any', 'post_status' => 'any' ) );
				$count = 0;
				foreach( $posts as $this_post ) {
					$images = vr_external_image_get_img_tags($this_post->ID);
					if( !empty( $images ) ) {
						$posts_to_fix[$count]['title'] = $this_post->post_title;
						$posts_to_fix[$count]['images'] = $images;
						$posts_to_fix[$count]['id'] = $this_post->ID;
						$posts_to_fix[$count]['guid'] = $this_post->guid;
						$posts_to_fix[$count]['post_type'] = $this_post->post_type;
						$posts_to_fix[$count]['post_date'] = $this_post->post_date;
						$posts_to_fix[$count]['post_modified'] = $this_post->post_modified;
					}
				$count++;	
				}

				$import = '<div style="float:left; margin: 0 10px;">';
				$import .= '<p class="submit" id="bulk-import-examine-button">';
				$import .= '<button class="button-primary" onclick="vr_external_images_import_images();">Import Images Now</button>';
				$import .= '</p>';

				$import .= '<div id="vr_import_posts" style="display:none padding:25px 10px 10px 80px;"></div>';	
				$import .= '<div id="vr_import_results" style="display:none"></div>';			

				$import .= '</div>';

				$html = '';

				if ( !empty($posts_to_fix) ) {
					if ( is_array( $posts_to_fix ) ) {
						$html .= '<p class="howto">This can take a long time for sites with a lot of posts. You can also edit each post individually to import images one post at a time.</p>';
						$html .= '<p class="howto">If images fail to import you might need to clean the database transients with a plugin like WP Sweep or need to clean the post HTML.</p>';
						$html .= '<p class="howto">You should <a class="button-secondary" href="'.admin_url('upload.php?page=vr_external_image').'">refresh the page</a> when done to check if you have more posts to process.</p>';
						$html .= '<p class="howto">Refresh this page to try again if the import process stalls.</p>';

						$html .= $import;
						$html .= '<div id="vr_posts_list" style="padding: 0 5px; margin: 0px; clear:both; ">';
						$html .= '<h4>Here is a look at posts that contain external Images:</h4>';

						$html .= '<table class="widefat">';
						$html .= '<thead>
								<th class="manage-column column-post-count" scope="num"></th>
								<th class="manage-column column-post-type" scope="col">Type</th>
								<th class="manage-column column-date-created date" scope="col">Created</th>
								<th class="manage-column column-date-modified date" scope="col">Modified</th>
								<th class="manage-column column-title" scope="col">Title</th>
								<th class="manage-column column-images" scope="col">Ext. Images</th>
								<th class="manage-column column-edit" scope="col">Edit</th>
								<th class="manage-column column-view" scope="col">View</th>
							</thead>';
						$html .= '<tbody>';
						$num = 1;
						foreach( $posts_to_fix as $post_to_fix ) {
							$html .= '<tr>
									<td class="manage-column column-post-count date" scope="num">' . $num . '</td>
									<td class="manage-column column-date-created date" scope="col">' . $post_to_fix['post_type'] . '</td>
									<td class="manage-column column-date-created date" scope="col">' . $post_to_fix['post_date'] . '</td>
									<td class="manage-column column-date-modified date" scope="col">' . $post_to_fix['post_modified'] . '</td>
									<td class="manage-column column-title" scope="col">' . $post_to_fix['title'] . '</td>
									<td class="manage-column column-images" scope="col">' . count($post_to_fix['images']) . ' images.</td>
									<td class="manage-column column-edit" scope="col"><a href="' . admin_url('post.php?post='.$post_to_fix['id'].'&action=edit') . '" class="button-link" target="_blank">Edit</a>.</td>
									<td class="manage-column column-view" scope="col"><a href="' . $post_to_fix['guid'] . '" class="button-link" target="_blank">View</a>.</td>
								</tr>';
							$num++;
						}
						$html .= '</tbody>';
						$html .= '</table>';
						$html .= '</div>';
					}
				} else {
					$html .= "<p>We didn't find any external images to import or external image links change. You're all set!</p>";

				}
				$html .= '</div>';

				echo $html;

			?>

	</div>
<?php
}
