<?php
namespace SIM\FAMILY;
use SIM;

class Family{
    public $tableName;
    public $metaTableName;
    public $siblings;
    public $children;
    public $partner;
    public $parents;
    public $userId;

    /**
     * Initiates the class
     */
    public function __construct() {
        global $wpdb;

        $this->tableName        = $wpdb->prefix . 'sim_family';
        $this->metaTableName    = $wpdb->prefix . 'sim_family_meta';
    }

    /**
	 * Creates the tables for this module
	 */
	public function createDbTables(){
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		
		//only create db if it does not exist
		global $wpdb;
		$charsetCollate = $wpdb->get_charset_collate();

		//Main table
		$sql = "CREATE TABLE {$this->tableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
            family_id mediumint(9) NOT NULL,
			user_id_1 mediumint(9) NOT NULL,
			user_id_2 mediumint(9) NOT NULL,
            relationship tinytext NOT NULL,
            start_date date,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );

        // Family Meta table
		$sql = "CREATE TABLE {$this->metaTableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
            family_id mediumint(9) NOT NULL,
			`key` text NOT NULL,
			`value` text NOT NULL,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->metaTableName, $sql );
    }

    /**
     * Gets all family members of a user
     * 
     * @param   int|object  $userId     The wp user or user id
     * @param   bool        $flat       Wheter to return a flast arary of user ids or indexed by relation type. Default false for indexed
     * 
     * @return array                    The requested array
     */
    public function getFamily($userId, $flat=false){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select * from $this->tableName where user_id_1='$userId' or user_id_2='$userId'";
        $results    = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        $family = [];

        if($flat){
            foreach($results as $result){
                if($result->user_id_1 == $userId){
                    $family[]   = $result->user_id_2;
                }else{
                    $family[]   = $result->user_id_1;
                }
            }

            return $family;
        }

        foreach($results as $result){
            // We add the relation ship as is
            if($result->user_id_1 == $userId){
                if(!is_array($family[$result->relationship])){
                    $family[$result->relationship]  = [];
                }

                $family[$result->relationship][]   = $result->user_id_2;
            }
            
            // We add the opposite as the user id is the second one
            else{
                $type   = $result->relationship;

                if($result->relationship == 'child'){
                    $type   = 'parent';
                }
                $family[$type]   = $result->user_id_1;
            }
        }

        return $family;
    }

    /**
     * Gets all the children of an user
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  array                   An array of children user ids
     */
    public function getChildren($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select user_id_2 from $this->tableName where user_id_1='$userId' AND relationship='child'";
        $results    = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        return $results;
    }

    /**
     * Gets all the siblings of an user
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  array                   An array of sibling user ids
     */
    public function getSiblings($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        // Query all relations marked as siblings
        $query      = "select * from $this->tableName where user_id_1='$userId' OR user_id_2='$userId' AND relationship='sibling'";
        $siblings   = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        // Get all the users with the same parent
        $subQuery   = "select user_id_1 as parent from $this->tableName where user_id_2='$userId' AND relationship='child' LIMIT 1";
        $query      = "select user_id_2 from $this->tableName where user_id_1=($subQuery) AND relationship='child'";
        $siblings   = array_merge($wpdb->get_results($query), $siblings);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        $siblingIds = [];
        foreach($siblings as $sibling){
            if($sibling->user_id_1 == $userId){
                $siblingIds[]   = $sibling->user_id_2;
            }else{
                $siblingIds[]   = $sibling->user_id_1;
            }
        }

        return $siblings;
    }

    /**
     * Gets all the parents of an user
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  array                   An array of parent user ids
     */
    public function getParents($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select user_id_1 from $this->tableName where user_id_2='$userId' AND relationship='child'";
        $results    = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        $parents    = [];

        if($results[0]->user_id_1 == $userId){
            $parents[]  = $results[0]->user_id_2;
        }else{
            $parents[]  = $results[0]->user_id_1;
        }

        return $parents;
    }

    /**
     * Get the partner of a user
     * 
     * @param   int|object  $userId     The wp user or user id
     * @param   bool        $returnDate Wheter to return the wedding date, default false
     * 
     * @return  int|string              The partner user id of wedding date
     */
    public function getPartner($userId, $returnDate=false){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select * from $this->tableName where user_id_1='$userId' OR user_id_2='$userId' AND relationship='partner'";
        $results    = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        if($returnDate){
            return $results[0]->start_date;
        }

        if($results[0]->user_id_1 == $userId){
            return $results[0]->user_id_2;
        }else{
            return $results[0]->user_id_1;
        }
    }

    /**
     * Get the wedding date of a user
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  string                  The wedding date
     */
    public function getWeddingDate($userId){
        return $this->getPartner($userId, true);
    }

    /**
     * Get a value from the family meta db
     * 
     * @param   int|object  $userId     The wp user or user id
     * @param   string      $key        The key to get the value for
     */
    public function getFamilyMeta($userId, $key){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $subQuery   = "select family_id from $this->tableName where user_id_1='$userId' OR user_id_2='$userId' LIMIT 1";
        $query      = "select value from $this->metaTableName where family_id=($subQuery) AND `key`='$key'";
        return $wpdb->get_var($query);
    }

    /**
     * Stores a relationship in the db
     * 
     * @param   int     $userId     The main user this relationship applies to
     * @param   int     $userId2    The other user this relationship applies to
     * @param   string  $type       The relationship type (parent, partner, child, sibling)
     * @param   string  $start      The start of relatioship, i.e. wedding date   
     * 
     * @return  WP_Error|int        The id or an wp error object   
     */
    public function storeRelationship($userId, $userId2, $type, $start=''){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        if(is_object($userId2)){
            $userId2 = $userId2->ID;
        }

        if(empty($userId) || empty($userId2) || empty($type)){
            return new \WP_Error('family', 'Please supply valid values');
        }

        // Check if this relationship is already in the db
        switch($type){
            case 'siblings':
                if(in_array($userId2, $this->getSiblings($userId))){
                    return true;
                }
                break;
            case 'child':
                if(in_array($userId2, $this->getChildren($userId))){
                    return true;
                }
                break;
            case 'partner':
                if($this->getPartner($userId) == $userId2){
                    return true;
                }
                break;
        }

        // Check if this user is already in the db
        $query      = "select family_id from $this->tableName where user_id_1='$userId' OR user_id_2='$userId'";
        $familyId   = $wpdb->get_var($query);

        // Create family id if needed
        if(empty($familyId)){
            $query      = "SELECT MAX(family_id) FROM $this->tableName;";
            $familyId   = $wpdb->get_var($query) + 1;
        }

        $wpdb->insert(
            $this->tableName,
            [
                'family_id'     => $familyId,
			    'user_id_1'     => $userId,
                'user_id_2'     => $userId2,
                'relationship'  => $type,
                'start_date'    => $start
            ]
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('family', $wpdb->last_error);
		}

		return $wpdb->insert_id;
    }

    /**
     * Stores a family meta value
     * 
     * @param   int     $userId     The user this relationship applies to
     * @param   string  $key        The key
     * @param   string  $value      The value
     * 
     * @return  WP_Error|int        The id or an wp error object
     */
    public function storeFamilyMeta($userId, $key, $value){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        // Check if already there
        $v   = $this->getFamilyMeta($userId, $key);
        if($value == $v){
            return true;
        }

        // Fetch the family Id
        $query      = "select family_id from $this->tableName where user_id_1='$userId' OR user_id_2='$userId'";
        $familyId   = $wpdb->get_var($query);

        if(empty($familyId)){
            return new \WP_Error('family', 'No family found!');
        }

        $wpdb->insert(
            $this->metaTableName,
            [
                'family_id' => $familyId,
			    'key'       => $key,
                'value'     => $value
            ]
        );

        if(!empty($wpdb->last_error)){
			return new \WP_Error('family', $wpdb->last_error);
		}

		return $wpdb->insert_id;
    }
}