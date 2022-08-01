<?php
namespace SIM;

// run on activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin != PLUGIN ) {
        return;
    }

    // create must use plugins folder if it does not exist
    if (!is_dir(WP_CONTENT_DIR.'/mu-plugins')) {
        mkdir(WP_CONTENT_DIR.'/mu-plugins', 0777, true);
    }

    // Copy must plugin
    copy(__DIR__.'/other/sim.php', WP_CONTENT_DIR.'/mu-plugins/sim.php');

    // Create private upload folder
    $path   = wp_upload_dir()['basedir'].'/private';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    // Copy dl-file.php
    copy(__DIR__.'/other/dl-file.php', ABSPATH.'/dl-file.php');

    //.htaccess
    $htaccess = file_get_contents(ABSPATH.'/.htaccess');
    if(strpos($htaccess, '# BEGIN THIS DL-FILE.PHP ADDITION') === false){
        $htaccess .= "\n\n# BEGIN THIS DL-FILE.PHP ADDITION";
        $htaccess .= "\nRewriteCond %{REQUEST_URI} ^.*wp-content/uploads/private/.*";
        $htaccess .= "\nRewriteRule ^wp-content/uploads/(private/.*)$ dl-file.php?file=$1 [QSA,L] */";
        $htaccess .= "\n# END THIS DL-FILE.PHP ADDITION";
    }
    file_put_contents(ABSPATH.'/.htaccess', $htaccess);

    //redirect after plugin activation
    exit( wp_redirect( admin_url( 'admin.php?page=sim' ) ) );
} );

//Add setting link
add_filter("plugin_action_links_".PLUGIN, function ($links) { 
    $url            = admin_url( 'admin.php?page=sim' );
    $settings_link  = "<a href='$url'>Settings</a>"; 
    array_unshift($links, $settings_link); 
    return $links; 
});