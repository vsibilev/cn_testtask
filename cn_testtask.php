<?php
/*
Plugin Name: Coding Ninjas Test Task Sibiliev
Description: Extension for Plugin for freelancers tasks.
Author: CodingNinjas inc.
Author URI: http://codingninjas.co/
Plugin URI: http://codingninjas.co/
Version: 1.0
Text Domain: cn
*/

//check if parent plugin is active before activation
function cn_testtask_activation() {
	if ( !class_exists('\codingninjas\App') ) {

		// Deactivate the plugin
		deactivate_plugins(__FILE__);

		// Throw an error in the wordpress admin console
		$error_message = __('This plugin requires a Coding Ninjas parent plugin to be active!', 'cn');
		wp_die($error_message);

	}
}

register_activation_hook( __FILE__, 'cn_testtask_activation' );

require_once "app/App.php";
\cnTestTask\App::run(__FILE__);
