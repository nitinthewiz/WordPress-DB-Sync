<?php 
	/*
	Plugin Name: WP Dropbox Sync
	Plugin URI: http://blog.nitinkhanna.com
	Description: Sync WP Posts with Dropbox as Markdown
	Author: N. Khanna
	Version: 0.1
	Author URI: http://blog.nitinkhanna.com
	*/
	include 'Convert2.php';
	# Include the Dropbox SDK libraries
	require_once "/dropbox-sdk/lib/Dropbox/autoload.php";
	use \Dropbox as dbx;
	//$appInfo = dbx\AppInfo::loadFromJsonFile("INSERT_PATH_TO_JSON_CONFIG_PATH");


	register_activation_hook( __FILE__, 'sync_activate' );

	function sync_activate(){
		Convert2();
	}

	add_action('admin_menu', 'my_plugin_menu');

	function my_plugin_menu() {
		add_options_page('WP DB Sync','WordPress DB Sync','manage_options','wp-db-sync.php','db_show');
	}

	function db_show(){
		echo "test";
	}

	//add_options_page('WP DB Sync','WordPress DropBox Sync Settings','manage_options','wp-db-sync.php','db_show');


?>
	