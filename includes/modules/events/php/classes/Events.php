<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;


class Events{
	function __construct(){
		global $wpdb;
		$this->tableName		= $wpdb->prefix.'sim_events';
		$this->dayStartTime		= '00:00';
		$this->dayEndTime		= '23:59';
	}
	
	/**
	 * Creates the table holding all events if it does not exist
	*/
	function createEventsTable(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		}

		global $wpdb;
		
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName}(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id mediumint(9) NOT NULL,
			startdate varchar(80) NOT NULL,
			enddate varchar(80) NOT NULL,
			starttime varchar(80) NOT NULL,
			endtime varchar(80) NOT NULL,
			location varchar(80),
			organizer varchar(80),
			location_id mediumint(9),
			organizer_id mediumint(9),
			onlyfor mediumint(9),
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

	/**
	 * Gets an event from the db
	 * @param	int			$postId		WP_Post id
	 * 
	 * @return	object|false			The event or false if no event found
	*/
	public function retrieveSingleEvent($postId){
		global $wpdb;
		$query		= "SELECT * FROM {$wpdb->prefix}posts INNER JOIN `{$this->tableName}` ON {$wpdb->prefix}posts.ID={$this->tableName}.post_id WHERE post_id=$postId ORDER BY ABS( DATEDIFF( startdate, CURDATE() ) ) LIMIT 1";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}
		return $results[0];
	}
}