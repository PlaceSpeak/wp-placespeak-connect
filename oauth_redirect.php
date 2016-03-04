<?php
/**
 * Takes care of handling the redirect from PlaceSpeak.
 *
 *
 * @link       https://placespeak.com
 * @since      1.0.0
 *
 * @package    wp-placespeak-connect
 */

/**
 * First, get the path and app information from the query string
 * The index number of the app is appended on the end of the state variable, needs to be parsed out
 */
$state = htmlspecialchars($_GET["state"]);
$state = urldecode($state);
$index_position = strrpos($state, '_', -1);
$app_id = str_replace('_','',strrchr($state,'_'));
$old_url = substr($state,0,$index_position);

/**
 * Load WP functions
 * 
 */
require_once( dirname(dirname(dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

/**
 * Get relevant app information out of DB
 * 
 */
global $wpdb;
$table_name = $wpdb->prefix . 'placespeak';
$query_array = [$app_id];
$client_info = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM " . $table_name . " WHERE id = %d",
        $query_array
    )
);
$client_id = $client_info->client_key;
$client_secret = $client_info->client_secret;
$redirect_uri = plugin_dir_url(__FILE__) . 'oauth_redirect.php';

/**
 * If request has returned a code, then send the authorization request with cURL
 * 
 */
if(isset($_GET["code"])){
    $code = $_GET["code"];
    $url = 'https://www.placespeak.com/connect/token/';
    $myvars = 'client_id=' . $client_id . '&client_secret=' . $client_secret . '&redirect_uri=' . $redirect_uri . '&code=' . $code . '&grant_type=authorization_code';
    
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt( $ch, CURLOPT_HEADER, 0);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec( $ch );
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
      if($httpcode==200) { 
        $response_json = json_decode($response);
    
        /**
         * If first auth request is successful, then do another with the access_token
         * After success, put information into appropriate DB depending on what user has selected in Options page
         */
        $header = array();
        $header[] = 'AUTHORIZATION: Bearer ' . $response_json->{'access_token'};
        $ch2 = curl_init();
        curl_setopt( $ch2, CURLOPT_URL, 'https://www.placespeak.com/connect/api/user_info/');
        curl_setopt( $ch2, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, 1);
        $response2 = curl_exec( $ch2 );
        $httpcode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if($httpcode2==200) { 
          $response_json2 = json_decode($response2);
            
        /**
         * Removing any extra formatting (arrays, etc) to get information into DB in consistent manner
         * 
         */
          $fixed_verifications = str_replace('True','"True"',str_replace('False','"False"',str_replace("'",'"',$response_json2->{'verifications'})));
          $user_id               = $response_json2->{'id'};
          $first_name            = $response_json2->{'first_name'};
          $last_name             = $response_json2->{'last_name'};
          $geo_labels            = implode(",",$response_json2->{'geo_labels'});
          $verifications         = $fixed_verifications;
          $access_token          = $response_json->{'access_token'};
          $refresh_token         = $response_json->{'refresh_token'};
          $authorized_client_key = $client_info->client_key;
        
          $user_storage = get_option('placespeak_user_storage');
        
        /**
         * If they have selected WP_USERS, then store as Wordpress users
         * 
         */
          if($user_storage == 'WP_USERS') {
              $user_name = $user_id . '_placespeak';
              $wordpress_user_id = username_exists( $user_name );
              if ( $wordpress_user_id == true ) {
                // User exists - update fields
                $existing_geo_labels = explode("|", get_user_meta($wordpress_user_id,'placespeak_geo_labels', true));
                $access_tokens = explode(",", get_user_meta($wordpress_user_id,'placespeak_access_token', true));
                $refresh_tokens = explode(",", get_user_meta($wordpress_user_id,'placespeak_refresh_token', true));
                $client_keys = explode(",", get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true));
                $this_key_exists = false;
                  
                foreach($client_keys as $key=>$single_client_key) {
                    // Check if this client key exists and what index it's at
                    if($single_client_key == $authorized_client_key) {
                        $this_key_exists = true;
                        // Replace its values with the new ones in all the arrays
                        $existing_geo_labels[$key] = $geo_labels; // This is already made into a string
                        $access_tokens[$key] = $access_token;
                        $refresh_tokens[$key] = $refresh_token;
                    }
                }
                if($this_key_exists) {
                    $new_geo_labels = implode("|", $existing_geo_labels);
                    $new_client_keys = get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true); // stays the same
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                } else {
                    array_push($existing_geo_labels,$geo_labels);
                    array_push($access_tokens,$access_token);
                    array_push($refresh_tokens,$refresh_token);
                    array_push($client_keys,$authorized_client_key);
                    $new_geo_labels = implode("|",$existing_geo_labels);
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                    $new_client_keys = implode(",",$client_keys);
                }
                  
                update_user_meta( $wordpress_user_id, 'placespeak_user_id', $user_id);
                update_user_meta( $wordpress_user_id, 'placespeak_first_name', $first_name);
                update_user_meta( $wordpress_user_id, 'placespeak_last_name', $last_name);
                update_user_meta( $wordpress_user_id, 'placespeak_geo_labels', $new_geo_labels);
                update_user_meta( $wordpress_user_id, 'placespeak_verifications', $verifications);
                update_user_meta( $wordpress_user_id, 'placespeak_access_token', $new_access_tokens);
                update_user_meta( $wordpress_user_id, 'placespeak_refresh_token', $new_refresh_tokens);
                update_user_meta( $wordpress_user_id, 'placespeak_authorized_client_key', $new_client_keys);
                  
                /**
                 * Sign in to WP after authenticating app
                 * 
                 */
                /*
                $single_sign_on = get_option('placespeak_single_sign_on');
                if($single_sign_on!=='') {
                    $wordpress_user = get_user_by( 'id', $wordpress_user_id );
                    wp_set_current_user( $wordpress_user_id, $wordpress_user->user_login );
                    wp_set_auth_cookie( $wordpress_user_id );
                    do_action( 'wp_login', $wordpress_user->user_login );
                } */
              } else {
                // User does not exist, create new user with meta information
                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                $userdata = array(
                    'user_login'  =>  $user_name,
                    'user_pass'   =>  $random_password,
                    'user_nicename' => $user_name,
                    'display_name' => $first_name . ' ' . $last_name,
                    'first_name' => $first_name,
                    'last_name' => $last_name
                );
                $wordpress_user_id = wp_insert_user( $userdata ) ;
                  
                add_user_meta( $wordpress_user_id, 'placespeak_user_id', $user_id);
                add_user_meta( $wordpress_user_id, 'placespeak_first_name', $first_name);
                add_user_meta( $wordpress_user_id, 'placespeak_last_name', $last_name);
                add_user_meta( $wordpress_user_id, 'placespeak_geo_labels', $geo_labels);
                add_user_meta( $wordpress_user_id, 'placespeak_verifications', $verifications);
                add_user_meta( $wordpress_user_id, 'placespeak_access_token', $access_token);
                add_user_meta( $wordpress_user_id, 'placespeak_refresh_token', $refresh_token);
                add_user_meta( $wordpress_user_id, 'placespeak_authorized_client_key', $authorized_client_key);
                  
                /**
                 * Sign in to WP after authenticating app
                 * 
                 */
                /*
                $single_sign_on = get_option('placespeak_single_sign_on');
                if($single_sign_on!=='') {
                    $wordpress_user = get_user_by( 'id', $wordpress_user_id );
                    wp_set_current_user( $wordpress_user_id, $wordpress_user->user_login );
                    wp_set_auth_cookie( $wordpress_user_id );
                    do_action( 'wp_login', $wordpress_user->user_login );
                } */
              }
          }
            
        /**
         * If they have selected placespeak_users, then store in that table (no login option available)
         * 
         */
          if($user_storage == 'PS_USERS') {
            global $wpdb;

            $table_name = $wpdb->prefix . 'placespeak_users';
              
            $client_info = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE user_id = " . $user_id);
            if($client_info) {
                // User exists - update fields
                $existing_geo_labels = explode("|", $client_info['geo_labels']);
                $access_tokens = explode(",", $client_info['access_token']);
                $refresh_tokens = explode(",", $client_info['refresh_token']);
                $client_keys = explode(",", $client_info['authorized_client_key']);
                $this_key_exists = false;
                foreach($client_keys as $key=>$single_client_key) {
                    // Check if this client key exists and what index it's at
                    if($single_client_key == $authorized_client_key) {
                        $this_key_exists = true;
                        // Replace its values with the new ones in all the arrays
                        $existing_geo_labels[$key] = $geo_labels; // This is already made into a string
                        $access_tokens[$key] = $access_token;
                        $refresh_tokens[$key] = $refresh_token;
                    }
                }
                if($this_key_exists) {
                    $new_geo_labels = implode("|", $existing_geo_labels);
                    $new_client_keys = $client_info['authorized_client_key']; // stays the same
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                } else {
                    array_push($existing_geo_labels,$geo_labels);
                    array_push($access_tokens,$access_token);
                    array_push($refresh_tokens,$refresh_token);
                    array_push($client_keys,$authorized_client_key);
                    $new_geo_labels = implode("|",$existing_geo_labels);
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                    $new_client_keys = implode(",",$client_keys);
                }
                
                $wpdb->update( 
                    $table_name, 
                    array( 
                        'time' => current_time( 'mysql' ), 
                        'first_name' => $first_name, 
                        'last_name' => $last_name, 
                        'geo_labels' => $new_geo_labels, 
                        'verifications' => $verifications, 
                        'access_token' => $new_access_tokens, 
                        'refresh_token' => $new_refresh_tokens, 
                        'authorized_client_key' => $new_client_keys
                    ),
                    array( 'user_id' => $user_id ),
                    array( 
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    ),
                    array( '%d' )
                );
            } else {
                // If no user exists, create new one
                $wpdb->insert( 
                    $table_name, 
                    array( 
                        'time' => current_time( 'mysql' ), 
                        'user_id' => $user_id, 
                        'first_name' => $first_name, 
                        'last_name' => $last_name, 
                        'geo_labels' => $geo_labels, 
                        'verifications' => $verifications, 
                        'access_token' => $access_token, 
                        'refresh_token' => $refresh_token, 
                        'authorized_client_key' => $authorized_client_key
                    ) 
                );
            }
          }
            
        /**
         * After user stuff, send them back to whatever page the state variable tells us
         * 
         */
          $url =  "//{$_SERVER['HTTP_HOST']}";
          echo '<meta http-equiv="REFRESH" content="0; url=' . $url . $old_url . '">';
          exit();
            
        } else {
        /**
         * If an error occurs on the second authorization request
         * 
         */
          $response_json2 = json_decode($response2);
          echo 'Second request did not come back correctly.<br>';
          echo 'Error: ' . $response_json2->{'error'};
          echo '<br>Error Description: ' . $response_json2->{'error_description'};
        }
      } else {
          
        /**
         * If an error occurs on the first authorization request
         * 
         */
          $response_json = json_decode($response);
          echo 'First request did not come back correctly.<br>';
          echo 'Error: ' . $response_json->{'error'};
          echo '<br>Error Description: ' . $response_json->{'error_description'};
      }
} else {
    /**
     * If there is an error with the query strings
     * 
     */
      echo 'Initial query strings have an error.<br>';
      echo 'Error: ' . $_GET["error"];
      echo '<br>Error Description: ' . $_GET["error_description"];
}
?>