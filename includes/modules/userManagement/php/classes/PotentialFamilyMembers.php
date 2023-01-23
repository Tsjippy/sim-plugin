<?php
namespace SIM\USERMANAGEMENT;
use SIM;

class PotentialFamilyMembers{
    public $userId;
    public $birthday;
    public $gender;
    public $family;
    public $potentialSpouses;
    public $potentialFathers;
    public $potentialMothers;
    public $potentialChildren;
    public $users;

    public function __construct($userId){
        $this->userId               = $userId;
        $this->birthday	            = get_user_meta( $userId, 'birthday', true );
        $this->gender		        = get_user_meta( $userId, 'gender', true );
        $this->family		        = (array)SIM\familyFlatArray($userId);
        $this->potentialSpouses	    = [];
        $this->potentialFathers	    = [];
        $this->potentialMothers	    = [];
        $this->potentialChildren	= [];

        $this->getUsers();
    }

    /**
     * Gets all the users and makes sure the names are unique
     * Also get some meta data for them
     */
    public function getUsers(){
        //Get the id and the displayname of all users
        $this->users 					= get_users(
            array(
                'fields' 	=> array( 'ID','display_name' ) ,
                'orderby'	=> 'meta_value',
                'meta_key'	=> 'last_name',
                'exclude'   =>  [$this->userId]
            )
        );
        $existsArray = array();

        //Loop over all users to find dublicate displaynames
        foreach($this->users as $key=>&$user){
            //Get the displayname
            $displayName = strtolower($user->display_name);
            
            //If the display name is already found
            if (isset($existsArray[$displayName])){
                //Change current users displayname
                $user->display_name = $user->display_name." (".get_userdata($user->ID)->data->user_email.")";
                //Change previous found users displayname
                $this->users[$existsArray[$displayName]]->display_name = $this->users[$existsArray[$displayName]]->display_name." (".get_userdata($user->ID)->data->user_email.")";
            }else{
                //User has a so far unique displayname, add to array
                $existsArray[$displayName] = $key;
            }

            //Get the current gender
			$user->gender		= get_user_meta( $user->ID, 'gender', true );
			$user->birthday	= get_user_meta($user->ID, "birthday", true);
			$user->ageDifference 		= null;
			$user->age 		= null;
			if(!empty($user->birthday)){
				$user->age = date_diff( date_create( date("Y-m-d")), date_create($user->birthday))->y;
				if (!empty($this->birthday)){
					$user->ageDifference = date("Y", strtotime($user->birthday)) - date("Y", strtotime($this->birthday));
				}
			}
        }
    }

    /**
     * Get potential fathers
     */
	public function potentialParents(){
        foreach($this->users as $user){
            //Add the displayname as potential father if not younger then 18 and not part of the family
            if(($user->age == null || $user->age > 18) && !in_array($user->ID, $this->family)) {
                if (empty($user->gender) || $user->gender == 'male'){
                    $this->potentialFathers[$user->ID] = $user->display_name;
                }

                if (empty($user->gender) || $user->gender == 'female'){
                    $this->potentialMothers[$user->ID]	= $user->display_name;
                }
            }
        }
    }

    /**
     * Get potential spouses
     */
	public function potentialSpouses(){
        foreach($this->users as $user){
            //Check if current processing user already has a spouse
			$spouse = SIM\hasPartner($user->ID);

			if(
				$spouse == $this->userId				    ||	// This is our spouse
				(
					!is_numeric($spouse)				    &&	// this user does not have a spouse
					!in_array($user->ID, $this->family) 	&& 	// We are no family
                    (
						empty($user->gender) 		        || 	// Current user has no gender filled in
						empty($this->gender) 			    || 	// Or the gender is not filled in
						$user->gender != $this->gender		    // Or the genders differ
					)									    &&
					(
						!is_numeric($user->age) 	        ||	// The age is not filled in
						$user->age > 18				            // Older than 18
					)
				)
			){
				//Add the displayname as potential spouse
				$this->potentialSpouses[$user->ID] = $user->display_name;
			}
        }

        return $this->potentialSpouses;
    }

    /**
     * Get potential children
     */
	public function potentialChildren(){
        foreach($this->users as $user){
			$parents 		= SIM\getParents($user->ID, true);
            if(!$parents){
                $parents    = [];
            }
			
			if(
				in_array($this->userId, $parents)       || // is the current users child
				(
                    empty($parents)				        &&  // is not a child
                    !in_array($user->ID, $this->family) &&  // is not family already
				    (
                        $user->ageDifference == null    ||  // there is no age diff
				        $user->ageDifference > 16           // the age diff is at least 16 years
                    )
                )
			){
				$this->potentialChildren[$user->ID]	= $user->display_name;
			}
        }

        return $this->potentialChildren;
    }
}
