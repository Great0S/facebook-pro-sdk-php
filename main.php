<?php
/**
 * Plugin Name: Simple Facebook Login
 * Description: This plugin adds facebook login button anywhere in your wordpress website using a shortcode
 * Plugin URI: https://github.com/Great0s
 * Author: GreatOs
 * Version: 0.0.1
 * Credits: Victor Rusu
 **/

 //* Don't directly access this file
 defined( 'ABSPATH' ) or die();

 // Call Facebook SDK library
 require_once 'Facebook/autoload.php'

 session_start();
 $FB = new \Facebook\Facebook([
     'app_id' => '',
     'app_secret' => '',
     'default_graph_version' => 'v2.10'
 ])
 $handler - $FB -> getRedirectLoginHelper();

 add_shortcode( 'facebook-sign-in', 'sign_in_with_facebook' );
 function sign_in_with_facebook() {
     if((!is_user_logged_in(  )) {
         if(!get_option( 'users_can_register' )) {
             return ('Registerations are closed for now!');
         } else {
             global $handler;
             $nonce = wp_create_nonce( "facebook_sgin_in_nonce" );
             $link = admin_url( 'admin-ajax.php?action=facebook_sgin_in_nonce&nonce='.$nonce);
             $redirect_to = $link;
             $data = ["email"]
             $fullUrl = $handler -> getLoginURL($redirect_to, $data);
             return '
                    <button onclick="window.location.href='. $fullUrl .';">Facebook Login</button>
             '
         }
     } else {
         $current_user = wp_get_current_user(  );
         return 'Hello ' . $current_user -> first_name . '! - <a href="/wp-login.php?action=logout">Log Out</a>';
     }
  }

  add_action( "wp_ajax_facebook_sign_in", "facebook_sign_in" );

  function facebook_sign_in(){
      global $handler, $FB;

      if(!wp_verify_nonce( $_REQUEST['nonce'], "facebook_sign_in_nonce" )){
          exit("This is not Allowed!");
      }
      try {
          $access_token = $handler -> getAccessToken();
      } catch(\Facebook\Exceptions\FacebookResponseException $e){
          echo"Response Exception: " . $e -> getMessage();
          exit();
      }

      if(!$access_token){
          wp_redirect( home_url() );
          exit;
      }

      $oAuth2Client = $FB -> getOAuth2Client();
      if(!$access_token -> isLongLived())
      $access_token = $oAuth2Client -> getLongLivedAccessToken($access_token);

    $response = $FB -> get("me?fields=id, first_name, last_name, gender, email, birthday, location, picture.type(large), ", $access_token);
      $userData = $response -> getGraphNode() -> asArray();
      $userEmail = $userData['email'];

      // Check if the user's email is registerd
      if(!email_exists($userEmail)) {
          // Generate a password
          $byte = openssl_random_pseudo_bytes(2);
          $pass = md5(bin2hex($byte));
          $userSignIn = strtolower($userData['first_name'].$userData['last_name']);

          $newUserID = wp_insert_user(array(
              'user_'
          ))
      }
  }