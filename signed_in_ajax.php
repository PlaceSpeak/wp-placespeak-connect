<?php
/**
 * Takes care of handling the AJAX calls for appending hidden fields in comment_form, after authorization has occurred
 *
 *
 * @link       https://placespeak.com
 * @since      1.0.0
 *
 * @package    wp-placespeak-connect
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * First, get the user_id and the app_key from the query string
 * 
 */
$user_id = htmlspecialchars($_GET["user_id"]);
$app_key = htmlspecialchars($_GET["app_key"]);

/**
 * If user_id exists, then 
 * The index number of the app is appended on the end of the state variable, needs to be parsed out
 */
if($user_id) {
    /**
     * Load WP functions
     *
     */
    require_once( dirname(dirname(dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
    /**
     * Depending on user storage option, get user information out of DB
     * Returned to JS as jsonp
     */
    $user_storage = get_option('placespeak_user_storage');
    if($user_storage == 'WP_USERS') {
      $user_name = $user_id . '_placespeak';
      $wordpress_user_id = username_exists( $user_name );
      if($wordpress_user_id) {
        // Check to see which index number this app is for this user, and get specific geo_labels (stored in DB as arrays separated by vertical bars)
        $authorized_client_keys = explode(',',get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true));
        $geo_labels = explode('|',get_user_meta($wordpress_user_id,'placespeak_geo_labels', true));
        $these_geo_labels = '';
        foreach($authorized_client_keys as $key=>$client_key) {
            if($client_key == $app_key) {
                $these_geo_labels = $geo_labels[$key];
            }
        }
        $data = array(
            'user_id'=>$user_id,
            'first_name'=>get_user_meta($wordpress_user_id,'placespeak_first_name', true),
            'last_name'=>get_user_meta($wordpress_user_id,'placespeak_last_name', true),
            'verifications'=>get_user_meta($wordpress_user_id,'placespeak_verifications', true),
            'geo_labels'=>$these_geo_labels
        );
        echo $_GET['callback'] . '('.json_encode($data).')';
      } else {
        /**
         * If user doesn't exist
         *
         */
        $data = array(
            'error'=>'This user does not appear to be in the users table.'
        );
      }
    }
    if($user_storage == 'PS_USERS') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'placespeak_users';
        $query_array = [$user_id];
        $user_info = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . $table_name . " WHERE user_id = %d",
                $query_array
            )
        );
        if($user_info) {
            // Check to see which index number this app is for this user, and get specific geo_labels (stored in DB as arrays separated by vertical bars)
            $authorized_client_keys = explode(',',get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true));
            $geo_labels = explode('|',get_user_meta($wordpress_user_id,'placespeak_geo_labels', true));
            $these_geo_labels = '';
            foreach($authorized_client_keys as $key=>$client_key) {
                if($client_key == $app_key) {
                    $these_geo_labels = $geo_labels[$key];
                }
            }
            $data = array(
                'user_id'=>$user_id,
                'first_name'=>$user_info[0]->first_name,
                'last_name'=>$user_info[0]->last_name,
                'verifications'=>$user_info[0]->verifications,
                'geo_labels'=>$these_geo_labels
            );
            echo $_GET['callback'] . '('.json_encode($data).')';
        } else {
            
            /**
             * If user doesn't exist
             *
             */
            $data = array(
                'error'=>'This user does not appear to be in the placespeak_users table.'
            );
        }
    }
}
?>