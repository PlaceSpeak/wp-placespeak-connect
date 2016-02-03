<?php
/**
This page contains the logic to handle an AJAX call after a user has signed in.
*/
$user_id = htmlspecialchars($_GET["user_id"]);
if($user_id) {
    // Get info from the selected DB
    require_once( dirname(dirname(dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
        $user_storage = get_option('placespeak_user_storage');

    if($user_storage == 'WP_USERS') {
      $user_name = $user_id . '_placespeak';
      $wordpress_user_id = username_exists( $user_name );
      if($wordpress_user_id) {
        $data = array(
            'user_id'=>$user_id,
            'first_name'=>get_user_meta($wordpress_user_id,'placespeak_first_name', true),
            'last_name'=>get_user_meta($wordpress_user_id,'placespeak_last_name', true),
            'verifications'=>get_user_meta($wordpress_user_id,'placespeak_verifications', true)
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
            $data = array(
                'user_id'=>$user_id,
                'first_name'=>$user_info[0]->first_name,
                'last_name'=>$user_info[0]->last_name,
                'verifications'=>$user_info[0]->verifications
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