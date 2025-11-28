<?php
namespace SIM;

// run on activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin != PLUGIN ) {
        return;
    }

    // Create private upload folder
    $path   = wp_upload_dir()['basedir'].'/private';
    if (!is_dir($path)) {
        wp_mkdir_p($path);
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
    exit( esc_url(wp_redirect( admin_url( esc_url('admin.php?page=sim') ) ) ) );
} );