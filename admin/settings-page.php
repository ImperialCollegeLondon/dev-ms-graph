<?php

// API settings.
$api_settings = get_option( MSGRAPH_SERVICE_OPTIONS_NAME );

$client_id         = empty( $api_settings['client_id'] ) ? '' : esc_attr( $api_settings['client_id'] );
$client_secret     = empty( $api_settings['client_secret'] ) ? '' : esc_attr( $api_settings['client_secret'] );
$tennant_id        = empty( $api_settings['tennant_id'] ) ? '' : esc_attr( $api_settings['tennant_id'] );
$auth_root_url     = empty( $api_settings['auth_root_url'] ) ? '' : esc_attr( $api_settings['auth_root_url'] );
$ms_graph_root_url = empty( $api_settings['ms_graph_root_url'] ) ? '' : esc_attr( $api_settings['ms_graph_root_url'] );
$redirect_url      = empty( $api_settings['redirect_url'] ) ? '' : esc_attr( $api_settings['redirect_url'] );
$scopes            = empty( $api_settings['scopes'] ) ? '' : esc_attr( $api_settings['scopes'] );

// Update.
if ( isset( $_POST['ms_graph_save_settings'] ) ) {
    
    $client_id         = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
    $client_secret     = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';
    $tennant_id        = isset( $_POST['tennant_id'] ) ? sanitize_text_field( $_POST['tennant_id'] ) : '';
    $auth_root_url     = isset( $_POST['auth_root_url'] ) ? sanitize_text_field( $_POST['auth_root_url'] ) : '';
    $ms_graph_root_url = isset( $_POST['ms_graph_root_url'] ) ? sanitize_text_field( $_POST['ms_graph_root_url'] ) : '';
    $redirect_url      = isset( $_POST['redirect_url'] ) ? sanitize_text_field( $_POST['redirect_url'] ) : '';
    $scopes            = isset( $_POST['scopes'] ) ? sanitize_text_field( $_POST['scopes'] ) : '';
    
    $api_settings = array();
    $api_settings['client_id']         = $client_id;
    $api_settings['client_secret']     = $client_secret;
    $api_settings['tennant_id']        = $tennant_id;
    $api_settings['auth_root_url'] = $auth_root_url;
    $api_settings['ms_graph_root_url'] = $ms_graph_root_url;
    $api_settings['redirect_url']      = $redirect_url;
    $api_settings['scopes']            = $scopes;
    
    // Update option and clear any current admin token.
    update_option( MSGRAPH_SERVICE_OPTIONS_NAME, $api_settings );   
    //MSGraph_Service::delete_admin_token();
    
    $feedback = '<p>API settings saved.</p>';
}



// Draw UI.
?>
<div class="wrap">

    <h1>MS Graph API Settings</h1>
    <?php
    if ( isset( $feedback ) ) {
        echo '<div class="updated notice is-dismissible">' . $feedback . '</div>';
    }
    ?>
    <p></p>
    <br>
    
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <table>
            <tr>
                <td>Application ID:</td>
                <td><input type="text" name="client_id" value="<?php echo $client_id; ?>" class="wide-input" /></td>
            </tr>
            <tr>
                <td>Application Secret:</td>
                <td><input type="text" name="client_secret" value="<?php echo $client_secret; ?>" class="wide-input" /></td>
            </tr>
            
            
            <tr>
                <td>Tennant ID:</td>
                <td><input type="text" name="tennant_id" value="<?php echo $tennant_id; ?>" class="wide-input" /></td>
            </tr>
            
            <tr>
                <td>Authorization Root URL:</td>
                <td><input type="text" name="auth_root_url" value="<?php echo $auth_root_url; ?>" class="wide-input" /></td>
            </tr>
            <tr>
                <td>MS Graph Services Root URL:</td>
                <td><input type="text" name="ms_graph_root_url" value="<?php echo $ms_graph_root_url; ?>" class="wide-input" /></td>
            </tr>
            
            <tr>
                <td>Return (redirect) URL:</td>
                <td><input type="text" name="redirect_url" value="<?php echo $redirect_url; ?>" class="wide-input" /></td>
            </tr>
            
            <tr>
                <td>Scopes:<br>(separate multiple scopes with spaces)</td>
                <td><input type="text" name="scopes" value="<?php echo $scopes; ?>" class="wide-input" /></td>
            </tr>
            
        </table>
        
        
        <br><br>
        <input type="submit" value="Save Settings" name="ms_graph_save_settings" class="button-primary" />
    </form>
    
    <style>
        .wide-input {
            width:      480px;
        }
    </style>
    
    
<?php

?>
  
    
</div>


