<?php
namespace SIM;

class Statistics {
    function __construct(){
        global $wpdb;
        $this->table_name				= $wpdb->prefix . 'simnigeria_statistics';
		$this->create_db_table();

        //Make add_page_view function availbale for AJAX request
        add_action ( 'wp_ajax_nopriv_add_page_view', array($this,'add_page_view'));
        add_action ( 'wp_ajax_add_page_view', array($this,'add_page_view'));
    }

    function create_db_table(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 
		
		//only create db if it does not exist
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timecreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            timelastedited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            userid mediumint(9) NOT NULL,
            url longtext NOT NULL,
            counter int NOT NULL,
            PRIMARY KEY  (id)
		) $charset_collate;";

		maybe_create_table($this->table_name, $sql );
	}

    function add_page_view(){
        global $wpdb;

        if(empty($_POST['url'])) wp_die();
        $user_id        = get_current_user_id();
        $url            = str_replace(get_site_url(),'',$_POST['url']);
        $creation_date	= date("Y-m-d H:i:s");

        $pageviews  = $wpdb->get_var( "SELECT counter FROM {$this->table_name} WHERE userid='$user_id' AND url='$url'" );
        
        if(is_numeric($pageviews)){
            $wpdb->update(
                $this->table_name, 
                array(
                    'timelastedited'=> $creation_date,
                    'counter'	 	=> $pageviews+1
                ), 
                array(
                    'userid'		=> $user_id,
                    'url'           => $url,
                ),
            );
        }else{
            $wpdb->insert(
				$this->table_name, 
				array(
                    'timecreated'   => $creation_date,
                    'timelastedited'=> $creation_date,
					'userid'		=> $user_id,
                    'url'           => $url,
					'counter'	    => 1
				)
			);
        }
    }
}

add_action('init', function(){
	new Statistics();
});

add_filter( 'the_content', function ($content){
    if(!is_user_logged_in()) return $content;
    
    global $wpdb;

    $table_name				= $wpdb->prefix . 'simnigeria_statistics';
    $url        = str_replace(get_site_url(),'',current_url());

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