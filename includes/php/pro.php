<?php
namespace SIM;

//Add setting link to plugin page
add_filter("plugin_action_links_".PLUGIN, __NAMESPACE__.'\addExtraPluginLinks', 10, 3);
function addExtraPluginLinks($links, $plugin, $data) {
    // Settings Link
    $slug           = 'sim-base';
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


