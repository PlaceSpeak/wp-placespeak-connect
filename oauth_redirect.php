<?php
/**
This page contains the logic to handle a redirect from PlaceSpeak OAUTH process.
*/

// First, we get the path
$state = htmlspecialchars($_GET["state"]);

// The index number of this app is stuck onto the end of the state variable in a sneaky manner
$index_position = strpos($state, '_');
$app_index = substr(substr($state,$index_position),1);
$old_url = substr($state,0,$index_position);

// Get info from DB
require_once('../../../wp-config.php');
global $wpdb;
$table_name = $wpdb->prefix . 'placespeak';
$client_info = $wpdb->get_results("SELECT * FROM " . $table_name);
$client_id = $client_info[$app_index]->client_key;
$client_secret = $client_info[$app_index]->client_secret;
$redirect_uri = $client_info[$app_index]->redirect_uri;

// Get code from query string
$code = $_GET["code"];
$error = $_GET["error"];
if(!$error) {
    // Send authorization request
    $url = 'http://dev.placespeak.com/connect/token';
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
    
      // Check if it returned without an error
      if($httpcode==200) { 
        $response_json = json_decode($response);
    
        // Then, we send a GET, and get user information to put into the DB
        $header = array();
        $header[] = 'AUTHORIZATION: Bearer ' . $response_json->{'access_token'};
        $ch2 = curl_init();
        curl_setopt( $ch2, CURLOPT_URL, 'http://dev.placespeak.com/connect/api/user_info/');
        curl_setopt( $ch2, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, 1);
        $response2 = curl_exec( $ch2 );
        $httpcode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        // If this request succeeded then assign variables and put into DB
        if($httpcode2==200) { 
          $response_json2 = json_decode($response2);
            
          // Just assigning strings to these vars, removing any other formatting
          $user_id               = $response_json2->{'id'};
          $first_name            = $response_json2->{'first_name'};
          $last_name             = $response_json2->{'last_name'};
          $geo_labels            = implode(",",$response_json2->{'geo_labels'});
          $verifications         = $response_json2->{'verifications'};
          $access_token          = $response_json->{'access_token'};
          $refresh_token         = $response_json->{'refresh_token'};
          $authorized_client_key = $client_info[$app_index]->client_key;
        
          $user_storage = get_option('placespeak_user_storage');
        
          // Create user in the WP_USERS table
          if($user_storage == 'WP_USERS') {
              // Their username is their user_id with 'placespeak' attached (wanted more unique info, but can't use more)
              // Then all that other data is stored as metadata along with them
              $user_name = $user_id . '_placespeak';
              $wordpress_user_id = username_exists( $user_name );
              if ( $wordpress_user_id == true ) {
                // user exists - update fields
                // If they do, then add access token, refresh token, and client key to end of the db value
                // and update their settings in case there's been a change
                $access_tokens = explode(",", get_user_meta($wordpress_user_id,'placespeak_access_token', true));
                $refresh_tokens = explode(",", get_user_meta($wordpress_user_id,'placespeak_refresh_token', true));
                $client_keys = explode(",", get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true));
                $this_key_exists = false;
                // This could be a switch statement instead
                foreach($client_keys as $key=>$single_client_key) {
                    // Check if this client key exists and what index it's at
                    if($single_client_key == $authorized_client_key) {
                        $this_key_exists = true;
                        // Replace its values with the new ones in all the arrays
                        $access_tokens[$key] = $access_token;
                        $refresh_tokens[$key] = $refresh_token;
                    }
                }
                if($this_key_exists) {
                    $new_client_keys = get_user_meta($wordpress_user_id,'placespeak_authorized_client_key', true); // stays the same
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                } else {
                    // Need to add this onto the end of the array, then implode it
                    // I could check if the array is only one long and not add the comma, but it works the same anyway
                    array_push($access_tokens,$access_token);
                    array_push($refresh_tokens,$refresh_token);
                    array_push($client_keys,$authorized_client_key);
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                    $new_client_keys = implode(",",$client_keys);
                }
                  
                update_user_meta( $wordpress_user_id, 'placespeak_user_id', $user_id);
                update_user_meta( $wordpress_user_id, 'placespeak_first_name', $first_name);
                update_user_meta( $wordpress_user_id, 'placespeak_last_name', $last_name);
                update_user_meta( $wordpress_user_id, 'placespeak_geo_labels', $geo_labels);
                update_user_meta( $wordpress_user_id, 'placespeak_verifications', $verifications);
                update_user_meta( $wordpress_user_id, 'placespeak_access_token', $new_access_tokens);
                update_user_meta( $wordpress_user_id, 'placespeak_refresh_token', $new_refresh_tokens);
                update_user_meta( $wordpress_user_id, 'placespeak_authorized_client_key', $new_client_keys);
              } else {
                // Create user with meta fields
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
              }
          }
            
          // OR, put into a special PlaceSpeak table (depending on selected option)
          if($user_storage == 'PS_USERS') {
            global $wpdb;

            $table_name = $wpdb->prefix . 'placespeak_users';
              
            // First check if user exists
            $client_info = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE user_id = " . $user_id);
            if($client_info) {
                // If they do, then add access token, refresh token, and client key to end of the db value
                // and update their settings in case there's been a change
                $access_tokens = explode(",", $client_info['access_token']);
                $refresh_tokens = explode(",", $client_info['refresh_token']);
                $client_keys = explode(",", $client_info['authorized_client_key']);
                $this_key_exists = false;
                // This could be a switch statement instead
                foreach($client_keys as $key=>$single_client_key) {
                    // Check if this client key exists and what index it's at
                    if($single_client_key == $authorized_client_key) {
                        $this_key_exists = true;
                        // Replace its values with the new ones in all the arrays
                        $access_tokens[$key] = $access_token;
                        $refresh_tokens[$key] = $refresh_token;
                    }
                }
                if($this_key_exists) {
                    $new_client_keys = $client_info['authorized_client_key']; // stays the same
                    $new_access_tokens = implode(",",$access_tokens);
                    $new_refresh_tokens = implode(",",$refresh_tokens);
                } else {
                    // Need to add this onto the end of the array, then implode it
                    // I could check if the array is only one long and not add the comma, but it works the same anyway
                    array_push($access_tokens,$access_token);
                    array_push($refresh_tokens,$refresh_token);
                    array_push($client_keys,$authorized_client_key);
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
                        'geo_labels' => $geo_labels, 
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
                // Add them with all current information
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
            
          // Then, we send the user to the page they were on
          $url =  "//{$_SERVER['HTTP_HOST']}";
          header('Refresh: 1; '. $url . $old_url);
          exit();
            
        } else {
          $response_json2 = json_decode($response2);
          echo 'Second request did not come back correctly.<br>';
          echo 'Error: ' . $response_json2->{'error'};
          echo '<br>Error Description: ' . $response_json2->{'error_description'};
        }
      } else {
          $response_json = json_decode($response);
          echo 'First request did not come back correctly.<br>';
          echo 'Error: ' . $response_json->{'error'};
          echo '<br>Error Description: ' . $response_json->{'error_description'};
      }
} else {
      echo 'Initial query strings have an error.<br>';
      echo 'Error: ' . $_GET["error"];
      echo '<br>Error Description: ' . $_GET["error_description"];
}
?>