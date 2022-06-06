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
             return ('Registerations are closed for now!')
         }
     }
  }