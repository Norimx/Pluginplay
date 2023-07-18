<?php
/**
 * Plugin Name: Pluginplay
 * Description: Install a plugin from a ZIP file by providing the URL.
 * Author: Newemage
 * Plugin URI: http://newemage.com/
 * Version: 1.0
 */

// Plugin options page callback
function install_plugin_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['zip_url'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'install_plugin_from_zip' ) ) {
        $zip_url = sanitize_text_field( $_POST['zip_url'] );
        if ( filter_var( $zip_url, FILTER_VALIDATE_URL ) ) {
            install_plugin_from_zip( $zip_url );
            $pli = basename( $zip_url, '.zip' );
            echo "<div class=notice notice-success><p>Plugin {$pli} installed successfully! goto <a href=./plugins.php>Installed Plugins</a></p></div>";
        } else {
            
            echo '<div class="notice notice-error"><p>Invalid ZIP file URL. Please provide a valid URL.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'install_plugin_from_zip' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="zip_url">Plugin ZIP File URL</label></th>
                    <td>
                        <input type="text" id="zip_url" name="zip_url" class="regular-text">
                        example: https://github.com/NoriMx/inserto/archive/master.zip
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Install Plugin' ); ?>
        </form>
    </div>
    <?php
}

// Plugin options menu
function install_plugin_options_menu() {
    add_options_page(
        'Pluginplay',
        'Pluginplay',
        'manage_options',
        'pluginplay',
        'install_plugin_options_page'
    );
}
add_action( 'admin_menu', 'install_plugin_options_menu' );

// Plugin logic
function install_plugin_from_zip( $zip_url ) {
    if(!strstr($zip_url,'//') || !strstr(strtolower($zip_url),'.zip'))
       exit ('not zip url');
    $plugin_dir = WP_PLUGIN_DIR;

    // Create a unique folder name for the plugin installation
    $plugin_folder = basename( $zip_url, '.zip' );

   
    //$temp_folder = strstr($zip_url,'github') ? $plugin_dir : $plugin_dir . '/' . $plugin_folder;
$temp_folder =  $plugin_dir . '/' . $plugin_folder;
    // Download the ZIP file
    $zip_content = file_get_contents( $zip_url );

    // Save the ZIP file to the temporary folder
    file_put_contents( $temp_folder . '.zip', $zip_content );

    // Unzip the ZIP file
    $zip = new ZipArchive;
    if ( $zip->open( $temp_folder . '.zip' ) === true ) {
        $zip->extractTo( $temp_folder );
        $zip->close();
    }

    // Delete the ZIP file
    unlink( $temp_folder . '.zip' );

    // Get the plugin main file
   $temp_folder = hasfiles($temp_folder);// valid folder
   $finaldir = explode('/',$temp_folder);
   $plugin_folder = end($finaldir);
    
     $plugin_files = glob( $temp_folder . '/*.php' );
    
    if ( ! empty( $plugin_files ) ) {
        $plugin_file = $plugin_files[0];
    } else {
      exit ('bad zip struct'); 
    }
    
    // Create the plugin folder in the plugins directory
    if ( ! file_exists( $plugin_dir . '/' . $plugin_folder ) ) {
        mkdir( $plugin_dir . '/' . $plugin_folder );
    }

    // Move the plugin files from the temporary folder to the plugin folder
    foreach ( glob( $temp_folder . '/*' ) as $file ) {
        rename( $file, $plugin_dir . '/' . $plugin_folder . '/' . basename( $file ) );
    }

    // Activate the plugin
    activate_plugin( $plugin_dir . '/' . $plugin_folder . '/' . basename( $plugin_file ) );

    // Remove the temporary folder
    if ( file_exists( $temp_folder ) ) {
        foreach ( glob( $temp_folder . '/*' ) as $file ) {
            if ( is_dir( $file ) ) {
                rmdir( $file );
            } else {
                unlink( $file );
            }
        }
        rmdir( $temp_folder );
    }
     
}


function hasfiles($directory) {
    $phpFiles = glob($directory . '/*.php');

    if (count($phpFiles) > 0) {
        return $directory;
    } else {
        $directories = glob($directory . '/*', GLOB_ONLYDIR);

        foreach ($directories as $subDirectory) {
            $phpFiles = hasfiles($subDirectory);

            if ($phpFiles !== false) {
                return $phpFiles;
            }
        }
    }

    return false;
}
