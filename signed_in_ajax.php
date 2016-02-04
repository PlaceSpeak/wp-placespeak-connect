<?php
/**
This page contains the logic to handle an AJAX call after a user has signed in.
*/
$user_id = htmlspecialchars($_GET["user_id"]);
$app_key = htmlspecialchars($_GET["app_key"]);
if($user_id) {
    // Get info from the selected DB
    require_once( dirname(dirname(dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
        $user_storage = get_option('placespeak_user_storage');

    if($user_storage == 'WP_USERS') {
      $user_name = $user_id . '_placespeak';
      $wordpress_user_id = username_exists( $user_name );
      if($wordpress_user_id) {
        // Then check to see which index number this app is for this user, and get specific geo_labels (stored in DB as arrays separated by vertical bars)
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
        $data = array(
            'error'=>'This user does not appear to be in the users table.'
        );
      }
    }
    
    if($user_storage == 'PS_USERS') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'placespeak_users';
        $user_info = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE user_id = " . $user_id);
        if($user_info) {
            // Then check to see which index number this app is for this user, and get specific geo_labels (stored in DB as arrays separated by vertical bars)
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
            $data = array(
                'error'=>'This user does not appear to be in the placespeak_users table.'
            );
        }
    }
}
?>