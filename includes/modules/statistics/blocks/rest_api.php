<?php
namespace SIM\STATISTICS;
use SIM;

add_action( 'rest_api_init', function () {
	// show schedules
	register_rest_route(
		RESTAPIPREFIX.'/statistics',
		'/page_statistics',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\statisticsWidget',
			'permission_callback' 	=> '__return_true',
		)
	);
} );

function statisticsWidget(){
	$viewRoles     = SIM\getModuleOption(MODULE_SLUG, 'view_rights');
    $userRoles     = wp_get_current_user()->roles;

    //only continue if we have the right so see the statistics
    if(!array_intersect($viewRoles, $userRoles)){
        return '';
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

    return ob_get_clean();
}