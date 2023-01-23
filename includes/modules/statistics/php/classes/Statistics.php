<?php
namespace SIM\STATISTICS;
use SIM;

class Statistics {
    public $tableName;

    public function __construct(){
        global $wpdb;
        $this->tableName				= $wpdb->prefix . 'sim_statistics';
    }

    /**
     * Create the statistics table if it does not exist
     */
    public function createDbTable(){
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/install-helper.php';
		}
		
		//only create db if it does not exist
		global $wpdb;
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timecreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            timelastedited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            userid mediumint(9) NOT NULL,
            url longtext NOT NULL,
            counter int NOT NULL,
            PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

    /**
     * Add a viewed paged entry to db
     */
    public function addPageView(){
        global $wpdb;
        $userId         = get_current_user_id();
        $url            = str_replace(SITEURL,'',$_POST['url']);
        $creationDate	= date("Y-m-d H:i:s");

        $pageViews  = $wpdb->get_var( "SELECT counter FROM {$this->tableName} WHERE userid='$userId' AND url='$url'" );
        
        if(is_numeric($pageViews)){
            $wpdb->update(
                $this->tableName,
                array(
                    'timelastedited'=> $creationDate,
                    'counter'	 	=> $pageViews + 1
                ),
                array(
                    'userid'		=> $userId,
                    'url'           => $url,
                ),
            );
        }else{
            $wpdb->insert(
				$this->tableName,
				array(
                    'timecreated'   => $creationDate,
                    'timelastedited'=> $creationDate,
					'userid'		=> $userId,
                    'url'           => $url,
					'counter'	    => 1
				)
			);
        }
    }
}
