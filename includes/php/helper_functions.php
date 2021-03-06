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
 * @param 	string		$title	 		The title to display above the select
 * @param	bool		$onlyAdults	 	Whether children should be excluded. Default false
 * @param	bool		$families  		Whether we should group families in one entry default false
 * @param	string		$class			Any extra class to be added to the dropdown default empty
 * @param	string		$id				The name or id of the dropdown, default 'user-selection'    
 * @param	array		$args    		Extra query arg to get the users  
 * @param	int			$userId			The current selected user id
 * @param	array		$excludeIds		An array of user id's to be excluded 
 * @param	string		$type			Html input type Either select or list
 * 
 * @return	string						The html
*/
function userSelect($title, $onlyAdults=false, $families=false, $class='', $id='user_selection', $args=[], $userId='', $excludeIds=[1], $type='select'){
	wp_enqueue_script('sim_user_select_script');
	$html = "";

	if(!empty($_GET["userid"]) && !is_numeric($userId)){
		$userId = $_GET["userid"];
	}
	
	//Get the id and the displayname of all users
	$users 			= getUserAccounts($families, $onlyAdults, [], $args);
	$existsArray 	= array();
	
	//Loop over all users to find duplicate displaynames
	foreach($users as $key=>$user){
		//remove any user who should not be in the dropdown
		if(in_array($user->ID, $excludeIds)){
			unset($users[$key]);
			continue;
		}

		//Get the full name
		$fullName = strtolower("$user->first_name $user->last_name");
		
		//If the full name is already found
		if (isset($existsArray[$fullName])){
			//Change current users last name
			$user->last_name = "$user->last_name ($user->user_email)";
			
			//Change previous found users last name
			$user = $users[$existsArray[$fullName]];
			
			//But only if not already done
			if(strpos($user->last_name, $user->user_email) === false ){
				$user->last_name = "$user->last_name ($user->user_email)";
			}
		}else{
			//User has a so far unique displayname, add to array
			$existsArray[$fullName] = $key;
		}
	}
	
	$html .= "<div>";
	if(!empty($title)){
		$html .= "<h4>$title</h4>";
	}

	if($type == 'select'){
		$html .= "<select name='$id' class='$class user_selection'>";
			foreach($users as $key=>$user){
				if(empty($user->first_name) || empty($user->last_name)){
					$name	= $user->display_name;
				}else{
					$name	= "$user->first_name $user->last_name";
				}

				if ($userId == $user->ID){
					//Make this user the selected user
					$selected='selected="selected"';
				}else{
					$selected="";
				}
				$html .= "<option value='$user->ID' $selected>$name</option>";
			}
		$html .= '</select>';
	}elseif($type == 'list'){
		$value	= '';
		$datalist = "<datalist id='$id' class='$class user_selection'>";
			foreach($users as $key=>$user){
				if(empty($user->first_name) || empty($user->last_name)){
					$name	= $user->display_name;
				}else{
					$name	= "$user->first_name $user->last_name";
				}
				
				if ($userId == $user->ID){
					//Make this user the selected user
					$value	= $user->display_name;
				}
				$datalist .= "<option value='$name' data-userid='$user->ID'>";
			}
		$datalist .= '</datalist>';

		$html	.= "<input type='text' name='$id' list='$id' value='$value'>";
		$html	.= $datalist;
	}
	
	$html	.= '</div>';
	
	return $html;
}

/**
 * Returns the current url
 * 
 * @return	string						The url
*/
function currentUrl(){
	if(defined('REST_REQUEST')){
		$url	 = trim(explode('?',$_SERVER['HTTP_REFERER'])[0],"/");
	}else{
		$url	 = '';
		$url 	.=	$_SERVER['REQUEST_SCHEME']."://";
		$url	.=	$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}
	return $url;
}

/**
 * Transforms an url to a path
 * @param 	string		$url	 		The url to be transformed
 * 
 * @return	string						The path
*/
function urlToPath($url){
	$siteUrl	= str_replace(['https://', 'http://'], '', SITEURL);
	$url		= str_replace(['https://', 'http://'], '', $url);
	
	return str_replace(trailingslashit($siteUrl), ABSPATH, $url);
}

/**
 * Transforms a path to an url
 * @param 	string		$path	 		The path to be transformed
 * 
 * @return	string						The url
*/
function pathToUrl($path){
	if(is_string($path)){
		$base	= str_replace('\\', '/', ABSPATH);
		$path	= str_replace('\\', '/', $path);
		$url	= str_replace($base, SITEURL.'/', $path);
	}else{
		$url	= $path;
	}
	
	return $url;
}

/**
 * Prints something to the log file and optional to the screen
 * @param 	string		$message	 	The message to be printed
 * @param	bool		$display		Whether to print the message to the screen or not
*/
function printArray($message, $display=false){
	$bt		= debug_backtrace();
	$caller = array_shift($bt);
	//always write to log
	error_log("Called from file {$caller['file']} line {$caller['line']}");
	//file_put_contents(__DIR__.'/simlog.log',"Called from file {$caller['file']} line {$caller['line']}\n",FILE_APPEND);

	if(is_array($message) || is_object($message)){
		error_log(print_r($message,true));
		//file_put_contents(__DIR__.'/simlog.log',print_r($message,true),FILE_APPEND);
	}else{
		error_log(date('d-m-Y H:i',time()).' - '.$message);
		//file_put_contents(__DIR__.'/simlog.log',date('d-m-Y H:i',time())." - $message\n",FILE_APPEND);
	}
	
	if($display){
		echo "<pre>";
		echo "Called from file {$caller['file']} line {$caller['line']}<br><br>";
		print_r($message);
		echo "</pre>";
	}
}

function printHtml($html){
	$tabs	= 0;

	$html		= explode('>', $html);
	$newHtml	= '';

	foreach($html as $el){
		if(empty($el)){
			continue;
		}

		$lines= explode('</', $el);

		if(!empty($lines[0])){
			$newHtml	.= "\n";
			
			for ($x = 0; $x <= $tabs; $x++) {
				$newHtml	.= "\t";
			}

			$newHtml	.= $lines[0];
		}

		if(substr($el, 0, 1) == '<' && substr($el, 0, 2) != '</' && substr($el, 0, 6) != '<input' && $el != '<br'){
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
function pageSelect($selectId, $pageId=null, $class="", $postTypes=['page', 'location']){	
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_title',
			'order' 		=> 'asc',
			'post_status' 	=> 'publish',
			'post_type'     => $postTypes,
			'posts_per_page'=> -1,
		)
	);

	$taxonomies = get_taxonomies(
		array(
		'public'   => true,
		'_builtin' => false	 
		)
	);

	$terms		= get_terms(['hide_empty'=>false]);

	$options	= [];
	foreach ( $pages as $page ) {
		$options[$page->ID]	= $page->post_title;
	}
	foreach ( $taxonomies as $taxonomy ) {
		$options[$taxonomy]	= ucfirst($taxonomy);
	}
	foreach ( $terms as $term ) {
		$options[$term->taxonomy.'/'.$term->slug]	= $term->name;
	}

	asort($options);

	$html = "<select name='$selectId' id='$selectId' class='selectpage $class'>";
		$html .= "<option value=''>---</option>";
	
		foreach ( $options as $id=>$name ) {
			if ($pageId == $id){
				$selected='selected=selected';
			}else{
				$selected="";
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
 * Gets the children array and add it to the main level of the array
 * @param 	int		$userId	 	WP User_ID
 * 
 * @return	array				All family members in one array
*/
function familyFlatArray($userId){
	$family = (array)get_user_meta( $userId, 'family', true );
	cleanUpNestedArray($family);

	//make the family array flat
	if (isset($family["children"])){
		$family = array_merge($family["children"],$family);
		unset($family["children"]);
	}
	
	return $family;
}

/**
 * Check if user has partner
 * @param 	int		$userId	 	WP User_ID
 * 
 * @return	int|false			The partner user id, or false if no partner
*/
function hasPartner($userId) {
	$family = get_user_meta($userId, "family", true);
	if(is_array($family)){
		if (isset($family['partner']) && is_numeric($family['partner'])){
			return $family['partner'];
		}else{
			return false;
		}
	}else{
		return false;
	}
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
 * Get an users age
 * @param 	int		$userId	 	WP User_ID
 * 
 * @return	int					Age in years
*/
function getAge($userId){
	$birthday = get_user_meta( $userId, 'birthday', true );
	if(empty($birthday)){
		return false;
	}

	$birthDate = explode("-", $birthday);
	if (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")){
		$age = (date("Y") - $birthDate[0]) - 1;
	}else{
		$age = (date("Y") - $birthDate[0]);
	}
	
	return $age;
}

/**
 * Converts an number to words
 * @param 	string|int|float	the number to be converted
 * 
 * @return	string				the number in words
*/
function numberToWords($number) {
    $hyphen = '-';
    $conjunction = ' and ';
    $separator = ', ';
    $negative = 'negative ';
    $decimal = ' Thai Baht And ';
	$first_dic	= [
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
		20 => 'twentieth'
	];
    $dictionary = array(
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

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $first_dic[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $first_dic[$units];
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
 * Create a dropdown with all users
 * @param	bool		$returnFamily  	Whether we should group families in one entry default false
 * @param	bool		$adults			Whether we should only get adults  
 * @param	array		$fields    		Extra fields to return
 * @param	array		$extraArgs		An array of extra query arguments
 * 
 * @return	array						An array of WP_Users
*/
function getUserAccounts($returnFamily=false, $adults=true, $fields=[], $extraArgs=[]){
	$doNotProcess 		= [];
	$cleanedUserArray 	= [];
	
	$arg = array(
		'orderby'	=> 'meta_value',
		'meta_key'	=> 'last_name'
	);
	
	if(is_array($fields) && count($fields)>0){
		$arg['fields'] = $fields;
	}
	
	$arg 	= array_merge_recursive($arg, $extraArgs);
	
	$users  = get_users($arg);
	
	//Loop over the users
	foreach($users as $user){
		//If we should only return families
		if($returnFamily){
			$family = get_user_meta( $user->ID, 'family', true );
			if ($family == ""){
				$family = [];
			}

			//Current user is a child, exclude it
			if (isChild($user->ID)){
				$doNotProcess[] = $user->ID;
			//Check if this adult is not already in the list
			}elseif(!in_array($user->ID, $doNotProcess)){
				if (isset($family["partner"])){
					$doNotProcess[] = $family["partner"];
					//Change the display name
					$user->display_name = $user->last_name." family";
				}
			}
		//Only returning adults, but this is a child
		}elseif($adults && isChild($user->ID)){
			$doNotProcess[] = $user->ID;
		}
	}
	
	//Loop over the users again
	foreach($users as $user){
		//Add the user to the cleaned array if not in the donotprocess array
		if(!in_array($user->ID, $doNotProcess)){
			$cleanedUserArray[] = $user;
		}
	}
	
	return $cleanedUserArray;
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
function cleanUpNestedArray(&$array, $delEmptyArrays=false){
	foreach ($array as $key => $value){
        if(is_array($value)){
            cleanUpNestedArray($value);
			if(empty($value) && $delEmptyArrays){
				unset($array[$key]);
			}else{
				$array[$key] = $value;
			}
		}elseif(empty(trim($value))){
            unset($array[$key]);
		}
    }
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

	//Return the value of the variable whos name is in the keystringvariable
	preg_match_all('/\[(.*?)\]/', $metaKey, $matches);
	if(is_array($matches[1])){
		$value	= $values;
		foreach($matches[1] as $match){
			if(!is_array($value)){
				break;
			}
			
			if(!isset($value[$match])){
				$match	= str_replace('_files', '', $match);
			}
			$value = $value[$match];
		}
	}

	return $value;
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
	$html = "<div class='submit_wrapper'>";
		$html .= "<button type='button' class='button form_submit $extraClass' name='$elementId'>$buttonText</button>";
		$html .= "<img class='loadergif hidden' src='".LOADERIMAGEURL."'>";
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
 * 
 * @return	string						the selector html
*/
function pictureSelector($key, $name, $settings){
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
	<div class='picture_selector_wrapper'>
		<div class='image-preview-wrapper <?php echo $hidden;?>'>
			<img class='image-preview' src='<?php echo $src;?>' alt=''>
		</div>
		<input type="button" class="button select_image_button" value="<?php echo $text;?> picture for <?php echo strtolower($name);?>" />
		<input type='hidden' class="image_attachment_id" name='picture_ids[<?php echo $key;?>]' value='<?php echo $id;?>'>
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
	if (preg_match("/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/",$date)) {
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
function addUserAccount($firstName, $lastName, $email, $approved = false, $validity = 'unlimited'){
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
		'user_pass'     => NULL
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
 * 
 * @return	string|false					The picture html or false if no picture
*/
function displayProfilePicture($userId, $size=[50,50], $showDefault = true, $famillyPicture=false){
	
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

	if(is_numeric($attachmentId)){
		$url = wp_get_attachment_image_url($attachmentId,'Full size');
		return "<a href='$url'><img width='{$size[0]}' height='{$size[1]}' src='$url' class='attachment-{$size[0]}x{$size[1]} size-{$size[0]}x{$size[1]}' loading='lazy'></a>";
	}elseif($showDefault){
		$url = plugins_url('pictures/usericon.png', __DIR__);
		return "<img width='{$size[0]}' height='{$size[1]}' src='$url' class='attachment-{$size[0]}x{$size[1]} size-{$size[0]}x{$size[1]}' loading='lazy'>";
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
	if(!is_numeric($postId)){
		return false;
	}

	if(get_post_status($postId) != 'publish'){
		return false;
	}

	$link      = get_page_link($postId);

	//Only redirect if we are not currently on the page already
	if(strpos(currentUrl(), $link) !== false){
		return false;
	}

	return $link;
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
		
		$html 		= preg_replace_callback(
			//get all tags which are followed by the same tag 
			//syntax: <(some tagname)>(some text)</some tagname)0 or more spaces<(use tagname as found before + some extra symbols)>
			'/<([^>]*)>([^<]*)<\/(\w+)>\s*<(\3[^>]*)>/m', 
			function($matches){
				//If the opening tag is exactly like the next opening tag, remove the the duplicate
				if($matches[1] == $matches[4] && ($matches[3] == 'span' || $matches[3] == 'strong' || $matches[3] == 'b')){
					return $matches[2];
				}else{
					return $matches[0];
				}
			}, 
			$html
		);
		
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
    return strpos( $_SERVER['REQUEST_URI'], $restPrefix ) !== false;
}

/**
 * Clears the output queue
 */
function clearOutput(){
	while(true){
        //ob_get_clean only returns false when there is absolutely nothing anymore
        $result	= ob_get_clean();
        if($result === false){
            break;
        }
    }
}

//Creates subimages
//Add action
add_action('init', function () {
	add_action( 'process_images_action', __NAMESPACE__.'\processImages' );
});