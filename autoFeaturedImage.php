<?php
/**
 * Plugin Name: autoFeaturedImage
 * Description: auto create a featured image from inlined images in Wordpress (<img src="...")
 * Author: Mark Watkins
 * Version: 0.1
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// an amalgam of:
// https://wordpress.stackexchange.com/questions/3029/how-to-retrieve-image-from-url-and-set-as-featured-image-post-thumbnail
// https://wordpress.stackexchange.com/questions/57060/set-first-image-external-as-featured-image-thumbnail
// https://www.isitwp.com/automatically-set-the-featured-image/
// https://codex.wordpress.org/Function_Reference/media_sideload_image


// required libraries for media_sideload_image
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

// via https://www.smashingmagazine.com/2011/03/ten-things-every-wordpress-plugin-developer-should-know/
function log_me($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}


/**
 * via: https://wordpress.stackexchange.com/questions/57060/set-first-image-external-as-featured-image-thumbnail
 * Extracts the first image in the post content
 * @param object $post the post object
 * @return bool|string false if no images or img src
 */


function extract_image( $post ) {
  $html = $post->post_content;

  return extract_image_string ($html);
}

function extract_image_string($string) {
  log_me('doing extract image string on');
  log_me($string);
  if ( stripos( $string, '<img' ) !== false ) {
    // $regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
    $regex = "/\<img.+src\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>/";
    preg_match( $regex, $string, $matches );
  log_me($matches);
unset( $regex );
    unset( $string );
    if ( is_array( $matches ) && ! empty( $matches ) ) {
      return  $matches[1];

    } else {
      return false;
    }
  } else {
    return false;
  }
}

/*
 * running all over the internet. I found it here:
 * https://www.isitwp.com/automatically-set-the-featured-image/
 * and modified it to sideload the first found img tag, if nothing already set.
 */

function autoset_featured() {
  global $post;
  
  $already_has_thumb = has_post_thumbnail($post->ID);
  if (!$already_has_thumb)  {
    $attached_image = get_children( "post_parent=$post->ID&post_type=attachment&post_mime_type=image&numberposts=1" );

    if(!$attached_image) {
      $postImage = extract_image($post);
      if($postImage) {
	$result = media_sideload_image($postImage, $post->ID, "test first image");
log_me('result of sideload');
	      log_me($result);
	      if($result) {
	  $attached_image = get_children( "post_parent=$post->ID&post_type=attachment&post_mime_type=image&numberposts=1" );
	}
      }
    }
	
    if ($attached_image) {
      foreach ($attached_image as $attachment_id => $attachment) {
	set_post_thumbnail($post->ID, $attachment_id);
      }
    }
  }
}

add_action('the_post', 'autoset_featured');
add_action('save_post', 'autoset_featured');
add_action('draft_to_publish', 'autoset_featured');
add_action('new_to_publish', 'autoset_featured');
add_action('pending_to_publish', 'autoset_featured');
add_action('future_to_publish', 'autoset_featured');

?>
