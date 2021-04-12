<?php
/* 
Plugin Name: 	MS Graph Service ICL
Description: 	Authenticate with Microsoft and interact over the MS Graph API. 
Version: 		0.2
*/

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) { 
	die();
}



// Name of the admin consent token data in the WP options table.
define( 'MSGRAPH_ADMIN_TOKEN_NAME', 'msgraph_service_admin_token' );

// Name of the user token data in the WP user meta table.
define( 'MSGRAPH_USER_TOKEN_NAME', 'msgraph_user_token' );



// Server path to the plugin's directory.
define(	'MSGRAPH_SERVICE_PLUGIN_DIR', dirname( __FILE__ ) );

// Admin page menu/url slug.
define( 'MSGRAPH_SERVICE_MENU_SLUG', 'msgraph-service' );

// Options name.
define( 'MSGRAPH_SERVICE_OPTIONS_NAME', 'ms_graph_service_settings' );



include_once( MSGRAPH_SERVICE_PLUGIN_DIR . '/includes/class-msgraph.php' );


MSGraph_Service::init();

?>