<?php

/*


*/

class MSGraph_Service 
{    
    
    /**
    *	Returns the application's option set.
    */
    public static function get_options ()
    {
        return get_option( MSGRAPH_SERVICE_OPTIONS_NAME );
    }
    
    
    /**
    *	Returns the application ID.
    */
    public static function get_app_id ()
    {
        $ops = self::get_options();
        return ( ! empty( $ops['client_id'] ) ? esc_attr( $ops['client_id'] ) : '' );
    }


    /**
    *	Returns the application secret.
    */
    public static function get_app_secret ()
    {
        $ops = self::get_options();
        return ( ! empty( $ops['client_secret'] ) ? esc_attr( $ops['client_secret'] ) : '' );
    }
    

    
   
    /**
    *	Checks if the given token has expired yet, and returns the 
    *   number of second left til expiry, or 0 if expired.
    */
    public static function is_valid_token ( $token_data )
    {
        $is_valid = 0;
        
        if ( ! empty( $token_data['expires_in'] ) ) {
            
            $length = intval( $token_data['expires_in'] );
            $length -= 30; // knock 30 sec off as a safety margin.
            $expires_at = $token_data['uts_saved'] + $length;
            
            $now = time();
            if ( $expires_at > $now ) {
                $is_valid = $expires_at - $now;
            }
        }
        
        return $is_valid;
    }
    
    
    /**
    *	Tries to return a valid token either from the db, or if it's present but expired, 
    *   by refreshing it.
    */
    public static function get_valid_user_token ( $user_id )
    {
        $user_token = self::get_user_token( $user_id );
        
        if ( ! empty( $user_token ) ) {
            $token_valid = self::is_valid_token( $user_token );
            if ( ! $token_valid ) {
                $user_token = self::exchange_refresh_token( $user_token, $user_id );
                $token_valid = self::is_valid_token( $user_token );
            }
            
            if ( ! $token_valid ) {
                $user_token = false;
                echo '<pre>Error: Unable to refresh token.</pre>';
            }
            
        } else {
            //self::authorize( 'user' );
            // TODO How deal with this redirect?
            echo '<pre>Please authorize first</pre>';
            $user_token = false;
        }
        
        return $user_token;
    }
    
    
    

/**
*   Token Management.
*   ---
*/     
    // ADMINCONSENT TOKEN
    //---
    public static function get_admin_token ()
    {
        return get_option( MSGRAPH_ADMIN_TOKEN_NAME );
    }
    
    //---
    public static function save_admin_token ( $token_data )
    {
        return update_option( MSGRAPH_ADMIN_TOKEN_NAME, $token_data );
    }
    
    //---
    public static function delete_admin_token ()
    {
        return delete_option( MSGRAPH_ADMIN_TOKEN_NAME );
    }
    
    
    // USER TOKENS
    //---
    public static function get_user_token ( $user_id )
    {
        return get_user_meta( $user_id, MSGRAPH_USER_TOKEN_NAME, true );
    }
    
    //---
    public static function save_user_token ( $user_id, $token_data )
    {
        return update_user_meta( $user_id, MSGRAPH_USER_TOKEN_NAME, $token_data );
    }
    
    //---
    public static function delete_user_token ( $user_id )
    {
        $user_id = intval( $user_id );
        $success = false;
        if ( $user_id ) {
            $success = delete_user_meta( $user_id, MSGRAPH_USER_TOKEN_NAME );
        }
        return $success;
    }
   
   

    
    
/**
*   Authentication.
*   ---
*/    
    /**
    *	Returns random string used for state check.
    */
    public static function create_random_string ( $length = 16 )
    {
        $randomstring = bin2hex( openssl_random_pseudo_bytes( $length ) );
        return $randomstring;
    }
    
    
    /**
    *	Redirects to the authorisation endpoint.
    */
    public static function authorize ( $type = 'user', $scopes = false )
    {
        $auth_url = self::make_auth_url( $type, $scopes );
        header( 'Location:' . $auth_url );
        die();
    }
    
     
    /**
    *	Builds the authorization url.
    */
    public static function make_auth_url ( $type = 'user', $scopes = false )
    {
        $auth_url = '';
        $ops = self::get_options();
        if ( empty( $ops['client_id'] ) ) {
            return $auth_url;
        }
        
        // Set up scopes, and the relevant endpoint.
        if ( 'user' === $type ) {
            $scopes = ! $scopes ? esc_attr( $ops['scopes'] ) : $scopes;
            $auth_url = $ops['auth_root_url'] . '/authorize';
        } elseif ( 'adminconsent' === $type ) {
            $scopes = ! $scopes ? 'https://graph.microsoft.com/.default' : $scopes;
            //$auth_url = 'https://login.microsoftonline.com/common/adminconsent';
            $auth_url = 'https://login.microsoftonline.com/' . $ops['tennant_id'] . '/adminconsent';
        }
        
        // Create and store a random 'state' in the user's meta that will be sent with the request and checked upon return.
        $state_check = self::create_random_string( 6 );
        update_user_meta( get_current_user_id(), 'msgraph_state_check', $state_check );
        
        // Build the url.
        $auth_query_parameters = array(
            'client_id'     => $ops['client_id'],
            'scope'         => $scopes,
            'state'         => $state_check,
            'redirect_uri'  => $ops['redirect_url'],
            'response_type' => 'code',
        );
        
        echo '<pre>auth_query_parameters ';
        print_r( $auth_query_parameters );
        echo '</pre>';
        
        $auth_query = http_build_query( $auth_query_parameters );
        $auth_url .= '?' . $auth_query;
        return $auth_url;
    }
    
    
    /**
    *	Tries to get a token from the token endpoint 
    *   using the received auth code.
    */
    public static function request_access_token ( $auth_vars, $grant_type = 'authorization_code', $scopes = false )
    {
        $token = array();
        $ops = self::get_options();
        
        // Check that the 'state' sent back matches the one saved to the user's meta.
        $state_check = get_user_meta( get_current_user_id(), 'msgraph_state_check', true );
        echo '<pre>saved state ';
        print_r( $state_check );
        echo '</pre>';
        if ( $auth_vars['state'] !== $state_check ) {
            echo '<pre>Failed the state check. auth_vars ';
            print_r( $auth_vars );
            echo '</pre>';
            // Bail out.
            return $token;
        }
        
        $token_exchange_params = array(
            'client_id'     => $ops['client_id'],
            'client_secret' => $ops['client_secret'],
            'code'          => $auth_vars['code'],
            'grant_type'    => $grant_type,
            'redirect_uri'  => $ops['redirect_url'],
        );
        
        if ( 'authorization_code' === $grant_type ) {
            $scopes = ! $scopes ? esc_attr( $ops['scopes'] ) : $scopes;
        } elseif ( 'client_credentials' === $grant_type ) {
            $scopes = ! $scopes ? 'https://graph.microsoft.com/.default' : $scopes;
            //$token_exchange_params['resource'] = 'https://graph.microsoft.com/';
        }
        $token_exchange_params['scope'] = $scopes;
        
        echo '<pre>token_exchange_params ';
        print_r( $token_exchange_params );
        echo '</pre>';
        
        $token = self::send_token_request( $token_exchange_params );
        if ( ! empty( $token ) ) {
            if ( 'authorization_code' === $grant_type ) {
                self::save_user_token( get_current_user_id(), $token );
            } elseif ( 'client_credentials' === $grant_type ) {
                self::save_admin_token( $token );
            }
        }
        
        return $token;
    }
    
    

    /**
    *	Tries to exchange an old token for a new one 
    *   at the endpoint using the refresh_token.
    */
    public static function exchange_refresh_token ( $token_data, $user_id = false )
    {
        $token = array();
        $ops = self::get_options();
                
        $token_exchange_params = array(
            'client_id'     => $ops['client_id'],
            'client_secret' => $ops['client_secret'],
            'refresh_token' => $token_data['refresh_token'],
            'grant_type'    => 'refresh_token',
            'redirect_uri'  => $ops['redirect_url'],
            'scope'         => $token_data['scope'] . ' offline_access',
        );
        
        echo '<pre>token_exchange_params (refresh) ';
        print_r( $token_exchange_params );
        echo '</pre>';
                
        $token = self::send_token_request( $token_exchange_params );
        if ( ! empty( $token ) ) {
            if ( $user_id ) {
                self::save_user_token( $user_id , $token );
            } else {
                self::save_admin_token( $token );
            }
        }
                
        return $token;
    }
    
    
    //---
    private static function send_token_request ( $token_exchange_params )
    {
        $token = false;
        $ops = self::get_options();
        $token_endpoint = $ops['auth_root_url'] . '/token';
        
        $request_headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept'       => 'application/json'
        );
        $request_arguments = array(
            'headers' => $request_headers,
            'body'    => $token_exchange_params,
            'timeout' => 60
        );
        
        $response = wp_remote_post( $token_endpoint, $request_arguments );
        
        if ( ! empty( $response['response'] ) && $response['response']['code'] == 200 ) {
            if ( ! empty( $response['body'] ) ) {
                $rbody = json_decode( $response['body'], true );
                if ( isset( $rbody['access_token'] ) ) {
                    $token = $rbody;
                    $token['uts_saved'] = time();
                }
            }
        } else {
            echo '<pre>response ';
            print_r( $response );
            echo '</pre>';
        }
        
        return $token;
    }
    

    
    
    
    
/**
*   WP integration.
*   ---
*/
    /**
    *	Hook up with WP.
    */
    public static function init ()
    {
        if ( is_admin() ) {
            add_action( 'admin_menu',               'MSGraph_Service::register_admin_menu_pages', 100 );
			add_filter( 'plugin_action_links',      'MSGraph_Service::add_plugin_list_links', 10, 2 );
		}
    }

    
    /**
    *	Adds the link on the plugins list to the settings page.
    */
    public static function add_plugin_list_links ( $links, $file )
	{
        if ( $file == 'msgraph-service/msgraph-service.php' ) {
			$settings_link = '<a href="admin.php?page=' . MSGRAPH_SERVICE_MENU_SLUG . '">' . __( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
    }
    
    
    /**
    *	Registers the admin-side menu pages and draw/scripts callbacks.
    */
    public static function register_admin_menu_pages ()
	{	
		$browser_title 		= 'MSGraph Service Settings';
		$menu_name 			= 'MSGraph Service';
		$capability 		= 'manage_options';
		$slug 				=  MSGRAPH_SERVICE_MENU_SLUG;
		$draw_function 		= 'MSGraph_Service::draw_settings_page';
		$scripts_function	= 'MSGraph_Service::add_scripts_settings_page';
		
		// Add the root page.
		$root_page = add_menu_page( $browser_title, $menu_name, $capability, $slug, $draw_function );				
		
        // Add the sub-menu duplicate.
        add_submenu_page( $slug, $browser_title, 'Settings', $capability, $slug, $draw_function );
		
        // Register the scripts callback.
        add_action( 'admin_head-'. $root_page, $scripts_function );
    }
    
    
    /**
    *	Draws the settings page.
    */    
    public static function draw_settings_page () 
    {
        include_once( MSGRAPH_SERVICE_PLUGIN_DIR . '/admin/settings-page.php' );
    }


    /**
    *   Adds scripts specifically for the settings page.
    */    
    public static function add_scripts_settings_page () 
    {
    
    }
    



    


    
   
/**
*   MS Graph calls.
*   ---
*/
    //---
    public static function get_user_profile ( $user_id )
    {
        $response_body = array();
        $token = self::get_valid_user_token( $user_id );
        
        if ( $token ) {
            $graph_request_headers = array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token['access_token'],
                'Accept'        => 'application/json',
            );
            $graph_query_params = array(
                'headers'       => $graph_request_headers,
                'httpversion'   => '1.1',
                'user-agent'    => 'Dev',
                'timeout'       => 60,
            );
            $response_body = self::fetch_graph_data( 'https://graph.microsoft.com/v1.0/me', $graph_query_params );
        }
        
        return $response_body;
    }
    
    
    //---
    private static function fetch_graph_data ( $graph_query_url, $graph_query_params )
    {
        $response_body = array();
        $response = wp_remote_get( $graph_query_url, $graph_query_params );
        
        if ( $response['response']['code'] == 200 && ! empty( $response['body'] ) ) {
            $response_body = json_decode( $response['body'], true );
        }
        
        return $response_body;
    }
    
    
    /*
    //---
    public static function get_user_profile_OLD ( $token )
    {
        $response_body = array();
        
        $graph_request_headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Accept'        => 'application/json',
        );
        $graph_query_params = array(
            'headers'       => $graph_request_headers,
            'httpversion'   => '1.1',
            'user-agent'    => 'Dev',
            'timeout'       => 60,
        );
        
        $graph_query_url = 'https://graph.microsoft.com/v1.0/me';
        $response = wp_remote_get( $graph_query_url, $graph_query_params );
        
        if ( $response['response']['code'] == 200 && ! empty( $response['body'] ) ) {
            $response_body = json_decode( $response['body'], true );
        }
        
        return $response_body;
    }
    */
}
?>