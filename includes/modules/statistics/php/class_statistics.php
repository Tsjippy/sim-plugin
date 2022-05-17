<?php
namespace SIM\STATISTICS;
use SIM;

class Statistics {
    function __construct(){
        global $wpdb;
        $this->table_name				= $wpdb->prefix . 'sim_statistics';
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
        $userId        = get_current_user_id();
        $url            = str_replace(SITEURL,'',$_POST['url']);
        $creation_date	= date("Y-m-d H:i:s");

        $pageviews  = $wpdb->get_var( "SELECT counter FROM {$this->table_name} WHERE userid='$userId' AND url='$url'" );
        
        if(is_numeric($pageviews)){
            $wpdb->update(
                $this->table_name, 
                array(
                    'timelastedited'=> $creation_date,
                    'counter'	 	=> $pageviews+1
                ), 
                array(
                    'userid'		=> $userId,
                    'url'           => $url,
                ),
            );
        }else{
            $wpdb->insert(
				$this->table_name, 
				array(
                    'timecreated'   => $creation_date,
                    'timelastedited'=> $creation_date,
					'userid'		=> $userId,
                    'url'           => $url,
					'counter'	    => 1
				)
			);
        }
    }
}
