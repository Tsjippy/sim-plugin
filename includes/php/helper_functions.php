<?php
namespace SIM;

use WP_Error;

//Update user meta of a user and all of its relatives

 /**
 * Update user meta for a specific key for the current user and all of its relatives
 * @param  int		$userId 	WP_User id
 * @param  string	metaKey		The meta key to update or create
 * @param  string	value  		The new value
 */
function updateFamilyMeta($userId, $metaKey, $value){
	if($value == 'delete'){
		delete_user_meta($userId, $metaKey);
	}else{
		update_user_meta( $userId, $metaKey, $value);
	}
		
	//Update the meta key for all family members as well
	$family = familyFlatArray($userId);
	if (is_array($family) && !empty($family)){
		foreach($family as $relative){
			if($value == 'delete'){
				delete_user_meta($relative, $metaKey);
			}else{
				//Update the marker for the relative as well
				update_user_meta($relative, $metaKey, $value);
			}
		}
	}
}

/**
 * Create a dropdown with all users
 * @param	bool		$returnFamily  	Whether we should group families in one entry default false
 * @param	bool		$adults			Whether we should only get adults
 * @param	array		$fields    		Extra fields to return
 * @param	array		$extraArgs		An array of extra query arguments
 * @param	array		$excludeIds		An array of user id's to be excluded
 *
 * @return	array						An array of WP_Users
*/
function getUserAccounts($returnFamily=false, $adults=true, $fields=[], $extraArgs=[], $excludeIds=[1], $uniqueDisplayName=false){
	$doNotProcess 		= $excludeIds;
	$cleanedUserArray 	= [];
	
	$arg = [];
	
	if(!empty($fields)){
		$arg['fields'] = $fields;
	}
	
	$arg 	= array_merge_recursive($arg, $extraArgs);
	
	$users  = get_users($arg);
	
	//Loop over the users and remove any user who should not be in the dropdown
	foreach($users as $user){
		// If ‘fields‘ is set to any individual wp_users table field, an array of IDs will be returned.
		// In that case the user will not be an object
		if(is_object($user)){
			$userId	= $user->ID;
		}else{
			$userId	= $user;
		}
		//If we should only return families
		if($returnFamily){
			//Current user is a child, exclude it
			if (isChild($userId)){
				$doNotProcess[] = $userId;
			}

			//Check if this adult is not already in the list
			elseif(!in_array($userId, $doNotProcess)){
				//Change the display name
				$user->display_name = getFamilyName($user, false, $partnerId);

				if ($partnerId){
					$doNotProcess[] = $partnerId;
				}
			}
		//Only returning adults, but this is a child
		}elseif($adults && isChild($userId)){
			$doNotProcess[] = $userId;
		}
	}

	// Return the ids we need
	if(is_numeric($user)){
		sort($users);
		
		return array_diff( $users, $doNotProcess );
	}

	$existsArray 	= array();
	
	//Loop over all users again to make sure we do not have duplicate names
	foreach($users as $key => $user){
		if(in_array($user->ID, $doNotProcess)){
			continue;
		}
		
		if($uniqueDisplayName){
			//Get the full name
			$fullName = strtolower("$user->first_name $user->last_name");
			
			//If the full name is already found
			if (isset($existsArray[$fullName])){
				// Change current users last name
				$user->last_name = "$user->last_name ($user->user_email)";

				// Change current users display name
				if($user->display_name == $user->nickname){
					$user->display_name = "$user->first_name $user->last_name";
				}else{
					$user->display_name = $user->nickname;
				}
				
				// Change previous found users last name
				$prevUser = $users[$existsArray[$fullName]];
				
				// But only if not already done
				if(!str_contains($prevUser->last_name, $prevUser->user_email)  ){
					$prevUser->last_name = "$prevUser->last_name ($prevUser->user_email)";
				}

				// Change current users display name
				if($prevUser->display_name == $prevUser->nickname){
					$prevUser->display_name = "$prevUser->first_name $prevUser->last_name";
				}else{
					$prevUser->display_name = $prevUser->nickname;
				}

				$cleanedUserArray[$prevUser->ID] = $prevUser;
			}else{
				//User has a so far unique displayname, add to array
				$existsArray[$fullName] = $key;
			}
		}

		//Add the user to the cleaned array if not in the donotprocess array
		$cleanedUserArray[$user->ID] = $user;
	}

	usort($cleanedUserArray, function ($a, $b) {
		return strcmp($a->last_name, $b->last_name);
	});
	
	return $cleanedUserArray;
}

/**
 * Create a dropdown with all users
 * @param 	string				$title	 		The title to display above the select
 * @param	bool				$onlyAdults	 	Whether children should be excluded. Default false
 * @param	bool				$families  		Whether we should group families in one entry default false
 * @param	string				$class			Any extra class to be added to the dropdown default empty
 * @param	string				$id				The name or id of the dropdown, default 'user-selection'
 * @param	array				$args    		Extra query arg to get the users
 * @param	int|string|array	$userId			The current selected user id or name or array of multiple user-ids
 * @param	array				$excludeIds		An array of user id's to be excluded
 * @param	string				$type			Html input type Either select or list
 *
 * @return	string						The html
 */
function userSelect($title, $onlyAdults=false, $families=false, $class='', $id='user-selection', $args=[], $userId='', $excludeIds=[1], $type='select', $listId='', $multiple=false){

	wp_enqueue_script('sim_user_select_script');
	$html = "";

	if(empty($userId) && !empty($_REQUEST["user-id"]) && !is_numeric($userId)){
		$userId = $_REQUEST["user-id"];
	}
	
	//Get the id and the displayname of all users
	$users 			= getUserAccounts($families, $onlyAdults, [], $args, $excludeIds, true);
	
	$html .= "<div class='option-wrapper'>";
	if(!empty($title)){
		$html .= "<h4>$title</h4>";
	}

	$inputClass	= 'wide';
	if($type == 'select'){
		if($multiple){
			$multiple	= 'multiple';

			if(!str_contains($id, '[]')){
				$id	.= '[]';
			}
		}

		$html .= "<select name='$id' class='$class user-selection' value='' $multiple>";
			foreach($users as $key=>$user){
				if(empty($user->first_name) || empty($user->last_name) || $families){
					$name	= $user->display_name;
				}else{
					$name	= "$user->first_name $user->last_name";
				}

				if ($userId == $user->ID || (is_array($userId) && in_array($user->ID, $userId))){
					//Make this user the selected user
					$selected='selected="selected"';
				}else{
					$selected="";
				}
				$html .= "<option value='$user->ID' $selected>$name</option>";
			}
		$html .= '</select>';
	}elseif($type == 'list'){
		if($multiple){
			$html	.= '<ul class="list-selection-list">';
				// we supplied an array of users
				if(is_array($userId)){
					foreach($userId as $id){
						$html	.= "<li class='list-selection'>";
							$html	.= "<button type='button' class='small remove-list-selection'><span class='remove-list-selection'>×</span></button>";
						if(is_numeric($id)){
							$user	= get_userdata($id);
							if($user){
								$html	.= "<input type='hidden' class='no-reset' class='no-reset' name='{$id}[]' value='{$user->ID}'>";
								$html	.= "<span>{$user->display_name}</span>";
							}
						}else{
							$html	.= "<span>";
								$html	.= "<input type='text' name='{$id->ID}[]' value='$id->ID' readonly=readonly style='width:".strlen($id->display_name)."ch'>";
							$html	.= "</span>";
						}
					}
	
					$userId	= '';
				}
			$html	.= '</ul>';
	
			$inputClass	.= ' datalistinput multiple';
		}

		$value	= '';

		if(!is_numeric($userId)){
			$value	= $userId;
		}

		if(empty($listId)){
			$listId = $id."-list";
		}

		$datalist = "<datalist id='$listId' class='$class user-selection'>";
			foreach($users as $key=>$user){
				if($families || empty($user->first_name) || empty($user->last_name)){
					$name	= $user->display_name;
				}else{
					$name	= "$user->first_name $user->last_name";
				}
				
				if ($userId == $user->ID){
					//Make this user the selected user
					$value	= $user->display_name;
				}
				$datalist .= "<option value='$name' data-user-id='$user->ID' data-value='$user->ID'>";
			}
		$datalist .= '</datalist>';

		$html	.= "<input type='text' class='$inputClass' name='$id' id='$id' list='$listId' value='$value'>";
		$html	.= $datalist;
	}
	
	$html	.= '</div>';
	
	return $html;
}

/**
 * Returns the current url
 *
 * @param	bool	$trim		Remove request params
 *
 * @return	string				The url
*/
function currentUrl($trim=false){
	if(defined('REST_REQUEST') && !empty($_SERVER['HTTP_REFERER'])){
		$url		= $_SERVER['HTTP_REFERER'];
	}else{
		$url	 = '';
		$url 	.=	$_SERVER['REQUEST_SCHEME']."://";
		$url	.=	$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}

	if($trim){
		$url	 = trim(explode('?', $url)[0], "/");
	}
	return $url;
}

/**
 * Returns the current url
 *
 * @return	string						The url
*/
function getCurrentUrl(){
	return currentUrl();
}

/**
 * Transforms an url to a path
 * @param 	string		$url	 		The url to be transformed
 *
 * @return	string						The path
*/
function urlToPath($url){
	if(gettype($url) != 'string'){
		printArray("Invalid url:");
		printArray($url);
		return '';
	}

	if(file_exists($url)){
		return $url;
	}
	
	$siteUrl	= str_replace(['https://', 'http://'], '', SITEURL);
	$url		= str_replace(['https://', 'http://'], '', urldecode($url));
	$url		= explode('?', $url)[0];
	
	return str_replace(trailingslashit($siteUrl), str_replace('\\', '/', ABSPATH), $url);
}

/**
 * Transforms a path to an url
 * @param 	string		$path	 		The path to be transformed
 *
 * @return	string|false				The url or false on failure
*/
function pathToUrl($path){
	if(empty($path)){
		return false;
	}
	
	// Check if already an url
	if (filter_var($path, FILTER_VALIDATE_URL)) {
		return $path;
	}

	if(is_string($path)){
		$base	= str_replace('\\', '/', ABSPATH);
		$path	= str_replace('\\', '/', $path);

		//Replace any query params
		$exploded	= explode('?', $path);
		$path		= $exploded[0];
		$query		= '';
		if(!empty($exploded[1])){
			$query	= '?'.$exploded[1];
		}

		if(!str_contains($path, ABSPATH)  && !str_contains($path, $base) ){
			$path	= $base.$path;
		}

		if(!file_exists($path)){
			return false;
		}
		$url	= str_replace($base, SITEURL.'/', $path).$query;

		// fix any spaces
		$url	= str_replace(' ', '%20', $url);

		// not a valid url
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			printArray($url);
			return false;
		}
	}else{
		$url	= $path;
	}
	
	return $url;
}

/**
 * Prints something to the log file and optional to the screen
 * @param 	string		$message	 			The message to be printed
 * @param	bool		$display				Whether to print the message to the screen or not
 * @param	bool|int	$printFunctionHiearchy	Whether to print the full backtrace, false for not printing, true for all, number for max depth
*/
function printArray($message, $display=false, $printFunctionHiearchy=false){
	$bt		= debug_backtrace();

	if($printFunctionHiearchy){
		error_log("Called from:");
		foreach($bt as $index=>$trace){
			// stop if we have reached the max depth
			if(is_numeric($printFunctionHiearchy) && $index == $printFunctionHiearchy){
				break;
			}
			
			$path	= str_replace(MODULESPATH, '', $trace['file']);

			error_log($index);
			error_log( "    File: $path");
			error_log( "    Line {$trace['line']}");
			error_log( "    Function: {$trace['function']}");
			error_log( "    Args:");
			error_log(print_r($trace['args'], true));
		}
	}else{
		$caller = array_shift($bt);
		$path	= str_replace(MODULESPATH, '', $caller['file']);
		error_log("Called from file $path line {$caller['line']}");
	}

	if(is_array($message) || is_object($message)){
		error_log(print_r($message, true));
	}else{
		error_log(date(DATEFORMAT.' '.TIMEFORMAT, time()).' - '.$message);
	}
	
	if($display){
		echo "<pre>";
		echo "Called from file {$caller['file']} line {$caller['line']}<br><br>";
		print_r($message);
		echo "</pre>";
	}
}

/**
 * Prints html properly outlined for easy debugging
 */
function printHtml($html){
	$tabs	= 0;

	// Split on the < symbol to get a list of opening and closing tags
	$html		= explode('>', $html);
	$newHtml	= '';

	// loop over the elements
	foreach($html as $index=>$el){
		if(empty($el)){
			continue;
		}

		// Split the line on a closing character </
		$lines	= explode('</', $el);

		if(!empty($lines[0])){
			$newHtml	.= "\n";
			
			// write as many tabs as need
			for ($x = 0; $x <= $tabs; $x++) {
				$newHtml	.= "\t";
			}

			// then write the first element
			$newHtml	.= $lines[0];
		}

		if(
			substr($el, 0, 1) == '<' && 						// Element start with an opening symbol
			substr($el, 0, 2) != '</' && 						// It does not start with a closing symbol
			substr($el, 0, 6) != '<input' && 					// It does not start with <input (as that one does not have a closing />)
			(
				substr($el, 0, 7) != '<option' || 				// It does not start with <option (as that one does not have a closing />)
				str_contains( $html[$index+1], '</option') 		// or the next element contains a closing option
			) &&
			$el != '<br'
		){
			$tabs++;
		}
		
		if(isset($lines[1])){
			$tabs--;

			$newHtml	.= "\n";

			for ($x = 0; $x <= $tabs; $x++) {
				$newHtml	.= "\t";
			}
			$newHtml	.= '</'.$lines[1].'>';
		}else{
			$newHtml	.= '>';
		}
	}

	printArray($newHtml);
}

/**
 * Creates s dropdown to select a page
 * @param 	string		$selectId	 	The id or name of the dropown
 * @param	bool		$pageId	 		The current select page id default to empty
 * @param	string		$class			Any extra class to be added to the dropdown default empty
 * @param	array		$postTypes    	The posttypes to include archive pages for. Defaults to pages and locations
 *
 * @return	string						The dropdown html
*/
function pageSelect($selectId, $pageId=null, $class="", $postTypes=['page', 'location'], $includeTax=true){
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_title',
			'order' 		=> 'asc',
			'post_status' 	=> 'publish',
			'post_type'     => $postTypes,
			'posts_per_page'=> -1,
			'exclude'		=> [get_the_ID()]
		)
	);

	$options	= [];
	foreach ( $pages as $page ) {
		$options[$page->ID]	= $page->post_title;
	}

	if($includeTax){
		$taxonomies = get_taxonomies(
			array(
			'public'   => true,
			'_builtin' => false
			)
		);
		foreach ( $taxonomies as $taxonomy ) {
			$options[$taxonomy]	= ucfirst($taxonomy);
		}

		$terms		= get_terms(['hide_empty'=>false]);
		foreach ( $terms as $term ) {
			$options[$term->taxonomy.'/'.$term->slug]	= $term->name;
		}
	}

	asort($options);

	$html = "<select name='$selectId' id='$selectId' class='selectpage $class'>";
		$html .= "<option value=''>---</option>";
	
		foreach ( $options as $id=>$name ) {
			$selected	= "";
			if (!empty($pageId) && $pageId == $id){
				$selected='selected=selected';
			}
			$html .= "<option value='$id' $selected>$name</option>";
		}
	
	$html .= "</select>";
	return $html;
}

/**
 * Checks if a child is a son or daughter
 * @param 	int		$userId	 	The User_ID of the child
 *
 * @return	string				Either "son", "daughter" or 'child'
*/
function getChildTitle($userId){
	$gender = get_user_meta( $userId, 'gender', true );
	if($gender == 'male'){
		$title = "son";
	}elseif($gender == 'female'){
		$title = "daughter";
	}else{
		$title = "child";
	}
	
	return $title;
}

/**
 * Get the family of an user
 *
 * @param 	int	$userId		The user id to get the family for
 *
 * @return	array			The family array
 */
function getUserFamily($userId){

	$family = cleanUpNestedArray((array)get_user_meta( $userId, 'family', true ));

	// If there is no family, but a family name is set
	if(count($family) == 1 && isset($family['name'])){
		unset($family['name']);
	}

	return $family;
}

/**
 * Gets the children array and add it to the main level of the array
 * @param 	int		$userId	 	WP User_ID
 *
 * @return	array				All family members in one array
*/
function familyFlatArray($userId){
	$family	= getUserFamily($userId);

	//make the family array flat
	if (isset($family["children"])){
		$family = array_merge($family["children"], $family);
		unset($family["children"]);
	}
	
	return $family;
}

/**
 * Check if user has partner
 * @param 	int		$userId	 	WP User_ID
 * @param	bool	$returnUser	Whether to return the partners user id or the full user object default false for just the id
 *
 * @return	int|object|false			The partner user id, partner user object or false if no partner
*/
function hasPartner($userId, $returnUser=false) {
	$family = get_user_meta($userId, "family", true);
	if(is_array($family) && isset($family['partner']) && is_numeric($family['partner'])){
		if($returnUser){
			return get_userdata($family['partner']);
		}
		return $family['partner'];
	}

	return false;
}

/**
 * Get users parents
 * @param 	int		$userId	 	WP User_ID
 * @param	bool	$onlyId		Whether to return the parent user or just the user id. Default false
 *
 * @return	array|false			Array containing the id of the father and the mother, or false if no parents
*/
function getParents($userId, $onlyId=false){
	$family 	= get_user_meta( $userId, 'family', true );
	$parents 	= [];
	foreach (["father","mother"] as $parent) {
		if (isset($family[$parent])) {
			$parent = get_userdata($family[$parent]);
			if($parent){
				if($onlyId){
					$parents[]	= $parent->ID;
				}else{
					$parents[]	= $parent;
				}
			}
		}
	}

	if(empty($parents)){
		return false;
	}
	return $parents;
}

/**
 * Function to check if a certain user is a child
 * @param 	int		$userId	 	WP User_ID
 *
 * @return	bool				True if a child, false if not
*/
function isChild($userId) {
	$family = get_user_meta($userId, "family", true);
	if(is_array($family) && (isset($family["father"]) || isset($family["mother"]))){
		return true;
	}
	return false;
}

/**
 * Function to get proper family name
 * @param 	object|int		$user			WP User_ID or WP_User object
 * @param	bool			$lastNameFirst	Whether we should return the names asl Lastname, Firstname. Default false
 * @param	mixed			$partnerId		Variable passed by reference to hold the partner id
 *
 * @return	string|false				Family name string or last name when a single or false when not a valid user
*/
function getFamilyName($user, $lastNameFirst=false, &$partnerId=false) {
	if(is_numeric($user)){
		$user	= get_userdata($user);

		if(!$user){
			return false;
		}
	}

	$partnerId	= false;

	$family 	= get_user_meta($user->ID, "family", true);

	if(isset($family['children']) && empty($family['children'])){
		unset($family['children']);
	}

	if(isset($family['partner']) && is_numeric($family['partner'])){
		$partnerId	= $family['partner'];
	}

	if(isset($family['siblings'])){
		unset($family['siblings']);
	}

	$familyName	= '';
	if(!empty($family['name'])){
		$familyName	= $family['name'];
		unset($family['name']);
	}

	// user has family
	if(empty($family)){
		if($lastNameFirst){
			return "$user->last_name, $user->first_name";
		}

		return $user->display_name;
	}
	
	if(!empty($familyName)){
		return $familyName.' family';
	}

	$name 	= $user->last_name;

	// user has a partner
	if(isset($family['partner']) && is_numeric($family['partner'])){
		$partner	= get_userdata($family['partner']);

		if($partner->last_name != $user->last_name){
			// Male name first
			if(get_user_meta($user->ID, 'gender', true)[0] == 'Male'){
				$name	= $user->last_name.' - '. $partner->last_name;
			}else{
				$name	= $partner->last_name.' - '. $user->last_name;
			}
		}
	}

	return $name.' family';
}

/**
 * Get an users age
 * @param 	int		$userId	 	WP User_ID
 * @param	bool	$numeric	Whether to return the age as a number or a word. Default false
 *
 * @return	int					Age in years
*/
function getAge($userId, $numeric=false){
	if(is_numeric($userId)){
		$birthday = get_user_meta( $userId, 'birthday', true );

		if(empty($birthday)){
			return false;
		}
	}else{
		$birthday = $userId;
	}

	if(is_array($birthday)){
		$birthday	= array_values($birthday)[0];
	}
	
	if(empty($birthday)){
		return;
	}

	$birthDate = explode("-", $birthday);

	if (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")){
		$age = (date("Y") - $birthDate[0]) - 1;
	}else{
		$age = (date("Y") - $birthDate[0]);
	}
	
	if($numeric){
		return $age;
	}
	return numberToWords($age);
}

/**
 * Converts an number to words
 * @param 	string|int|float	the number to be converted
 *
 * @return	string				the number in words
*/
function numberToWords($number) {
    $hyphen 		= '-';
    $conjunction 	= ' and ';
    $separator 		= ', ';
    $negative 		= 'negative ';
    $decimal 		= ' Thai Baht And ';

	$firstDic		= [
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'fifth',
        6 => 'sixth',
        7 => 'seventh',
        8 => 'eight',
        9 => 'nineth',
        10 => 'tenth',
        11 => 'eleventh',
        12 => 'twelfth',
        13 => 'thirteenth',
        14 => 'fourteenth',
        15 => 'fifteenth',
        16 => 'sixteenth',
        17 => 'seventeenth',
        18 => 'eighteenth',
        19 => 'nineteenth',
		20 => 'twentieth',
		30 => 'thirtieth',
		40 => 'fortieth',
		50 => 'fiftieth',
		60 => 'sixtieth',
		70 => 'seventieth',
		80 => 'eightieth',
		90 => 'ninetieth'
	];
    $dictionary 	= array(
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nin',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'fourty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
        1000000 => 'million',
        1000000000 => 'billion',
        1000000000000 => 'trillion',
        1000000000000000 => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

	// If not numeric return an number from a word
    if (!is_numeric($number)) {
        return array_search(strtolower($number), $dictionary);
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return $negative . numberToWords(abs($number));
    }

    $string = $fraction = null;

    if (str_contains($number, '.')) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case isset($firstDic[$number]):
            $string = $firstDic[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $firstDic[$units];
            }
            break;
        case $number < 1000:
            $hundreds = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numberToWords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}

/**
 * Updated nested array based on array of keys
 * @param	array		$keys  			The keys
 * @param	array		$array			Reference to an array
 * @param	string		$value    		The value to set
*/
function addToNestedArray($keys, &$array=array(), $value=null) {
	//$temp point to the same content as $array
	$temp =& $array;
	if(!is_array($temp)){
		$temp = [];
	}
	
	//loop over all the keys
	foreach($keys as $key) {
		if(!isset($temp[$key])){
			$temp[$key]	= [];
		}
		//$temp points now to $array[$key]
		$temp =& $temp[$key];
	}
	
	//We update $temp resulting in updating $array[X][y][z] as well
	$temp[] = $value;
}

/**
 * Removes a key from a nested array based on array of keys
 * @param	array		$array			Reference to an array
 * @param	array		$arrayKeys    	Array of keys
 *
 * @return array						The array
*/
function removeFromNestedArray(&$array, $arrayKeys){
	if(!is_array($array)){
		return $array;
	}

	$last 		= array_key_last($arrayKeys);
	$current 	=& $array;
    foreach($arrayKeys as $index=>$key){
		if($index == $last){
			unset($current[$key]);
		}else{
        	$current =& $current[$key];
		}
    }

    return $current;
}

/**
 * Removes all empty values from array, if the emty value is an array keep it by default
 * @param	array		$array			Reference to an array
 * @param	bool		$delEmptyArrays Wheter to delete empty nested arrays or not. Default false
*/
function cleanUpNestedArray($array, $delEmptyArrays=false){
	if(!is_array($array)){
		return;
	}

	return array_filter(
		$array,
		function($value){
			if(is_array($value)){
				return cleanUpNestedArray($value);
			}

			return !empty($value);
		}
	);
}

/**
 * Get the value of a given meta key
 * @param	int		$userId			WP_User id
 * @param	string	$metaKey    	The meta key we should get the value for
 * @param	array	$values			The optional values of a metakey
 *
 * @return string					The value
*/
function getMetaArrayValue($userId, $metaKey, $values=null){
	if(empty($metaKey)){
		return $values;
	}
	
	if($values === null && !empty($metaKey)){
		//get the basemetakey in case of an indexed one
		if(preg_match('/(.*?)\[/', $metaKey, $match)){
			$baseMetaKey	= $match[1];
		}else{
			//just use the whole, it is not indexed
			$baseMetaKey	= $metaKey;
		}
		$values	= (array)get_user_meta($userId, $baseMetaKey, true);
	}

	$value	= $values;

	//Return the value of the variable whos name is in the keystringvariable
	preg_match_all('/\[(.*?)\]/', $metaKey, $matches);
	if(!empty($matches[1]) && is_array($matches[1])){
		foreach($matches[1] as $key){
			if(!is_array($value)){
				break;
			}

			if(empty($key)){
				$value = array_values($value)[0];
			}else{
				if(!isset($value[$key])){
					$key	= str_replace('-files', '', $key);
				}

				if(isset($value[$key])){
					$value	= $value[$key];
				}else{
					$value	= '';
				}
			}
		}
	}

	return $value;
}

/**
 * Finds a value in an nested array
 */
function arraySearchRecursive($needle, $haystack, $strict=true, $stack=array()) {
    $results = array();
    foreach($haystack as $key=>$value) {
        if(($strict && $needle == $value) || (is_string($value) && !$strict && str_contains($value, $needle))) {
			$value	= maybe_unserialize($value);

			if(!is_array($value)){
            	$results[] = array_merge($stack, array($key));
			}
        }

        if(is_array($value) && count($value) != 0) {
            $results = array_merge($results, arraySearchRecursive($needle, $value, $strict, array_merge($stack, array($key))));
        }
    }
    return($results);
}

/**
 * Creates a submit button with a loader gif
 * @param	string	$elementId		The name or id of the button
 * @param	string	$buttonText    	The text of the button
 * @param	string	$extraClass		Any extra class to add to the button
 *
 * @return string					The html
*/
function addSaveButton($elementId, $buttonText, $extraClass = ''){
	$html = "<div class='submit-wrapper'>";
		$html .= "<button type='button' class='button form-submit $extraClass' name='$elementId'>$buttonText</button>";
	$html .= "</div>";
	
	return $html;
}

/**
 * Creates a submit button with a loader gif
 * @param	string	$targetFile		The path to a file
 * @param	string	$title    		The title for the file
 * @param	string	$description	The default description of the file
 *
 * @return 	int|WP_Error			The post id of the created attachment, WP_Error on error
*/
function addToLibrary($targetFile, $title='', $description=''){
	try{
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $targetFile ), null );

		if(empty($title)){
			$title = preg_replace( '/\.[^.]+$/', '', basename( $targetFile ) );
		}
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           =>	pathToUrl($targetFile ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => $description,
			'post_status'    => 'publish'
		);
		
		// Insert the attachment.
		$postId = wp_insert_attachment( $attachment, $targetFile);

		//Schedule the creation of subsizes as it can take some time.
		// By doing it this way its asynchronous
		wp_schedule_single_event( time(), 'process_images_action', [$postId]);
		
		return $postId;
	}catch(\GuzzleHttp\Exception\ClientException $e){
		$result = json_decode($e->getResponse()->getBody()->getContents());
		$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
		printArray($errorResult);
		if(isset($postId)){
			return $postId;
		}

		return new WP_Error('library', $errorResult);
	}catch(\Exception $e) {
		$errorResult = $e->getMessage();
		printArray($errorResult);
		if(isset($postId)){
			return $postId;
		}
		return new WP_Error('library', $errorResult);
	}
}

/**
 * Creates sub images using wp_maybe_generate_attachment_metadata
 * @param	int|WP_Post	$post		WP_Post or attachment id
*/
function processImages($post){
	include_once( ABSPATH . 'wp-admin/includes/image.php' );

	if(is_numeric($post)){
		$post	= get_post($post);
	}
	wp_maybe_generate_attachment_metadata($post);
}

/**
 * Get html to select an image
 * @param	string 		$key			the image key in the module settings
 * @param	string		$name			Human readable name of the picture
 * @param	array		$settings		The module settings array
 * @param	string		$type			The image type you allow
 *
 * @return	string						the selector html
*/
function pictureSelector($key, $name, $settings, $type=''){
	wp_enqueue_media();
	wp_enqueue_script('sim_picture_selector_script', INCLUDESURL.'/js/select_picture.min.js', array(), '7.0.0',true);
	wp_enqueue_style( 'sim_picture_selector_style', INCLUDESURL.'/css/picture_select.min.css', array(), '7.0.0');

	if(empty($settings['picture_ids'][$key])){
		$hidden		= 'hidden';
		$src		= '';
		$id			= '';
		$text		= 'Select';
	}else{
		$id			= $settings['picture_ids'][$key];
		$src		= wp_get_attachment_image_url($id);
		$hidden		= '';
		$text		= 'Change';
	}
	?>
	<div class='picture-selector-wrapper'>
		<div class='image-preview-wrapper <?php echo $hidden;?>'>
			<img loading='lazy' class='image-preview' src='<?php echo $src;?>' alt=''>
		</div>
		<input type="button" class="button select-image-button" value="<?php echo $text;?> picture for <?php echo strtolower($name);?>" <?php if(!empty($type)){echo "data-type='$type'";}?>/>
		<input type='hidden' class='no-reset' class='no-reset' class='no-reset' class='no-reset' class='no-reset' class='no-reset' class='no-reset' class='no-reset' class="image-attachment-id" name='picture-ids[<?php echo $key;?>]' value='<?php echo $id;?>'>
	</div>
	<?php
}

/**
 * Remove a single file or a folder including all the files
 * @param	string 		$target			The path to delete
*/
function removeFiles($target){
	if(is_dir($target)){

		$files = glob( $target . '*', GLOB_MARK );

		foreach( $files as $file ){
			removeFiles( $file );
		}

		rmdir( $target );
	} elseif(is_file($target)) {
		unlink( $target );
	}
}

/**
 * Checks if a string is a date
 * @param	string 		$date			the date to check
 *
 * @return	bool						Whether a date or not
*/
function isDate($date){
	if(is_array($date)){
		$date	= array_values($date)[0];
	}
	
	if (preg_match("/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/", $date)) {
		return true;
	}
		
	return false;
}

/**
 * Checks if a string is a time
 * @param	string 		$time			the time to check
 *
 * @return	bool						Whether a time or not
*/
function isTime($time){
	if (preg_match("/^\d{2}:\d{2}$/",$time)) {
		return true;
	}
	return false;
}

/**
 * Returns a unique username
 * @param	string 		$firstName		First name of a new user
 * @param	string 		$lastName		Last name of a new user
 *
 * @return	string						An unique username
*/
function getAvailableUsername($firstName, $lastName){
	//Check if a user with this username already exists
	$i =1;
	while (true){
		//Create a username
		$userName = str_replace(' ', '', $firstName.substr($lastName, 0, $i));
		//Check for availability
		if (get_user_by("login",$userName) == ""){
			//available, return the username
			return $userName;
		}
		$i += 1;
	}
}

/**
 * Creates an useraccount
 * @param	string 		$firstName		First name of a new user
 * @param	string 		$lastName		Last name of a new user
 * @param	string		$email			E-mail adres
 * @param	bool		$approved		Whether the user is already approved or not. Default false
 * @param	string		$validity		How long the account will be valid, default 'unlimited'
 *
 * @return	int|WP_Error				The new user id or WP_Error on error
*/
function addUserAccount($firstName, $lastName, $email, $approved = false, $validity = 'unlimited', $roles=[]){
	//Get the username based on the first and lastname
	$username = getAvailableUsername($firstName, $lastName);
	
	//Build the user
	$userData = array(
		'user_login'    => $username,
		'last_name'     => $lastName,
		'first_name'    => $firstName,
		'user_email'    => $email,
		'display_name'  => "$firstName $lastName",
		'nickname'  	=> "$firstName $lastName",
		'user_pass'     => null
	);
	
	//Give it the guest user role
	if($validity != "unlimited"){
		$userData['role'] = 'subscriber';
	}

	//Insert the user
	$userId = wp_insert_user( $userData ) ;

	// User creation failed
	if(is_wp_error($userId)){
		printArray($userId->get_error_message());
		return new \WP_Error('User creation', $userId->get_error_message());
	}

	if(!empty($roles) && function_exists('SIM\USERMANAGEMENT\updateRoles')){
		USERMANAGEMENT\updateRoles($userId, $roles);
	}
	
	if($approved){
		delete_user_meta( $userId, 'disabled');
		wp_send_new_user_notifications($userId, 'user');

		//Force an account update
		do_action( 'sim_approved_user', $userId);
	}else{
		//Make the useraccount inactive
		update_user_meta( $userId, 'disabled', 'pending');
	}

	//Store the validity
	update_user_meta( $userId, 'account_validity', $validity);
	
	// Return the user id
	return $userId;
}

/**
 * Get profile picture html
 * @param	int 		$userId				WP_user id
 * @param	array 		$size				Size (width, height) of the image. Default [50,50]
 * @param	bool		$showDefault		Whether to show a default pictur if no user picture is found. Default true
 * @param	bool		$famillyPicture		Whether or not to use the family picture
 * @param	bool		$wrapInLink			Whether or not to make the picture clickable to the full size picture
 *
 * @return	string|false					The picture html or false if no picture
 */
function displayProfilePicture($userId, $size=[50,50], $showDefault = true, $famillyPicture=false, $wrapInLink=true){
	
	$attachmentId = get_user_meta($userId, 'profile_picture', true);

	if($famillyPicture){
		$family			= get_user_meta($userId, 'family', true);

		if(isset($family['picture']) && is_numeric($family['picture'])){
			$attachmentId	= $family['picture'];
		}
	}
	if(!empty($attachmentId) && is_array($attachmentId)){
		$attachmentId	= $attachmentId[0];
	}
	
	$defaultUrl		= plugins_url('pictures/usericon.png', __DIR__);
	$defaultPicture	= "<img loading='lazy' width='{$size[0]}' height='{$size[1]}' src='$defaultUrl' class='profile-picture attachment-{$size[0]}x{$size[1]} size-{$size[0]}x{$size[1]}' loading='lazy'>";

	if(is_numeric($attachmentId)){
		$url = wp_get_attachment_image_url($attachmentId,'Full size');

		if(!$url || !file_exists(urlToPath($url))){
			if($showDefault){
				return $defaultPicture;
			}else{
				return false;
			}
		}

		$image	= "<img loading='lazy' width='{$size[0]}' height='{$size[1]}' src='$url' class='profile-picture attachment-{$size[0]}x{$size[1]} size-{$size[0]}x{$size[1]}' loading='lazy'>";
		if($wrapInLink){
			return "<a href='$url'>$image</a>";
		}else{
			return $image;
		}

		
	}elseif($showDefault){
		return $defaultPicture;
	}else{
		return false;
	}
}

/**
 * Get profile picture html
 * @param	int 		$postId				WP_post id
 *
 * @return	string|false					The url or false if no valid page
*/
function getValidPageLink($postId){
	if(is_array($postId)){
		foreach($postId as $id){
			$url	= getValidPageLink($id);
			if($url){
				return $url;
			}
		}
	}

	if(!is_numeric($postId)){
		return false;
	}

	if(get_post_status($postId) != 'publish'){
		return false;
	}

	$link      = get_page_link($postId);

	//Only redirect if we are not currently on the page already
	if(str_contains(currentUrl(), $link)){
		return false;
	}

	return $link;
}

function removeDuplicateTags($matches){
	//If the opening tag is exactly like the next opening tag, remove the the duplicate
	if($matches[1] == $matches[4] && ($matches[3] == 'span' || $matches[3] == 'strong' || $matches[3] == 'b')){
		return '<'.$matches[1].'>'.$matches[2];
	}else{
		return $matches[0];
	}
}

function readTextFile($path){
	$ext 	= pathinfo($path, PATHINFO_EXTENSION);
		
	if($ext == 'docx'){
		$reader = 'Word2007';
	}elseif($ext == 'doc'){
		$reader = 'MsDoc';
	}elseif($ext == 'rtf'){
		$reader = 'rtf';
	}elseif($ext == 'txt'){
		$reader = 'plain';
	}else{
		$reader = 'Word2007';
	}
	
	if($reader == 'plain'){
		$file = fopen($path, "r");
		$contents =  fread($file,filesize($path));
		fclose($file);
		
		return str_replace("\n", '<br>', $contents);
	}else{
		//Load the filecontents
		$phpWord = \PhpOffice\PhpWord\IOFactory::createReader($reader)->load($path);

		//Convert it to html
		$htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
		
		$html 		= $htmlWriter->getWriterPart('Body')->write();

		// Replace paragraps with linebreaks
		$re 		= '/<p .*?>(.*?)<\/p>/s';
		$html 		= preg_replace($re, "$1<br>", $html);

		// Replace spans with bold
		$re 		= '~<span[^>]*?font-weight: bold;[^>]*>([^<]*)<\/span>~sm';
		$html 		= preg_replace($re, '<b>$1</b>', $html);

		// Remove remaining spans
		$re 		= '~<span[^>]*>([^<]*)<\/span>~sm';
		$html 		= preg_replace($re, '$1', $html);

		// Remove duplicate tags like </b><b>
		$re			= '/<\/([^>]*)>\s*<\1>/s';
		$html 		= preg_replace($re, '$2', $html);
		
		//Return the contents
		return $html;
	}
}

function isRestApiRequest() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        // Probably a CLI request
        return false;
    }

    $restPrefix         = trailingslashit( rest_get_url_prefix() );
    return str_contains( $_SERVER['REQUEST_URI'], $restPrefix );
}

/**
 * Clears the output queue
 */
function clearOutput($write=false){
	while(true){
        //ob_get_clean only returns false when there is absolutely nothing anymore
        $result	= ob_get_clean();
        if($result === false){
            break;
        }
		if($write){
			echo $result;
		}
    }
}

/**
 * Find users in a string
 *
 * @param	string	$string			The string to search in
 * @param	bool	$skipHyperlinks	Wheter we should skip users contained in a hyperlink
 *
 * @return	array					Array of with found user ids as index and an array of the text found and its start location as value
 */
function findUsers(&$string, $skipHyperlinks){
	$foundUsers	= [];

	// get all useraccounts
	$users 		= getUserAccounts(false, false);

	// Clean up the string
	$string		= str_replace(['&amp;', chr(194), chr(160)], ['&', ' '], $string);

	$displayNames	= [];
	$coupleNames	= [];

	foreach($users as $user){
		// store displayname
		$displayNames[$user->ID]	= strtolower($user->display_name);
		$partner					= hasPartner($user->ID, true);

		// store firstname and partner firstname in case last name is omitted
		if($partner){
			$coupleNames[$user->ID]	= strtolower("$user->first_name & $partner->first_name");
		}
	}

	//Find names in content
	$oneWord	= "[A-Z][^\$%\^*£=~@\d\s:\[\],\"\.\)\(<]+\s?+";		// a word starting with a capital, ending with a space
	$singleRe	= "(?:$oneWord){2,}";								// two or more words starting with a capital after each other 
	$coupleRe	= "(?:($oneWord)+(?:&|and).(($oneWord)+))";			// one or more words starting with a capital letter followed by 'and' or '&' followed by one or more words starting with a capital letter 
	$familyRe	= "$oneWord\s(?:F|f)amily";
	if($skipHyperlinks){
		$skipHyperlinks	= "<a [^>]+?>.*?<\/a>(*SKIP)(*FAIL)|";
	}else{
		$skipHyperlinks	= "";
	}

	// check if prayer contains a single name or a couples name
	// We use look ahead (?=)to allow for overlap
	$re		= "/(*UTF8)$skipHyperlinks(?=($coupleRe|$singleRe|$familyRe))/m";	
	preg_match_all($re, $string, $matches, PREG_SET_ORDER+PREG_OFFSET_CAPTURE, 0);

	foreach($matches as $index => $match){
		if(!isset($match[1])){
			continue;
		}

		$userId		= false;
		$userId2	= false;

		// full catch
		$name	= strtolower(trim($match[1][0]));

		// no family reference found
		if(empty($match[3])){
			// check if single user
			$userId	= array_search($name, $displayNames);
		}else{
			// find the second person
			$name	= trim($match[3][0]);

			$userId	= array_search(strtolower($name), $displayNames);

			// second person not found try the first one
			if(!$userId){
				// firstname and last name
				$name	= trim($match[2][0].$match[4][0]);

				$userId	= array_search(strtolower($name), $displayNames);

				// still not found, maybe two different lastnames, split on the & and use the first part
				if(!$userId){
					$names		= explode('&', str_replace(' and ', ' & ', $match[1][0]));
					
					$userId2	= array_search(trim(strtolower($names[0])), $displayNames);
					$userId		= array_search(trim(strtolower($names[1])), $displayNames);

					if($userId2){
						// Add first name to the array
						$foundUsers[$userId2]	= [trim($names[0]), $match[1][1]];
					}

					if($userId){
						// add second name to the array
			
						// Calculate the start position
						$pos					= strpos($match[1][0], trim($match[3][0]));

						// Add to the array
						$foundUsers[$userId]	= [$name, $match[1][1] + $pos];
					}

					if($userId || $userId2){
						continue;
					}
				}
			}
		}	

		// Still not found lets try copules first names without last name
		if(!$userId && !$userId2){
			$name	= trim($match[1][0]);

			// check if mentioned as a couple without lastname
			$userId	= array_search(str_replace(' and ', ' & ', strtolower($name)), $coupleNames);

			if(!$userId){
				continue;
			}
		}

		if(isset($foundUsers[$userId])){
			continue;
		}

		// if we are dealing with a couple
		if(isset($match[2])){
			$firstName	= trim($match[2][0]);

			// Make sure this is a couple and not just two people
			$exploded	= explode($firstName, $match[1][0]);

			// check if this is an user as well
			$extraName	= trim($exploded[0].$firstName);

			// Get the last name
			if(!empty($match[4][0])){
				$lastName	= ' '.trim($match[4][0]);
			}else{
				$lastName	= '';
			}

			$userId2	= array_search(strtolower($extraName.$lastName), $displayNames);

			if($userId2){
				// Add first name to the array
				$foundUsers[$userId2]	= [$extraName, $match[1][1]];

				// add second name to the array
				
				// Calculate the start position
				$pos					= strpos($match[1][0], trim($match[3][0]));

				// Add to the array
				$foundUsers[$userId]	= [$name, $match[1][1] + $pos];
			}else{
				// Make sure we only return the name, split on the first name, take the second portion and prepend the name
				$str					= $firstName . explode($firstName, $match[1][0])[1];

				// Calculate the updated string start position
				$pos					= strlen($match[1][0])	- strlen($str);

				// Add to the array
				$foundUsers[$userId]	= [$str, $match[1][1] + $pos];
			}

			
		}else{
			$foundUsers[$userId]	= $match[1];
		}
	}

	return $foundUsers;
}

/**
 * Replace a users name with a link to the user page
 *
 * @param	string	$string		The string to scan for users
 *
 * @return	string				The string with userpagelinks
 */
function userPageLinks($string){
	$userIds	= findUsers($string, true);

	$offset	= 0;
	foreach($userIds as $userId => $match){
		$privacyPreference = get_user_meta( $userId, 'privacy_preference', true );

		//only replace the name with a link if privacy allows
		if(!empty($privacyPreference['hide_name'])){
			continue;
		}

		//Replace the name with a hyperlink
		$url	= maybeGetUserPageUrl($userId);
		if(!$url){
			continue;
		}

		$name	= trim($match[0]);
		$link	= "<a href=\"$url\">$name</a>";

		$string	= substr_replace($string, $link, $match[1] + $offset, strlen($name));

		// $match comes with the original offset, we need to keep track of the amount of added chars
		$offset	+= strlen($link) - strlen($name);
	}

	return $string;
}

/**
 * Removes any unneeded slashes
 *
 * @param	string	$content	The string to deslash
 *
 * @return	string				The cleaned string
 */
function deslash( $content ) {
	if(is_array($content)){
		return $content;
	}
	
	$content = preg_replace( "/\\\+'/", "'", $content );
	$content = preg_replace( '/\\\+"/', '"', $content );
	$content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );

	return $content;
}

/**
 * Find all depency urls of a given js handle
 *
 * @param	array	$scripts	the current urls array
 * @param	string	$handle			the handle of the js to find all urls for
 *
 * @return	array					array containing all urls to the js files
 */
function getJsDependicies(&$scripts, $handle, $extras = []){
    global $wp_scripts;

	$url	= $wp_scripts->registered[$handle]->src;
	if(!$url){
		return $extras;
	}

	if(!str_contains($url, '//')){
		$url	= $wp_scripts->base_url.$url;
	}
	$scripts[$handle]	= [
		'src'	=> $url,
		'deps'	=> []
	];


	$extra	= $wp_scripts->registered[$handle]->extra;
	if(!empty($extra)){
		$extras[]	= $extra;
	}

    foreach($wp_scripts->registered[$handle]->deps as $dep){
        $extras	= getJsDependicies($scripts[$handle]['deps'], $dep, $extras );
    }

    return $extras;
}

/**
 * update url in posts
 *
 * @param	string		$oldPath		The path to be replaced
 * @param	string		$newPath		The path to replace with
 */
function urlUpdate($oldPath, $newPath){
	//replace any url with new urls for this attachment
	$oldUrl    = pathToUrl($oldPath);
	$newUrl    = pathToUrl($newPath);

	// Search for any post with the old url
	$query = new \WP_Query( array( 's' => basename($oldUrl) ) );

	foreach($query->posts as $post){
		$updated	= false;
		//if old url is found in the content of this post
		if(str_contains($post->post_content, $oldUrl)){
			//replace with new url
			$post->post_content = str_replace($oldUrl, $newUrl, $post->post_content);

			$updated	= true;
		}

		if($updated){
			$args = array(
				'ID'           => $post->ID,
				'post_content' => $post->post_content,
			);

			// Update the post into the database
			wp_update_post( $args, false, false );
		}
	}
}

/**
 * Search every table and column in the db
 *
 * @param	string	$search				the searchstring
 * @param	array	$excludedTables		the tables to exclude from the search
 * @param	array	$excludedColumns	the columns to exclude from the search
 *
 * @return	array						An array of results
 */
function searchAllDB($search, $excludedTables=[], $excludedColumns=[]){
    global $wpdb;

    $out 	= [];

    $sql	= "show tables";
    $tables	= $wpdb->get_results($sql, ARRAY_N);
    if(!empty($tables)){
        foreach($tables as $table){
			if(in_array($table[0], $excludedTables)){
				continue;
			}

            $sqlSearch 			= "select * from `".$table[0]."` where ";
            $sqlSearchFields 	= [];
            $sql2 				= "SHOW COLUMNS FROM `".$table[0]."`";
            $columns 			= $wpdb->get_results($sql2);
            if(!empty($columns)){
                foreach($columns as $column){
					if(in_array($column->Field, $excludedColumns)){
						continue;
					}

                    $sqlSearchFields[] = "`".$column->Field."` like('%".$wpdb->_real_escape($search)."%')";
                }
            }
            $sqlSearch 		.= implode(" OR ", $sqlSearchFields);
            $results		= $wpdb->get_results($sqlSearch);
			if(!empty($results)){
				foreach($results as $result){
					foreach($result as $column=>$value){
						if(in_array($column, $excludedColumns)){
							continue;
						}
						if(str_contains($value, $search)){
							$out[] 	= [
								'table'		=> $table[0],
								'column'	=> $column,
								'value'		=> $value,
							];
						}
					}
				}
			}
        }
    }

	foreach($out as $index=>&$result){
		$match	= false;
		$value	= maybe_unserialize($result['value']);
		if(is_array($value)){
			$found	= arraySearchRecursive($search, $result);
			if(!empty($found)){
				$match	= true;
				$result	= $found;
			}
		}elseif($value == $search){
			$match	= true;
		}

		if(!$match){
			unset($out[$index]);
		}
	}

    return array_values($out);
}

//Creates subimages
//Add action
add_action('init', __NAMESPACE__.'\processImagesAction');
function processImagesAction() {
	add_action( 'process_images_action', __NAMESPACE__.'\processImages' );
}

function loaderImage($size=50, $text='', $hidden=false){
	if(!is_numeric($size)){
		return false;
	}

	$factor		= $size / 100;
	$dotSize	= $factor * 16;

	ob_start();
	?>
	<div class='loader-wrapper <?php if($hidden){echo 'hidden';}?>' style='height: <?php echo $factor * 100 + 10; ?>px;'>
		<div class="loader" style='width: <?php echo $factor * 100; ?>px; height: <?php echo $factor * 100; ?>px; '>
			<?php
			for ($i = 0; $i < 8; $i++){
				switch ($i) {
					case 0:
						$top	= 0;
						$left	= $factor * 44;
						$delay	= 0;
						break;
					case 1:
						$top	= $factor * 15;
						$left	= $factor * 78;
						$delay	= 0.15;
						break;
					case 2:
						$top	= $factor * 44;
						$left	= $factor * 88;
						$delay	= 0.3;
						break;
					case 3:
						$top	= $factor * 75;
						$left	= $factor * 75;
						$delay	= 0.45;
						break;
					case 4:
						$top	= $factor * 88;
						$left	= $factor * 44;
						$delay	= 0.6;
						break;
					case 5:
						$top	= $factor * 75;
						$left	= $factor * 15;
						$delay	= 0.75;
						break;
					case 6:
						$top	= $factor * 44;
						$left	= 0;
						$delay	= 0.9;
						break;
					case 7:
						$top	= $factor * 15;
						$left	= $factor * 15;
						$delay	= 1.05;
						break;
					default:
						$top	= $factor * 15;
						$left	= $factor * 15;
						$delay	= 1.05;
						break;
				}

				echo "<div class='dot' style='width: {$dotSize}px; height: {$dotSize}px; top: {$top}px; left: {$left}px; animation-delay: {$delay}s;'></div>";
			}
			?>
		</div>

		<span class='loader-text'><?php echo $text;?></span>
	</div>

	<?php

	return ob_get_clean();
}
define(__NAMESPACE__ .'\LOADERIMAGE', loaderImage());