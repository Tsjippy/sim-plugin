<?php
namespace SIM\STATISTICS;
use SIM;

// Adds statisics to a page about the current page
add_filter( 'the_content', function ($content){
    if(!is_user_logged_in()) return $content;

    $view_roles     = SIM\get_module_option('statistics', 'view_rights');
    $user_roles     = wp_get_current_user()->roles;

    //only continue if we have the right so see the statistics
    if(!array_intersect($view_roles, $user_roles)) return $content;
    
    global $wpdb;

    $table_name	= $wpdb->prefix . 'sim_statistics';
    $url        = str_replace(get_site_url(),'', SIM\current_url());

    $pageviews  = $wpdb->get_results( "SELECT * FROM $table_name WHERE url='$url' ORDER BY $table_name.`timelastedited` DESC" );
    
    $total_views                = 0;
    $unique_views_last_months   = 0;
    $now                        = new \DateTime();
    foreach($pageviews as $view){
        $total_views += $view->counter; 

        $date = new \DateTime($view->timelastedited);
        $interval = $now->diff($date)->format('%m months');
        if($interval<6){
            $unique_views_last_months++;
        }
    }
    $unique_views   = count($pageviews);

    ob_start();
    ?>
    <br>
    <div class='pagestatistics'>
        <h4>Page statistics</h4>
        <table class='statistics_table'>
            <tbody>
                <tr>
                    <td>
                        <b>Total views:</b>   
                    </td>
                    <td class='value'>
                        <?php echo $total_views;?>  
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Unique views:</b>   
                    </td>
                    <td class='value'>
                        <?php echo $unique_views;?>  
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Unique views last 6 months:</b>   
                    </td>
                    <td class='value'>
                        <?php echo $unique_views_last_months;?>  
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php

    return $content.ob_get_clean();
},999);

new Statistics();