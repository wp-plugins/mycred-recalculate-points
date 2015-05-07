<?php

/**
 * returns the count of media uploaded by user
 * @param type $userid
 * @param type $media_type string type of media
 */
function get_media_count( $userid, $media_type ) {
    $media_args = array(
	'author' => $userid,
	'post_type' => 'attachment',
	'numberposts' => -1,
	'post_mime_type' => $media_type
    );
    $rtp_media_files = get_posts( $media_args );
    return count( $rtp_media_files );
}

/**
 * get_approved_comments_count gives total number of approved comments by userid.
 * @param int $userid
 * @return int returns approved comments count for user
 */
function get_approved_comments_count( $userid, $comment_status ) {
    $args = array(
	'status' => $comment_status,
	'user_id' => $userid,
	'count' => TRUE,
    );
    return get_comments( $args );
}

/**
 * rtp_get_user_posts_count returns count of published posts by given $userid
 * @param int $userid
 * @param string $post_type
 */
function rtp_get_user_posts_count( $userid, $post_type ) {
    // WP_Query arguments
    $args = array(
	'post_type' => $post_type,
	'author' => $userid,
	'posts_per_page' => '500',
	'status' => 'publish'
    );

    // The Query
    $query = new WP_Query( $args );
    wp_reset_postdata();
    return $query->post_count;
}

/**
 *  handle ajax call
 */
function rtp_calculate_points() {

    // get mycred setting so that we can call add_to_log function
    $rtp_mycred = mycred_get_settings();

    // get userid for post
    if ( isset( $_POST['user_id'] ) ) {
	$user_id = $_POST['user_id'];
	$user_data = get_userdata( $user_id );
    } else {
	echo json_encode( array( 'updated' => FALSE, 'message' => 'User ID not found.' ) );
	die();
    }
    $user_points = 0.00;

    // get mycred settings data
    if ( isset( $_POST['rtp_mycred_settings'] ) ) {
	$rtp_my_cred_settings = $_POST['rtp_mycred_settings'];
	if ( isset( $rtp_my_cred_settings['rtmedia'] ) ) {
	    $cred_points_media = $rtp_my_cred_settings['rtmedia']['new_media'];
	    $cred_points_media_photo = $cred_points_media['photo'];
	    $cred_points_media_video = $cred_points_media['video'];
	    $cred_points_media_music = $cred_points_media['music'];
	}
	$cred_points_registration = $rtp_my_cred_settings['registration']['creds'];
	$cred_option_publishing_content = $rtp_my_cred_settings['publishing_content'];
	$cred_points_published_post = $cred_option_publishing_content['post']['creds'];
	$cred_points_published_page = $cred_option_publishing_content['page']['creds'];
	$cred_option_comment = $rtp_my_cred_settings['comments'];
	$cred_points_approved_comment = $cred_option_comment['approved']['creds'];
	$cred_points_spam_comment = $cred_option_comment['spam']['creds'];
    } else {
	echo json_encode( array( 'updated' => FALSE, 'message' => 'myCRED data not found.' ) );
	die();
    }

    
    // calculate points for media
    if ( isset( $rtp_my_cred_settings['rtmedia'] ) ) {
	$rtp_user_music_count = get_media_count( $user_id, 'audio' );
	if ( !empty( $rtp_user_music_count ) ) {
	    $user_points = $user_points + ((float) $rtp_user_music_count * (float) $cred_points_media_music);
	}
	$rtp_user_image_count = get_media_count( $user_id, 'image' );
	if ( !empty( $rtp_user_image_count ) ) {
	    $user_points = $user_points + ((float) $rtp_user_image_count * (float) $cred_points_media_photo);
	}
	$rtp_user_video_count = get_media_count( $user_id, 'video' );
	if ( !empty( $rtp_user_video_count ) ) {
	    $user_points = $user_points + ((float) $rtp_user_video_count * (float) $cred_points_media_video);
	}
    }

    // calculate points for approved comments 

    $approved_comments = get_approved_comments_count( $user_id, 'approve' );
    if ( !empty( $approved_comments ) ) {
	$user_points = $user_points + ((float) $approved_comments * (float) $cred_points_approved_comment);
    }

    // calculate points for spam comments 

    $spam_comments = get_approved_comments_count( $user_id, 'spam' );
    if ( !empty( $spam_comments ) ) {
	$user_points = $user_points + ((float) $spam_comments * (float) $cred_points_spam_comment);
    }

    // calculate points for published post

    $user_post_count = rtp_get_user_posts_count( $user_id, 'post' );
    if ( !empty( $user_post_count ) ) {
	$user_points = $user_points + ((float) $user_post_count * (float) $cred_points_published_post);
    }

    // calculate points for published page

    $user_page_count = rtp_get_user_posts_count( $user_id, 'page' );
    if ( !empty( $user_page_count ) ) {
	$user_points = $user_points + ((float) $user_page_count * (float) $cred_points_published_page);
    }

    // calculate points for each custom post types

    $args = array(
	'public' => true,
	'_builtin' => false
    );

    $post_types = get_post_types( $args, 'names', 'and' );
    foreach ( $post_types as $post_type ) {

	$cred_points_custom_post = $cred_option_publishing_content[$post_type]['creds'];
	$custom_post_count = rtp_get_user_posts_count( $user_id, $post_type );
	$user_points = $user_points + ((float) $cred_points_custom_post * (float) $custom_post_count);
    }

    // add registration points for user

    $user_points = $user_points + (float) $cred_points_registration;

    // get users current points 
    $old_points = $user_points;
    $current_cred_points = get_user_meta( $user_id, 'mycred_default', TRUE );
    
    $updated_old_user = false;
    
    if ( !empty( $current_cred_points ) ) {
	// compare our points and user current points
	if ( (float) $user_points > (float) $current_cred_points ) {
	    $final_points = $user_points;
	    // update user meta data
	    update_user_meta( $user_id, 'mycred_default', $user_points );
	    // add to mycred log
	    $rtp_mycred->add_to_log(
		    'rtp_mycred_recalculate', $user_id, $user_points, 'rtp recalculated points'
	    );
            
            $updated_old_user = true;
            
	} else if ( (float) $user_points == (float) $current_cred_points ) {
	    // do nothing
	    $final_points = $user_points;
	} else {
	    $final_points = $current_cred_points;
	}
    } else {
	$final_points = $user_points;
	// if mycred_default key not set means it is an old user so update the points
	update_user_meta( $user_id, 'mycred_default', $user_points );
	// add to mycred log
	$rtp_mycred->add_to_log(
		'rtp_mycred_recalculate', $user_id, $user_points, 'rtp recalculated points'
	);
        
        $updated_old_user = true;

    }

    // store old points for backup
    // first check if meta already exists
    $rtp_old_points_exists = get_user_meta( $user_id, 'rtp_mycred_old_points', TRUE );
    if ( $rtp_old_points_exists != '' ) {
	add_user_meta( $user_id, 'rtp_mycred_old_points', $old_points );
    }
    if ( isset($_SESSION['rtp_offset']) ) { 
        $_SESSION['rtp_offset'] ++;
    }
    
    // return username and points in response
    echo json_encode( array( 'updated' => TRUE, 'username' => $user_data->display_name, 
        'finalpoints' => $final_points, 
        'currentpoints' => $current_cred_points, 'calculatedpoints' => $user_points, 
        'total_offset' => $_SESSION['rtp_offset'], 'updated_old_user' => $updated_old_user ) );
    die();
}

add_action( 'wp_ajax_nopriv_calculate_points', 'rtp_calculate_points' );
add_action( 'wp_ajax_calculate_points', 'rtp_calculate_points' );

/**
 *  ajax function to return users list
 */
function rtp_get_users_list() {

    // get offset from session
    if ( isset( $_SESSION['rtp_offset'] ) ) {
	$offset = $_SESSION['rtp_offset'];
    } else {
	$offset = $_SESSION['rtp_offset'] = 0;
    }

    
    if( isset( $_SESSION['rtp_total_users']['total_users'] ) && isset($offset) 
        && $offset == $_SESSION['rtp_total_users']['total_users']  ) { //IF all users are updated, Reset Session.
        $_SESSION['rtp_offset'] = 0;
	echo json_encode( array( 'all_done' => __('All the <strong>'.$offset.'</strong> users are updated. Session has been reset. Refesh the page or click on the <strong>Reload</strong> button to Recalculate points.', 'rtp-mycred') ) );
        die();
    }
    
    // get user limit to set the offset
    if ( isset( $_POST['user_limit'] ) ) {
	$user_limit = $_POST['user_limit'];
    } else {
	$user_limit = '50';
    }
    
    $args = array(
	'number' => $user_limit,
	'offset' => $offset
    );
    $user_query = new WP_User_Query( $args );
    $rtp_old_users = $user_query->get_results();
   
    // If all users are completed then reset the session

    if ( count( $rtp_old_users ) == 0 ) {
	echo json_encode( array( 'fetched' => FALSE ) );
    } else {
	echo json_encode( array( 'fetched' => TRUE, 'userlist' => $rtp_old_users ) );
    }
    die();
}

add_action( 'wp_ajax_nopriv_rtp_get_users_list', 'rtp_get_users_list' );
add_action( 'wp_ajax_rtp_get_users_list', 'rtp_get_users_list' );

