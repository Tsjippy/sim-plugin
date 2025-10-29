<?php
namespace SIM;

// run on activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin != PLUGIN ) {
        return;
    }

    error_log('Running activation');

    // Create private upload folder
    $path   = wp_upload_dir()['basedir'].'/private';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    // Copy dl-file.php
    copy(__DIR__.'/other/dl-file.php', ABSPATH.'/dl-file.php');

    //.htaccess
    $htaccess = file_get_contents(ABSPATH.'/.htaccess');
    if(!str_contains($htaccess, '# BEGIN THIS DL-FILE.PHP ADDITION')){
        $htaccess .= "\n\n# BEGIN THIS DL-FILE.PHP ADDITION";
        $htaccess .= "\nRewriteCond %{REQUEST_URI} ^.*wp-content/uploads/private/.*";
        $htaccess .= "\nRewriteRule ^wp-content/uploads/(private/.*)$ dl-file.php?file=$1 [QSA,L] */";
        $htaccess .= "\n# END THIS DL-FILE.PHP ADDITION";
    }
    file_put_contents(ABSPATH.'/.htaccess', $htaccess);

    $family = new FAMILY\Family();
    $family->createDbTables();

    //redirect after plugin activation
    exit( wp_redirect( admin_url( esc_url('admin.php?page=sim') ) ) );
} );

//Add setting link
add_filter("plugin_action_links_".PLUGIN, __NAMESPACE__.'\addExtraPluginLinks', 10, 3);
function addExtraPluginLinks($links, $plugin, $data) {
    // Settings Link
    $slug           = 'sim-plugin';
    $url            = admin_url( "admin.php?page=sim" );
    $link           = "<a href='$url'>Settings</a>";
    array_unshift($links, $link);

    // Details link
    $url            = admin_url( "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog" );
    $link           = "<a href='$url'>Details</a>";
    array_unshift($links, $link);

    // Update links
    if(isset($_GET['update']) && $_GET['update'] == 'check'){
        // Reset updates cache
        delete_site_transient( 'update_plugins' );
        delete_transient('sim-git-release');

        wp_update_plugins();

        $updates    = get_site_transient( 'update_plugins' );
        if(is_wp_error($updates)){
            $link = "<div class='error'>".$updates->get_error_message()."</div>";
        }elseif(isset($updates->response[$plugin])){
            $url    = self_admin_url( 'update.php?action=update-selected&amp;plugin=' . urlencode( $plugin ) );
            $url    = wp_nonce_url( $url, 'bulk-update-plugins' );
            $link   = "<a href='$url' class='update-link'>Update to ".$updates->response[$plugin]->new_version."</a>";
        }else{
            $url   = admin_url( 'plugins.php?update=check' );
            $link  = "Up to date <a href='$url'>Check again</a>";
        }
    }else{
        $url   = admin_url( 'plugins.php?update=check' );
        $link  = "<a href='$url'>Check for update</a>";
    }
    array_unshift($links, $link);

    return $links;
}