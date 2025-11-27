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
     * Gets the family id
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  int|false               The family id or false on not found
     */
    protected function getFamilyId($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query   = "select family_id from $this->tableName where user_id_1='$userId' OR user_id_2='$userId' LIMIT 1";
        
        return $wpdb->get_var($query);
    }

    /**
     * Checks if an user has family
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  bool                    True if user has family
     */
    public function hasFamily($userId){
        
        return !empty($this->getFamilyId($userId));
    }

    /**
     * Gets all family members of a user
     * 
     * @param   int|object  $userId     The wp user or user id
     * @param   bool        $flat       Wheter to return a flast arary of user ids or indexed by relation type. Default false for indexed
     * 
     * @return array|WP_Error           The requested array
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
     * @return  array|WP_Error          An array of children user ids or wp error
     */
    public function getChildren($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select user_id_2 from $this->tableName where user_id_1='$userId' AND relationship='child'";
        $results    = $wpdb->get_col($query);

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
     * @return  array|WP_Error          An array of sibling user ids
     */
    public function getSiblings($userId){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $siblings   = [];

        // Query all relations marked as siblings
        $query      = "select * from $this->tableName where (user_id_1='$userId' OR user_id_2='$userId') AND relationship='sibling'";
        $results   = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        foreach($results as $result){
            if($result->user_id_1 == $userId){
                $siblings[] = $result->user_id_2;
            }else{
                $siblings[] = $result->user_id_1;
            }
        }

        // Get all the users with the same parent
        $subQuery   = "select user_id_1 as parent from $this->tableName where user_id_2='$userId' AND relationship='child' LIMIT 1";
        $query      = "select user_id_2 from $this->tableName where user_id_1=($subQuery) AND relationship='child'";
        $results    = $wpdb->get_col($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        foreach($results as $userId){
            if($result != $userId){
                $siblings[] = $result;
            }
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
     * @return  array|WP_Error          An array of parent user ids
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

        if(empty($results)){
            return $results;
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
     * @param   int|object  $userId                 The wp user or user id
     * @param	bool	    $returnUser	            Whether to return the partners user id or the full user object default false for just the id
     * @param   bool        $returnDate             Wheter to return the wedding date, default false
     * 
     * @return  int|object|string|false||WP_Error   The partner user id or user object or wedding date or false if no partner or wp error on error
     */
    public function getPartner($userId, $returnUser=false, $returnDate=false){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $query      = "select * from $this->tableName where (user_id_1='$userId' OR user_id_2='$userId') AND relationship='partner'";
        $results    = $wpdb->get_results($query);

        if($wpdb->last_error !== ''){
            return new \WP_Error('family', $wpdb->last_error);
        }

        if(empty($results)){
            return false;
        }

        if($returnDate){
            return $results[0]->start_date;
        }

        if($results[0]->user_id_1 == $userId){
            $partner    = $results[0]->user_id_2;
        }else{
            $partner    = $results[0]->user_id_1;
        }

        if($returnUser){
            return get_userdata($partner);
        }

        return $partner;
    }

    /**
     * Get the wedding date of a user
     * 
     * @param   int|object  $userId     The wp user or user id
     * 
     * @return  string|false|WP_Error   The wedding date or false if no partner or wp error on error
     */
    public function getWeddingDate($userId){
        return $this->getPartner($userId, false, true);
    }

    /**
     * Get a value from the family meta db
     * 
     * @param   int|object  $userId     The wp user or user id
     * @param   string      $key        The key to get the value for, default empty for all
     * 
     * @return  mixed                   The value or an array of key values values or null if not found
     */
    public function getFamilyMeta($userId, $key=''){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $subQuery   = "select family_id from $this->tableName where user_id_1='$userId' OR user_id_2='$userId' LIMIT 1";
        $query      = "select value from $this->metaTableName where family_id=($subQuery)";

        if(!empty($key)){
            $query      = "select value from $this->metaTableName where family_id=($subQuery) AND `key`='$key'";
            return $wpdb->get_var($query);
        }

        $query      = "select * from $this->metaTableName where family_id=($subQuery)";
        $results    = $wpdb->get_results($query);

        if(empty($results)){
            return null;
        }

        return $results;
    }

    /**
     * Function to get proper family name
     * @param 	object|int		$user			WP User_ID or WP_User object
     * @param	bool			$lastNameFirst	Whether we should return the names as Lastname, Firstname. Default false
     * @param	mixed			$partnerId		Variable passed by reference to hold the partner id
     *
     * @return	string|false				    Family name string or last name when a single or false when not a valid user
    */
    public function getFamilyName($user, $lastNameFirst=false, &$partnerId=false) {
        if(is_numeric($user)){
            $user	= get_userdata($user);

            if(!$user){
                return false;
            }
        }

        $familyName	= $this->getFamilyMeta($user, 'family_name');

        if(!empty($familyName)){
            return $familyName.' family';
        }

        // user has no family
        if(!$this->hasFamily($user)){
            if($lastNameFirst){
                return "$user->last_name, $user->first_name";
            }

            return $user->display_name;
        }

        $name 	    = $user->last_name;
        $partner    = $this->getPartner($user, true);

        // user has a partner
        if($partner){

            if($partner->last_name != $user->last_name){
                // Male name first
                if(get_user_meta($user->ID, 'gender', true)[0] == 'Male'){
                    $name	= $user->last_name.' - '. $partner->last_name;
                }else{
                    $name	= $partner->last_name.' - '. $user->last_name;
                }
            }
        }

        $this->updateFamilyMeta($user, 'family_name', $name.' family');

        return $name.' family';
    }

    /**
     * Function to check if a certain user is a child
     * @param 	int		$userId	 	WP User_ID
     *
     * @return	bool				True if a child, false if not
    */
    public function isChild($userId) {
        return !empty($this->getParents($userId));
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
                $prevPartner    = $this->getPartner($userId);

                // Nothing to change
                if($prevPartner == $userId2){
                    return true;
                }

                // there is already a different partner set, remove it
                $this->removeRelationShip($userId, $prevPartner); 
                break;
        }

        // Check if this user is already in the db
        $familyId   = $this->getFamilyId($userId);

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
     * Updates the date of a relationship
     * 
     * @param   int     $userId         The main user this relationship applies to
     * @param   string  $weddingdate    The start of relatioship, i.e. wedding date   
     */
    public function updateWeddingDate($userId,  $weddingdate){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        if(empty($userId) || empty($weddingdate)){
            return new \WP_Error('family', 'Please supply valid values');
        }

        // Update weddingdate
        $query      = "UPDATE $this->tableName SET start_date='$weddingdate' WHERE (user_id_1='$userId' OR user_id_2='$userId') and `relationship`='partner'";
        $result     = $wpdb->query($query);

        if(!empty($wpdb->last_error)){
			return new \WP_Error('family', $wpdb->last_error);
		}

		return true;
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
    public function updateFamilyMeta($userId, $key, $value){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        // Check if already there
        $v   = $this->getFamilyMeta($userId, $key);
        if($value == $v){
            return true;
        }elseif(!empty($v)){
            // remove the old one
            $this->removeFamilyMeta($userId, $key);
        }

        // Fetch the family Id
        $familyId   = $this->getFamilyId($userId);

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

    /**
     * Remove relationship
     * 
     * @param 	object|int		$userId1			WP User_ID or WP_User object
     * @param 	object|int		$userId2			WP User_ID or WP_User object
     */
    public function removeRelationShip($userId1, $userId2){
        global $wpdb;

        if(is_object($userId1)){
            $userId1 = $userId1->ID;
        }

        if(is_object($userId2)){
            $userId2 = $userId2->ID;
        }

        if(empty($userId1) || empty($userId2)){
            return new \WP_Error('family', 'Please supply valid values');
        }

        $familyId   = $this->getFamilyId($userId1);

        // Delete relationship
        $query  = "DELETE FROM `$this->tableName` WHERE (`user_id_1` = $userId1 AND `user_id_2` = $userId2 ) OR (`user_id_1` = $userId2 AND `user_id_2` = $userId1 )";
        $wpdb->query( $query);

        // Check if this was the last family relationship
        $results    = $wpdb->get_results("SELECT * FROM $this->tableName WHERE family_id=$familyId");

        if(empty($results)){
            // Delete any meta's
            $wpdb->delete(
                $this->metaTableName,
                [
                    'family_id' => $familyId
                ],
                [
                    '%d'
                ],
            );
        }
    }

    /**
     * Remove family meta
     * 
     * @param 	object|int		$userId			WP User_ID or WP_User object
     * @param 	string          $key            The meta key
     * 
     * @return  WP_Error|int|null               The amount of rows deleted or an wp error object or null if nothing happened
     */
    public function removeFamilyMeta($userId, $key){
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        $familyId   = $this->getFamilyId($userId);

        if(!$familyId){
            return null;
        }

        // delete meta
        $wpdb->delete(
			$this->metaTableName,
			[
                'family_id' => $familyId,
                'key'       => $key,
            ],
			[
                '%d',
                '%s'
            ],
		);

        if(!empty($wpdb->last_error)){
			return new \WP_Error('family', $wpdb->last_error);
		}

        if($wpdb->rows_affected === 0){
            return null;
        }

        return $wpdb->rows_affected;
    }

    /**
     * Remove user from family
     * 
     * @param 	object|int		$userId			WP User_ID or WP_User object
     */
    function removeUser($userId) {
        global $wpdb;

        if(is_object($userId)){
            $userId = $userId->ID;
        }

        // delete entries where the first user id is this user
        $wpdb->delete(
            $this->tableName,
            [
                'user_id_1' => $userId
            ],
            [
                '%d'
            ]
        );

        // delete entries where the second user id is this user
        $wpdb->delete(
            $this->tableName,
            [
                'user_id_2' => $userId
            ],
            [
                '%d'
            ]
        );
    }
}