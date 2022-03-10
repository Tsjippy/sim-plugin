<?php
namespace SIM\STATISTICS;
use SIM;

class Statistics {
    function __construct(){
        global $wpdb;
        $this->table_name				= $wpdb->prefix . 'sim_statistics';
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
        $url            = str_replace(SITEURL,'',$_POST['url']);
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
