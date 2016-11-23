<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://placespeak.com
 * @since             0.0.1
 * @package           wp-placespeak-connect
 *
 * @wordpress-plugin
 * Plugin Name:       WP PlaceSpeak Connect
 * Plugin URI:        https://placespeak.com
 * Description:       This plugin allows organizations with PlaceSpeak Connect accounts on Placespeak.com to use geoverification tools on their Wordpress pages.
 * Version:           1.1.2
 * Author:            PlaceSpeak
 * Author URI:        https://placespeak.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-placespeak-connect
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * INSTALLATION AND OPTIONS PAGE
 * 
*/

/**
 * First, create a new table on install to store app information
 * $placespeak_db_version can be used if DB ever needs to be updated
 */
global $placespeak_db_version;
$placespeak_db_version = '1.0';

function ps_placespeak_install() {
	global $wpdb;
	global $placespeak_db_version;

	$table_name = $wpdb->prefix . 'placespeak';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		app_name varchar(50) NOT NULL,
		client_key varchar(50) NOT NULL,
		client_secret varchar(50) NOT NULL,
        archived boolean not null default 0,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
    
    // Here is where you can do updates on a version change, before you set the option
    add_option( 'placespeak_db_version', $placespeak_db_version);
    // Default user WP_USERS for user storage
    add_option( 'placespeak_user_storage', 'WP_USERS');
    // Default show comment meta on front end
    add_option( 'placespeak_commenter_metadata', 'SHOW_DATA');
}
/**
 * Install initial data of sample app
 * 
 */
function ps_placespeak_install_data() {
	global $wpdb;
    
	$table_name = $wpdb->prefix . 'placespeak';
    
    $client_info = $wpdb->get_results("SELECT * FROM " . $table_name); 
    if(count($client_info) == 0 ) {
        $welcome_app = 'Sample App Name';
        $welcome_key = 'No app key entered.';
        $welcome_secret = 'No app secret entered.';

        $table_name = $wpdb->prefix . 'placespeak';

        $wpdb->insert( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ), 
                'app_name' => $welcome_app, 
                'client_key' => $welcome_key,  
                'client_secret' => $welcome_secret
            ) 
        );
    }
}

register_activation_hook( __FILE__, 'ps_placespeak_install' );
register_activation_hook( __FILE__, 'ps_placespeak_install_data' );

/**
 * Radio button in settings that allows user to choose storage of users
 * 
 */
function ps_choose_placespeak_user_table() {
	if ( isset( $_POST['choose-placespeak-user-table'] ) ) {
       $user_storage = $_POST['user_storage'];
        
       if($user_storage == 'WP_USERS') {
           update_option( 'placespeak_user_storage', 'WP_USERS');
       }
       if($user_storage == 'PS_USERS') {
           update_option( 'placespeak_user_storage', 'PS_USERS');
           // Check for table and maybe create it
           global $wpdb;
           $table_name = $wpdb->prefix . 'placespeak_users';
           if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                 //table not in database. Create new table
                 $charset_collate = $wpdb->get_charset_collate();

                 $sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    user_id varchar(50) NOT NULL,
                    first_name varchar(50) NOT NULL,
                    last_name varchar(50) NOT NULL,
                    geo_labels varchar(200) NOT NULL,
                    verifications varchar(200) NOT NULL,
                    access_token varchar(500) NOT NULL,
                    refresh_token varchar(500) NOT NULL,
                    authorized_client_key varchar(500) NOT NULL,
                    UNIQUE KEY id (id)
                 ) $charset_collate;";
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 dbDelta( $sql );
           }
       }
    }
}
ps_choose_placespeak_user_table();

function ps_choose_commenter_metadata() {
	if ( isset( $_POST['choose-commenter-metadata'] ) ) {
       $commenter_metadata = $_POST['commenter_metadata'];
       update_option( 'placespeak_commenter_metadata', $commenter_metadata);
    }
}
ps_choose_commenter_metadata();

/**
 * Checkbox for turning single sign-on on and off. Disabled for now.
 * 
 */
/*
function choose_placespeak_single_sign_on() {
	if ( isset( $_POST['single-sign-on'] ) ) {
       $single_sign_on = $_POST['single_sign_on_checkbox'];
        
       if($single_sign_on == 'single_sign_on') {
           // Here, would save app ID
           update_option('placespeak_single_sign_on', '1');
       } else {
           delete_option('placespeak_single_sign_on');
       }
    }
}
choose_placespeak_single_sign_on();
*/

/**
 * Adding PlaceSpeak item to Settings in wp-admin
 * 
 */
add_action( 'admin_menu', 'ps_plugin_menu' );
function ps_plugin_menu() {
	add_options_page( 'PlaceSpeak Options', 'PlaceSpeak', 'manage_options', 'placespeak', 'ps_plugin_options' );
}
/**
 * PlaceSpeak Options page
 * Has table storage options, ability to add a new app, and listing of apps with ability to edit them and archive
 */
function ps_plugin_options() {
    
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
    // Get plugin data from DB and display
    global $wpdb;

	$table_name = $wpdb->prefix . 'placespeak';
    
    $client_info = $wpdb->get_results("SELECT * FROM " . $table_name); 
    
    // Get count of archived vs published
    $published_count = 0;
    $archived_count = 0;
    for ($i=0;$i<count($client_info); ++$i) {
        if($client_info[$i]->archived!=='0') {
            $archived_count += 1;
        } else {
            $published_count += 1;
        }
    }
    
    // Checking query string to see if there is a filter on app display
    $current_app_status_display = '';
    $query_strings = $_SERVER['QUERY_STRING'];
    $all_query_strings_array = explode("&", $query_strings);
    for($i=0;$i<count($all_query_strings_array);++$i) {
        $each_query_strings_array = explode("=", $all_query_strings_array[$i]);
        if($each_query_strings_array[0]=='app_status') {
            $current_app_status_display = $each_query_strings_array[1];
        }
    }
    
    // Seeing which option the user currently has set for saving user
    $user_storage = get_option('placespeak_user_storage');
    // Seeing if user allows single sign on
    $single_sign_on = get_option('placespeak_single_sign_on');
    // Get commenter metadata 
    $commenter_metadata = get_option( 'placespeak_commenter_metadata');
    ?>

    <style>
        .inline-edit-row fieldset label span.title {
            width: 10em;
        }
        .inline-edit-row fieldset label span.input-text-wrap {
            margin-left: 10em;
        }
        .widefat th.check-column {
            text-align: center;
        }
        .submitLink {
          background-color: transparent;
          text-decoration: none;
          border: none;
          color: #a00;
          cursor: pointer;
          font-size: 13px;
        }
        .submitLink:hover {
          color: red;
        }

        .submitLink:focus {
          outline: none;
        }
    </style>
    
	<div class="wrap">
        <h3>Instructions</h3>
        <p>Redirect URL: <strong><?php echo esc_attr( add_query_arg( array( 'placespeak_oauth' => 'redirect' ), site_url( '/' ) ) ); ?></strong></p>
        <h3>Options</h3>
        <p><strong>User Storage</strong></p>
        <form action="" method="post">
            <input type="radio" name="user_storage" <?php if($user_storage == 'WP_USERS') echo "checked"; ?> value="WP_USERS" />Use default WP_USERS to store verified user information (<strong>default</strong>).
            <br>
            <input type="radio" name="user_storage" <?php if($user_storage == 'PS_USERS') echo "checked"; ?> value="PS_USERS" />Use custom PlaceSpeak user table to store verified user information.
            <br>
            <input type="submit" name="choose-placespeak-user-table" value="Save">
        </form>
        <p><strong>Show commenter metadata</strong></p>
        <form action="" method="post">
            <input type="radio" name="commenter_metadata" <?php if($commenter_metadata == 'SHOW_DATA') echo "checked"; ?> value="SHOW_DATA" />Show data on the front end (<strong>default</strong>).
            <br>
            <input type="radio" name="commenter_metadata" <?php if($commenter_metadata == 'HIDE_DATA') echo "checked"; ?> value="HIDE_DATA" />Show only to admins in the back end.
            <br>
            <input type="submit" name="choose-commenter-metadata" value="Save">
        </form>
        <!-- Single sign on disabled -->
        <!-- <p><strong>Single Sign On</strong></p>
        <form action="" method="post">
            <input type="checkbox" name="single_sign_on_checkbox" <?php // if($single_sign_on!=='') echo "checked"; ?> value="single_sign_on" /> PlaceSpeak users can sign into Wordpress using their PlaceSpeak login.
            <br>
            <input type="submit" name="single-sign-on" value="Save">
        </form> -->
        <h3>Add New PlaceSpeak App</h3>
        <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) ?>" method="post">
            <table>
                <tr><td>App Name</td><td><input type="text" name="app-name" placeholder="App name"></td></tr>
                <tr><td>App Key</td><td><input type="text" name="app-key" placeholder="App Key"></td></tr>
                <tr><td>App Secret</td><td><input type="text" name="app-secret" placeholder="App Secret"></td></tr>
            </table>
            <input type="submit" name="add-new-app">
        </form>
        
        <h3>Current Apps</h3>
        <ul class="subsubsub">
            <li class="all"><a href="options-general.php?page=placespeak&app_status=all" <?php if($current_app_status_display=='all'||!$current_app_status_display) { echo 'class="current"'; } ?>>All <span class="count">(<?php echo ($published_count+$archived_count); ?>)</span></a> |</li>
            <li class="publish"><a href="options-general.php?page=placespeak&app_status=published" <?php if($current_app_status_display=='published') { echo 'class="current"'; } ?>>Published <span class="count">(<?php echo $published_count; ?>)</span></a> |</li>
            <li class="draft"><a href="options-general.php?page=placespeak&app_status=archived" <?php if($current_app_status_display=='archived') { echo 'class="current"'; } ?>>Archived <span class="count">(<?php echo $archived_count; ?>)</span></a></li>
        </ul>
        <table class="wp-list-table widefat fixed pages">
            <thead>
                <tr>
                    <th scope="row" class="check-column">ID</th>
                    <th class="manage-column column-author" id="author" scope="col"
                    style="">App Name</th>
                    <th class="manage-column column-author" id="author" scope="col"
                    style="">App key</th>
                    <th class="manage-column column-author" id="author" scope="col"
                    style="">App secret</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php for ($i=0;$i<count($client_info); ++$i) { ?>
                    <?php if(($current_app_status_display=='published'&&$client_info[$i]->archived=='0')||($current_app_status_display=='archived'&&$client_info[$i]->archived=='1')||$current_app_status_display=='all'||!$current_app_status_display) { ?>
                        <tr <?php if($client_info[$i]->archived!=='0') { echo 'style="background-color: #fefefe;"'; } ?> class=
                        "post-<?php echo $client_info[$i]->id ?> type-page status-publish hentry alternate iedit author-self level-0"
                        id="post-<?php echo $client_info[$i]->id ?>">
                            <th scope="row" class="check-column"><?php echo $client_info[$i]->id ?></th>
                            <td class="post-title page-title column-title">
                                <strong>
                                    <?php echo esc_html( $client_info[$i]->app_name ); ?>
                                </strong>
                                <div class="row-actions" style="visibility:visible;">
                                    <span class="edit">
                                        <span class="inline">
                                            <a style="cursor:pointer;" class="editinline" id="editapp-<?php echo $client_info[$i]->id ?>" title="Edit this item inline">Edit</a>
                                            <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) ?>" method="post" style="display:inline;">
                                                <input id="app-id" name="app-id" type="hidden" value="<?php echo $client_info[$i]->id ?>"> 
                                                <?php if($client_info[$i]->archived!=='1') { ?>
                                                    <input type="submit" class="submitLink" name="archive-app" value="Archive">
                                                <?php } else { ?>
                                                    <input type="submit" class="submitLink" name="unarchive-app" value="Unarchive">
                                                <?php } ?>
                                            </form>
                                        </span>
                                    </span>
                                </div>
                            </td>
                            <td class="author column-author">
                                <?php echo esc_html( $client_info[$i]->client_key ); ?> 
                            </td>
                            <td class="author column-author">
                                <?php echo esc_html( $client_info[$i]->client_secret ); ?> 
                            </td>
                        </tr>
                        <tr class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page alternate inline-editor"
                        id="edit-<?php echo $client_info[$i]->id ?>" style="">
                            <td class="colspanchange" colspan="4">
                                <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) ?>" method="post">
                                    <fieldset class="inline-edit-col-left">
                                        <div class="inline-edit-col">
                                            <h4>Edit</h4>
                                            <label>
                                                <span class="title">App name</span>
                                                <span class="input-text-wrap">
                                                    <input class="ptitle" name="app-name" type="text" value="<?php echo esc_attr( $client_info[$i]->app_name ); ?>">
                                                </span>
                                            </label>
                                            <label>
                                                <span class="title">App key</span>
                                                <span class="input-text-wrap">
                                                    <input class="ptitle" name="app-key" type="text" value="<?php echo esc_attr( $client_info[$i]->client_key ); ?>">
                                                </span>
                                            </label>
                                            <label>
                                                <span class="title">App secret</span>
                                                <span class="input-text-wrap">
                                                    <input class="ptitle" name="app-secret" type="text" value="<?php echo esc_attr( $client_info[$i]->client_secret ); ?>">
                                                </span>
                                            </label>
                                        </div>
                                    </fieldset>
                                    <p class="submit inline-edit-save">
                                        <a accesskey="c" id="cancel-<?php echo $client_info[$i]->id ?>" class="button-secondary cancel alignleft">Cancel</a>
                                        <input id="app-id" name="app-id" type="hidden" value="<?php echo $client_info[$i]->id ?>"> 
                                        <input type="submit" class="button-primary save alignright" name="update-app">
                                        <span class="spinner"></span> 
                                        <br class="clear"></p>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
        <h3>Basic shortcode embed</h3>
        <p>To embed a button that will allow users to sign in and verify themselves with PlaceSpeak, use the following shortcode: <strong>[placespeak_connect id="APP_ID"]</strong>.</p>
        <p>Example: [placespeak_connect id="1"]</p>
        <h3>Choosing a connect button</h3>
        <p>There are a number of different connect buttons you can use. They are listed below. To use them for an app, add the "button" parameter to your shortcode along with the colour description.</p>
        <p>Example: [placespeak_connect id="1" button="dark_blue"]</p>
        <table>
            <tr><td>Green (green)</td><td>Light Blue (light_blue)</td><td>Dark Blue (dark_blue)</td></tr>
            <tr><td><img src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/connect_green.png"></td>
                <td><img src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/connect_light_blue.png"></td>
                <td><img src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/connect_dark_blue.png"></td>
            </tr>
        </table>
	</div>

<script>
    // This script ensures that Edit buttons work correctly and correspond to the right app
    var idArray = [];
    // Create functions for each edit box
    var editButtons = document.getElementsByClassName('editinline');
    for(button in editButtons) {
        if(typeof editButtons[button].id!=='undefined') {
            var thisID = editButtons[button].id.substring(8);
            idArray.push(thisID);
        }
    }
    idArray.forEach(function(element,index,array) {
        var thisEditBox = document.getElementById('edit-'+element);
        var thisEditButton = document.getElementById('editapp-'+element);
        var thisCancelButton = document.getElementById('cancel-'+element);
        thisEditButton.addEventListener('click', function() {
            thisEditBox.style.display = '';
        });
        thisCancelButton.addEventListener('click', function() {
            thisEditBox.style.display = 'none';
        });
        // Hide edit boxes at start
        thisEditBox.style.display = 'none';
    });
    
</script>
<?php }

/**
 * APP OPERATIONS
 * Adding, updating, archiving
*/

/**
 * Adding a new app to DB from PlaceSpeak Options page
 * 
 */
function ps_add_new_app() {

	// if the submit button is clicked, send the email
	if ( isset( $_POST['add-new-app'] ) ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';
        
		// sanitize form values
		$app_name        = sanitize_text_field( $_POST["app-name"] );
		$client_key      = sanitize_text_field( $_POST["app-key"] );
		$client_secret   = sanitize_text_field( $_POST["app-secret"] );
    
        $wpdb->insert( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ), 
                'app_name' => $app_name, 
                'client_key' => $client_key, 
                'client_secret' => $client_secret
            ) 
        );
    }
}
ps_add_new_app();

/**
 * Updating an app from PlaceSpeak Options page
 * 
 */
function ps_update_app() {

	if ( isset( $_POST['update-app'] ) ) {

		// sanitize form values
		$app_id          = $_POST["app-id"];
		$app_name        = sanitize_text_field( $_POST["app-name"] );
		$client_key      = sanitize_text_field( $_POST["app-key"] );
		$client_secret   = sanitize_text_field( $_POST["app-secret"] );
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';

        $wpdb->update( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ), 
                'app_name' => $app_name, 
                'client_key' => $client_key, 
                'client_secret' => $client_secret
            ),
            array( 'ID' => $app_id ),
            array( 
                '%s',
                '%s',
                '%s'
            ),
            array( '%d' )
        );
	}
}
ps_update_app();

/**
 * Archiving an app from PlaceSpeak Options page
 * Set to archived as 0 (false) or 1 (true)
 */
function ps_archive_app() {

	if ( isset( $_POST['archive-app'] ) ) {

		$app_id          = $_POST["app-id"];
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';

        $wpdb->update( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ),
                'archived' => 1
            ),
            array( 'ID' => $app_id ),
            array( 
                '%d',
            ),
            array( '%d' )
        );
	}
}
ps_archive_app();

/**
 * Unarchiving an app from PlaceSpeak Options page
 * Set to archived as 0 (false) or 1 (true)
 */
function ps_unarchive_app() {

	if ( isset( $_POST['unarchive-app'] ) ) {

		$app_id          = $_POST["app-id"];
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';

        $wpdb->update( 
            $table_name, 
            array( 
                'time' => current_time( 'mysql' ),
                'archived' => 0
            ),
            array( 'ID' => $app_id ),
            array( 
                '%d',
            ),
            array( '%d' )
        );
	}
}
ps_unarchive_app();

/**
 * Selection of app on post edit with a dropdown inside a metabox
 * 
 */
add_action( 'edit_form_after_editor', 'ps_select_placespeak_app' );
function ps_select_placespeak_app() {
    global $wpdb;

	$table_name = $wpdb->prefix . 'placespeak';
    
    $client_info = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE archived = 0");
    
    $post_id = intval( $_GET['post'] );
    $current_app_id = get_post_meta( $post_id, 'placespeak_app_id', true);
    if($current_app_id) {
        $current_app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $table_name . " WHERE id = %d;", $current_app_id ) );
    }
    function my_callback($post,$metabox) {
        if($metabox['args']['current_app_id']) { ?>
            <p>Current App: <strong><?php echo esc_html( $metabox['args']['current_app']->app_name ); ?></strong></p>
        <?php } ?>
        <label class="screen-reader-text" for="placespeak_app_id">App for this post/page</label>
        <select name="placespeak_app_id" id="placespeak_app_id">
            <option value="">(no app)</option>
            <!-- Some fiddling here to make sure the options come out correctly -->
            <?php for ($i=0;$i<count($metabox['args']['client_info']); ++$i) { ?>
                <option class="level-0" value="<?php echo $metabox['args']['client_info'][$i]->id ?>" <?php if($metabox['args']['client_info'][$i]->id==$metabox['args']['current_app_id']) { echo "selected='selected'"; } ?>><?php echo esc_html( $metabox['args']['client_info'][$i]->app_name ); ?></option>
            <?php } ?>
        </select>
        <?php 
    }
    // Add meta box for posts and for pages
    $post_types = array('post','page');
    foreach($post_types as $post_type) {
        add_meta_box( 
            'select_placespeak_app', 
            'Select PlaceSpeak App', 
            'my_callback', 
            $post_type, 
            'side', 
            'default',
            array('current_app_id'=>$current_app_id, 'current_app'=>$current_app, 'client_info'=>$client_info )
        );
    }
    ?>
<?php 
}

/**
 * FRONT END SHORTCODE AND BUTTON
 * 
*/

/**
 * Enqueue scripts for the map display and custom JS when button appears
 * This is loaded every time, in case they are using a shortcode
 */
function ps_placespeak_scripts() {
    wp_register_script( 'leaflet-js', plugin_dir_url(__FILE__) . 'js/leaflet.js', array('jquery'));
    wp_register_script( 'polyline-encoded-js', plugin_dir_url(__FILE__) . 'js/polyline.encoded.js', array('jquery','leaflet-js'));
    wp_register_script( 'placespeak-js', plugin_dir_url(__FILE__) . 'js/placespeak.js', array('jquery','leaflet-js','polyline-encoded-js'),'1.0.21');

    wp_enqueue_style( 'leaflet-css', plugin_dir_url(__FILE__) . 'css/leaflet.css' );
    wp_enqueue_style( 'placespeak-css', plugin_dir_url(__FILE__) . 'css/placespeak.css' );

    wp_enqueue_script( 'leaflet-js', plugin_dir_url(__FILE__) . 'js/leaflet.js', array('jquery'),'0.7.7',false);
    wp_enqueue_script( 'polyline-encoded-js', plugin_dir_url(__FILE__) . 'js/polyline.encoded.js', array('leaflet-js'),'0.13',false);
    wp_enqueue_script( 'placespeak-js', plugin_dir_url(__FILE__) . 'js/placespeak.js', array('jquery','leaflet-js','polyline-encoded-js'),'1.0.21',true);
}

add_action( 'wp_enqueue_scripts', 'ps_placespeak_scripts' );

/**
 * Shortcode Connect button, which allows a user to sign in and see map with green dots
 * Admin can add this in widgets, post content, etc
 */
function ps_placespeak_connect_shortcode($atts) {
    // Get shortcode atts
    $shortcode_connect_atts = shortcode_atts( array(
        'id' => '1', // Default just pulls the first one
        'button' => 'green' // Default displays green button
    ), $atts );
    
    $current_app_id = wp_kses_post($shortcode_connect_atts['id']);
    
    global $wpdb;

	$table_name = $wpdb->prefix . 'placespeak';
    
    $client_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $table_name . " WHERE id = %d;", $current_app_id ) );

    $url = $_SERVER['REQUEST_URI'];
    $escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
?>
    
    <div style="font-size:12px !important;">
        <div id="placespeak_connect_button">
            <div style="margin-bottom:10px;">
                <a href="https://placespeak.com/connect/authorize/?client_id=<?php echo esc_attr( urlencode( $client_info->client_key ) ); ?>&response_type=code&scope=user_info&redirect_uri=<?php echo esc_attr( urlencode( add_query_arg( array( 'placespeak_oauth' => 'redirect' ), site_url( '/' ) ) ) ); ?>&state=<?php echo $escaped_url; ?>_<?php echo $client_info->id; ?>">
                    <img src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/connect_<?php echo esc_attr( $shortcode_connect_atts['button'] ); ?>.png">
                </a>
            </div>
        </div>
        <input id="app_key" type="hidden" value="<?php echo esc_attr( $client_info->client_key ); ?>">
        <input id="url_directory" type="hidden" value="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>">
        <div id="verified_by_placespeak" style="display:none;">
            <p>Your comment is verified by PlaceSpeak.<img id="placespeak_verified_question" src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/question.png"</p>
            <div id="placespeak_verified_info" style="display:none;">
                <!-- <div id="placespeak_verified_info_triangle"></div> -->
                Because you have connected to this consultation using PlaceSpeak, PlaceSpeak will verify that your comment isn't spam and confirm your status as a resident (or not) of the consultation area. PlaceSpeak will not share any personal information, such as your address.
            </div>
        </div>
        <div id="placespeak_plugin_map" style="display:none;"></div>
        <div id="powered_by_placespeak" style="display:none;">
            <p>Powered by <img id="powered_by_placespeak_logo" src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/placespeak_logo.png"></p>
        </div>
    </div>

<?php }

add_shortcode('placespeak_connect', 'ps_placespeak_connect_shortcode');
add_filter('widget_text', 'do_shortcode');


/**
 * Adding connect button after form fields if user has selected it for that post
 * Appears whether logged in or not
 * Note: theme must contain appropriate hooks (WP defaults)
 */
add_action( 'comment_form_after_fields', 'ps_placespeak_connect_field', 20 );
add_action( 'comment_form_logged_in_after', 'ps_placespeak_connect_field', 20 );
function ps_placespeak_connect_field() {
    // Gets placespeak_app_id out of the settings, if it's set
    $current_app_id = get_post_meta( get_the_ID(), 'placespeak_app_id', true);
    if($current_app_id) { 
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';

        $client_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $table_name . " WHERE id = %d;", $current_app_id ) );
        
        $url = $_SERVER['REQUEST_URI'];
        $escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );

    ?>
        <div style="font-size:12px !important;margin-bottom:20px;">
            <div id="placespeak_connect_button">
                <div style="margin-bottom:10px;">
                    <a onclick="return saveFormToLocalStorage();" href="https://placespeak.com/connect/authorize/?client_id=<?php echo esc_attr( urlencode( $client_info->client_key ) ); ?>&response_type=code&scope=user_info&redirect_uri=<?php echo esc_attr( urlencode( add_query_arg( array( 'placespeak_oauth' => 'redirect' ), site_url( '/' ) ) ) ); ?>&state=<?php echo esc_attr( $escaped_url ); ?>_<?php echo $client_info->id; ?>">
                        <img src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/connect_dark_blue.png">
                    </a>
                    <div id="pre_verified_by_placespeak" style="display:none;">
                        <img id="placespeak_pre_verified_question" src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/question-grey.png">
                        <div id="placespeak_pre_verified_info" style="display:none;">
                            If you connect to this consultation using PlaceSpeak, PlaceSpeak will verify that your comment isn't spam and confirm your status as a resident (or not) of the consultation area. PlaceSpeak will not share your address.
                        </div>
                    </div>
                </div>
            </div>
            <input id="app_key" type="hidden" value="<?php echo esc_attr( $client_info->client_key ); ?>">
            <input id="url_directory" type="hidden" value="<?php echo esc_attr( plugin_dir_url(__FILE__) ); ?>">
            <div id="verified_by_placespeak" style="display:none;">
                <p>Your comment is verified by PlaceSpeak.<img id="placespeak_verified_question" src="<?php echo esc_url( plugin_dir_url(__FILE__) ); ?>/img/question.png"</p>
                <div id="placespeak_verified_info" style="display:none;">
                    Because you have connected to this consultation using PlaceSpeak, PlaceSpeak will verify that your comment isn't spam and confirm your status as a resident (or not) of the consultation area. PlaceSpeak will not share your address.
                </div>
            </div>
            <div id="placespeak_plugin_map" style="display:none;"></div>
            <div id="powered_by_placespeak" style="display:none;">
                <p>Powered by <img id="powered_by_placespeak_logo" src="<?php echo plugin_dir_url(__FILE__); ?>/img/placespeak_logo.png"></p>
            </div>
        </div>
    <?php 
    }
    
}

/**
 * COMMENT HANDLING AND ADMINISTRATION
 */

/**
 * Saving selected PlaceSpeak app on post edit page
 * 
 */
add_action( 'save_post', 'ps_save_placespeak_app_info' );
function ps_save_placespeak_app_info( $post_id ) {
    update_post_meta( $post_id, 'placespeak_app_id', sanitize_text_field( $_REQUEST['placespeak_app_id'] ) );
}

/**
 * When a comment is saved into the DB, add PlaceSpeak information if it exists
 * These are hidden fields appended inside comment_form after user returns from authenticating an app
 */
add_action( 'comment_post', 'ps_save_user_comment_information' );
function ps_save_user_comment_information($comment_id) {
    // If it has this input field, then it's been verified
    if($_POST['placespeak_verifications']) {
        add_comment_meta( $comment_id, 'placespeak_verified_user', $_POST['placespeak_user_id'] );
        add_comment_meta( $comment_id, 'placespeak_user_name', $_POST['placespeak_user_name'] );
        add_comment_meta( $comment_id, 'placespeak_user_verifications', $_POST['placespeak_verifications'] );
        add_comment_meta( $comment_id, 'placespeak_geo_labels', $_POST['placespeak_geo_labels'] );
            
    }
}

/**
 * Adding columns to comments admin page, showing verification levels, user name, and region inside relevant app if applicable
 * 
 */
add_filter('manage_edit-comments_columns', 'ps_add_new_comments_columns');
function ps_add_new_comments_columns($comments_columns) {
    $comments_columns['placespeak_verified'] = 'PlaceSpeak Verified';
    $comments_columns['placespeak_user_name'] = 'PlaceSpeak User Name';
    $comments_columns['placespeak_region'] = 'PlaceSpeak App Region';
    return $comments_columns;
}
/**
 * Populating columns created above
 * 
 */
add_action('manage_comments_custom_column','ps_manage_comments_columns',10,2);
function ps_manage_comments_columns($column_name, $id) {
    switch ($column_name) {
        case 'placespeak_verified':
            $user_id = get_comment_meta($id,'placespeak_verified_user',true);
            $verifications = get_comment_meta($id,'placespeak_user_verifications',true);
            $json_verifications = json_decode($verifications);
            if($user_id) {
                foreach($json_verifications as $key=>$verification_level) {
                    if($verification_level == 'True') {
                        echo esc_html( ucfirst($key) ) . ': <img style="width:15px;" src="' . esc_url( plugin_dir_url(__FILE__) ) . '/img/verified_checkbox.png""><br>';
                    } else {
                        echo esc_html( ucfirst($key) ) . ': Not verified<br>';
                    }
                }
            }
            break;
        case 'placespeak_user_name':
            $user_name = get_comment_meta($id,'placespeak_user_name',true);
            if($user_name) {
                echo esc_html( $user_name );
            }
            break;
        case 'placespeak_region':
            $user_geo_labels = get_comment_meta($id,'placespeak_geo_labels',true);
            if($user_geo_labels) {
                echo esc_html( $user_geo_labels );
            } else {
                if(get_comment_meta($id,'placespeak_verified_user',true)) {
                    echo "None";
                }
            }
            break;
        default:
            break;
    }
}

// Adding comment meta data if required. If the comment-meta plugin is installed it will integrate seamlessly.
function ps_add_area_metadata($comment) {
     $commenter_metadata_option = get_option( 'placespeak_commenter_metadata' );
     if($commenter_metadata_option=='SHOW_DATA') {
         // Check for labels and append them if they exist
         $comment_metadata = '';
         $comment_metadata_text = get_comment_meta( get_comment_ID(), 'placespeak_geo_labels',true);
         if($comment_metadata_text==true&&$comment_metadata_text!=='None'&&$comment_metadata_text!=='') {
            $comment_metadata = '<br><br><strong>PlaceSpeak verified area</strong>: ' . $comment_metadata_text;
         } elseif($comment_metadata_text==true) {
            $comment_metadata = '<br><br><strong>PlaceSpeak verified area</strong>: Not inside this consultation area.';
         }
         return $comment . $comment_metadata;
     } else {
         // No option selected, just give comment as usual
         return $comment;
     }
         
}
add_filter('comment_text', 'ps_add_area_metadata', 1000);

add_action( 'init', 'ps_init' );
// Main initialization, with core loaded up.
function ps_init() {
	// OAuth redirect and verify handlers
	if ( !empty( $_GET['placespeak_oauth'] ) ) {
		switch ( $_GET['placespeak_oauth'] ) {
			case 'redirect':
				require dirname( __FILE__ ) . '/oauth_redirect.php';
				exit;
			case 'check':
				require dirname( __FILE__ ) . '/signed_in_ajax.php';
				exit;
		}
	}
}

/**
 * SINGLE SIGN ON
 * REMOVED IN 1.0.0
*/
 
/**
 * Adding button to wp-login.php
 * 
add_filter('login_message', 'add_placespeak_single_sign_on');
function add_placespeak_single_sign_on() {
    $single_sign_on = get_option('placespeak_single_sign_on');
    if($single_sign_on!=='') {
        $app_id = $single_sign_on;
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'placespeak';
        
        $single_sign_on_app = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE id = " . $app_id);
        
        $url = $_SERVER['REQUEST_URI'];
        $url = urlencode($url);
        $escaped_url = htmlspecialchars( $url, ENT_QUOTES );
        
        ?>
        <p style="text-align:center;">
            <a href="https://placespeak.com/connect/authorize/?client_id=<?php echo $single_sign_on_app->client_key ?>&response_type=code&scope=user_info&redirect_uri=<?php echo plugin_dir_url(__FILE__); ?>oauth_redirect.php&state=<?php echo $escaped_url; ?>_<?php echo $single_sign_on_app->id; ?>">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>/img/connect_dark_blue.png">
            </a>    
        </p>

        <?php
    }
}
*/

?>
