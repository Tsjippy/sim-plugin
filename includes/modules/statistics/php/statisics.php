<?php
namespace SIM\STATISTICS;
use SIM;

// Adds statisics to a page about the current page
add_filter( 'the_content', function ($content){
    if(!is_main_query() || !is_user_logged_in()){
        return $content;
    }

    $viewRoles     = SIM\getModuleOption(MODULE_SLUG, 'view_rights');
    $userRoles     = wp_get_current_user()->roles;

    //only continue if we have the right so see the statistics
    if(!array_intersect($viewRoles, $userRoles)){
        return $content;
    }
    
    global $wpdb;

    $tableName	= $wpdb->prefix . 'sim_statistics';
    $url        = str_replace(SITEURL,'', SIM\currentUrl());

    $pageViews  = $wpdb->get_results( "SELECT * FROM $tableName WHERE url='$url' ORDER BY $tableName.`timelastedited` DESC" );
    
    $totalViews             = 0;
    $uniqueViewsLastMonths  = 0;
    $now                    = new \DateTime();
    foreach($pageViews as $view){
        $totalViews += $view->counter; 

        $date = new \DateTime($view->timelastedited);
        $interval = $now->diff($date)->format('%m months');
        if($interval<6){
            $uniqueViewsLastMonths++;
        }
    }
    $uniqueViews   = count($pageViews);

    ob_start();
    ?>
    <div class='pagestatistics'>
        <h4>Page statistics</h4>
        <table class='statistics_table'>
            <tbody>
                <tr>
                    <td>
                        <strong>Total views:</strong>   
                    </td>
                    <td class='value'>
                        <?php echo $totalViews;?>  
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Unique views:</strong>   
                    </td>
                    <td class='value'>
                        <?php echo $uniqueViews;?>  
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Unique views last 6 months:</strong>   
                    </td>
                    <td class='value'>
                        <?php echo $uniqueViewsLastMonths;?>  
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php

    return $content.ob_get_clean();
},999);