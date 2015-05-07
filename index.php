<?php
/**
 * Plugin Name: myCRED Points Recalculate
 * Description: This plugin recalculates the points for EXISTING / OLD users from Plugin's Page on backend.
 * Version: 1.0
 * Author: rohankveer
 * Author URI: https://rohanveer1989.wordpress.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * bbPress Compatible: no
 * WordPress Compatible: yes
 * BuddyPress Compatible: no
 */
/**
 * rtp_mycred_recalculate Class
 * 
 * @version 1.0
 */
if ( !class_exists( 'rtp_mycred_recalculate' ) ) {

    class rtp_mycred_recalculate {

	public function __construct() {
	    require_once 'admin/rtp-custom-recalculate.php';
	    add_action( 'admin_enqueue_scripts', array( $this, 'rtp_mycred_load_files' ) );
	    add_action( 'admin_menu', array( $this, 'rtp_points_option_page' ) );
	    add_action( 'init', array( $this, 'rtp_register_session' ) );
	    add_action( 'activated_plugin', array( $this, 'rtp_mycred_redirect' ) );
	    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'rtp_mycred_activation_link' ) );
	    register_activation_hook( __FILE__, array( $this, 'rtp_mycred_activation' ) );
	}

	/**
	 *  start a session if not already started
	 */
	public function rtp_register_session() {
	    // if session is not avaiable create a session to store the offset
	    if ( !session_id() ) {
		session_start();
	    }
	}

	/**
	 *  Redirect to plugin settings page after activation
	 *  @param String $plugin
	 */
	public function rtp_mycred_redirect( $plugin ) {
	    if ( $plugin == plugin_basename( __FILE__ ) ) {
		exit( wp_redirect( admin_url( 'options-general.php?page=rtp-mycred-recalculate' ) ) );
	    }
	}

	/**
	 *  Check if myCRED plugin is activated
	 */
	public function rtp_mycred_activation() {
	    if ( !is_plugin_active( 'mycred/mycred.php' ) ) {
		$message = __( '<style type="text/css">body {color: #444; margin: 0; font-family: "Open Sans",sans-serif; font-size: 13px; line-height: 1.4em; }</style><p>Please activate <strong>my</strong>CRED plugin first!!<br /><strong>my</strong>CRED Points Recalculate plugin is like an addon for <strong>my</strong>CRED plugin.</p>', 'rtp-mycred' );
		$this->rtp_trigger_error( $message, E_USER_ERROR );
	    }
	}

	/**
	 *  Trigger custom error while activation if myCRED plugin doesn't activated
	 * 
	 * @param type $message
	 * @param type $errno
	 */
	public function rtp_trigger_error( $message, $errno ) {
	    if ( isset( $_GET['action'] ) && $_GET['action'] == 'error_scrape' ) {
		echo __( $message, 'rtp-mycred' );
		exit;
	    } else {
		echo __( $message, 'rtp-mycred' );
		exit;
	    }
	}

	/**
	 *  add custom links in plugins page
	 * 
	 * @param String $links 
	 * @return string Returns all links
	 */
	public function rtp_mycred_activation_link( $links ) {
	    $links[] = __( '<a href="' . get_admin_url( null, 'options-general.php?page=rtp-mycred-recalculate' ) . '">Settings</a>', 'rtp-mycred' );
	    $links[] = __( '<a href="https://profiles.wordpress.org/rtcamp/" target="_blank">More plugins by rtCamp</a>', 'rtp-mycred' );
	    return $links;
	}

	/**
	 *  Adds Plugin options page
	 */
	public function rtp_points_option_page() {
	    add_options_page( 'rtp myCRED recalculate', 'rtp myCRED recalculate', 'manage_options', 'rtp-mycred-recalculate', array( $this, 'rtp_mycred_recalculate_setting_page' ) );
	}

	/**
	 * rtp_mycred_recalculate_setting_page
	 * Shows Backend Page options
	 */
	public function rtp_mycred_recalculate_setting_page() {


	    if ( isset( $_SESSION['rtp_offset'] ) ) {
		$offset = $_SESSION['rtp_offset'];
	    } else {
		$offset = $_SESSION['rtp_offset'] = 0;
	    }

	    if ( isset( $_SESSION['rtp_total_users']['total_users'] ) ) {
		$total_users = $_SESSION['rtp_total_users']['total_users'];
	    }


	    $message = '';

	    if ( isset( $total_users ) && isset( $offset ) && $offset == $total_users ) { //IF all users are updated, Reset Session.
		$message = __( 'All the <strong>' . $offset . '</strong> users are updated. Session has been reset. If you want to recalculate points again then Click on the <strong>Calculate Points</strong> button again.', 'rtp-mycred' );
	    } elseif ( $offset < $total_users ) {
		$message = __( 'We have updated points for <span class="totalcount">' . $_SESSION['rtp_offset'] . '</span> Users until now. Click on the <strong>Calculate Points</strong> button to set points for the remaining users.', 'rtp-mycred' );
	    }


	    $option = 'mycred_pref_hooks';
	    $cred_options = get_option( $option );
	    if ( isset( $cred_options ) ) {
		$rtp_my_cred_settings = $cred_options['hook_prefs'];
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
	    }
	    ?>
	    <div class="rtp-mycred-settings-page">
	        <h3><?php echo __( 'MyCRED points recalculate', 'rtp-mycred' ); ?></h3>
		<?php
		if ( isset( $cred_options ) ) {
		    ?>
		    <div class="postbox rtp-mycred-box">
			<div class="rtp-postbox-title"><h4>Your myCRED settings :</h4></div>
			<div class="rtp-mycred-details">
			    <ul>
				<li>Registration Points = <b><?php echo $cred_points_registration; ?></b></li>
				<li>Publishing Content Points :
				    <ul>
					<li class="rtp-inner-points">- Post = <b><?php echo $cred_points_published_post; ?></b></li>
					<li class="rtp-inner-points">- Page = <b><?php echo $cred_points_published_page; ?></b></li>
					<?php
					$args = array(
					    'public' => true,
					    '_builtin' => false
					);

					$post_types = get_post_types( $args, 'objects', 'and' );
					foreach ( $post_types as $post_type ) {
					    $post_label = $post_type->label;
					    $cred_points_custom_post = $cred_option_publishing_content[$post_type->name]['creds'];
					    if ( isset( $cred_points_custom_post ) ) {
						?>
						<li class="rtp-inner-points">- <?php echo $post_label; ?> = <b><?php echo $cred_points_custom_post; ?></b></li>
						<?php
					    }
					}
					?>
				    </ul>
				</li>
				<li>Comment Points = <b><?php echo $cred_points_approved_comment; ?></b></li>
				<?php if ( isset( $rtp_my_cred_settings['rtmedia'] ) ) { ?>
		    		<li>Uploading Content Points :
		    		    <ul>
		    			<li class="rtp-inner-points">- Photo = <b><?php echo $cred_points_media_photo; ?></b></li>
		    			<li class="rtp-inner-points">- Video = <b><?php echo $cred_points_media_video; ?></b></li>
		    			<li class="rtp-inner-points">- Music = <b><?php echo $cred_points_media_music; ?></b></li>
					<?php } ?>
				    </ul>
				</li>
			    </ul>
			</div>
		    </div>
		<?php } ?>
	        <div class="postbox">
	    	<div class="rtp-user-select"><label for="start_of_week">Users limit per Calculation:</label><select name="rtp_user_limit" id="rtp_user_limit">
	    		<option value="10">10</option>
	    		<option value="25">25</option>
	    		<option value="50">50 (Recommended)</option>
	    		<option value="100">100</option>
	    		<option value="200">200</option>
	    		<option value="250">250</option>
	    		<option value="500">500 (Not Recommended)</option>
	    	    </select>
	    	</div>
	        </div>
	        <div class="updated"><p><?php echo __( '<strong>Total Users: ' . $total_users . '</strong>', 'rtp-mycred' ); ?></p></div>
	        <div class="updated messages"><p><?php echo $message; ?></p></div>
	        <div id="rtp-updated-user-list">
	    	<table class="wp-list-table widefat users hidden rtp-mycred-table">
	    	    <tr><th style="width: 70px;">ID</th><th style="width: 190px;">Display Name</th><th>Current Points</th><th>Calculated Points</th><th>Final Points (Assigned)</th></tr>
	    	</table>
	    	<table class="wp-list-table widefat users hidden rtp-mycred-table" id="rtp-mycred-user-list">
	    	</table>
	        </div>
	        <div id="rtp-mycred-calculate-form">
	    	<button class="button-primary" id="rtp-calculate-points"><?php echo __( 'Calculate Points', 'rtp-mycred' ); ?></button>
	        </div>
	    </div>	
	    <?php
	}

	/**
	 * function for enqueue scripts and styles
	 */
	public function rtp_mycred_load_files() {
	    // get myCRED settings
	    $option = 'mycred_pref_hooks';
	    $cred_options = get_option( $option );
	    $rtp_hook_prefs = $cred_options['hook_prefs'];

	    $total_users = count_users();
	    $_SESSION['rtp_total_users'] = $total_users;
	    wp_enqueue_script( 'rtp_points_js', plugins_url( '/js/rtp-mycred-recalculate.js', __FILE__ ), array( 'jquery' ) );
	    wp_enqueue_style( 'rtp_points_css', plugins_url( '/css/rtp-mycred-recalculate.css', __FILE__ ) );
	    // pass my_cred_settings php variable to use with jQuery
	    wp_localize_script( 'rtp_points_js', 'rtp_hook_prefs', $rtp_hook_prefs );
	    wp_localize_script( 'rtp_points_js', 'rtp_total_users', $total_users );
	}

    }

    $rtp_mycred_recalculate = new rtp_mycred_recalculate();
}
