<?php
namespace SIM;

//Update user meta of a user and all of its relatives
function update_family_meta($user_id, $metakey, $value){
	if($value == 'delete'){
		delete_user_meta($user_id, $metakey);
	}else{
		update_user_meta( $user_id, $metakey, $value);
	}
		
	//Update the meta key for all family members as well
	$family = get_user_meta($user_id,"family",true);
	if (is_array($family) and count($family)>0){
		if (isset($family["children"])){
			$family = array_merge($family["children"],$family);
			unset($family["children"]);
		}
		foreach($family as $relative){
			if($value == 'delete'){
				delete_user_meta($relative, $metakey);
			}else{
				//Update the marker for the relative as well
				update_user_meta($relative,$metakey,$value);
			}
		}
	}
}

//Create a dropdown with all users
function user_select($text,$only_adults=false,$families=false, $class='',$id='user_selection',$args=[],$user_id='',$exclude_ids=[]){
	wp_enqueue_script('sim_other_script');
	$html = "";

	if(!is_numeric($user_id))	$user_id = $_GET["userid"];
	
	//Get the id and the displayname of all users
	$users = get_user_accounts($families,$only_adults,true,[],$args);
	$exists_array = array();

	$exclude_ids[]	= 1;
	
	//Loop over all users to find duplicate displaynames
	foreach($users as $key=>$user){
		//remove any user who should not be in the dropdown
		if(in_array($user->ID, $exclude_ids)){
			unset($users[$key]);
			continue;
		}

		//Get the full name
		$full_name = strtolower("$user->first_name $user->last_name");
		
		//If the full name is already found
		if (isset($exists_array[$full_name])){
			//Change current users last name
			$user->last_name = "$user->last_name ($user->user_email)";
			
			//Change previous found users last name
			$user = $users[$exists_array[$full_name]];
			
			//But only if not already done
			if(strpos($user->last_name, $user->user_email) === false ){
				$user->last_name = "$user->last_name ($user->user_email)";
			}
		}else{
			//User has a so far unique displayname, add to array
			$exists_array[$full_name] = $key;
		}
	}
	
	$html .= "<div>";
	if($text != ''){
		$html .= "<h4>$text</h4>";
	}
	$html .= "<select name='$id' class='$class user_selection'>";
		foreach($users as $key=>$user){
			if ($user_id == $user->ID){
				//Make this user the selected user
				$selected='selected="selected"';
			}else{
				$selected="";
			}
 			$html .= "<option value='$user->ID' $selected>$user->first_name $user->last_name</option>";
		}
	$html .= '</select></div>';
	
	return $html;
}

function current_url($exclude_scheme=false, $remove_get=false){
	if(wp_doing_ajax()){
		$url	 = trim(explode('?',$_SERVER['HTTP_REFERER'])[0],"/");
	}else{
		$url	 = '';
		if($exclude_scheme == false)	$url .=	$_SERVER['REQUEST_SCHEME']."://";
		$url	.=	$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}
	return $url;
}

function url_to_path($url){
	$site_url	= str_replace('https://','',get_site_url());
	$url		= str_replace('https://','',$url);
	
	//in case of http
	$site_url	= str_replace('http://','',$site_url);
	$url		= str_replace('http://','',$url);
	
	//print_array($site_url);
	$path = str_replace(trailingslashit($site_url),ABSPATH,$url);
	return $path;
}

function path_to_url($path){
	if(is_string($path)){
		$base	= str_replace('\\', '/', ABSPATH);
		$path	= str_replace('\\', '/', $path);
		$url	= str_replace($base, get_site_url().'/', $path);
	}else{
		$url	= $path;
	}
	
	return $url;
}

function print_array($message,$display=false){
	$bt = debug_backtrace();
	$caller = array_shift($bt);
	//always write to log
	error_log("Called from file {$caller['file']} line {$caller['line']}");
	file_put_contents(__DIR__.'/simlog.log',"Called from file {$caller['file']} line {$caller['line']}\n",FILE_APPEND);

	if(is_array($message) or is_object($message)){
		error_log(print_r($message,true));
		file_put_contents(__DIR__.'/simlog.log',print_r($message,true),FILE_APPEND);
	}else{
		error_log(date('d-m-Y H:i',time()).' - '.$message);
		file_put_contents(__DIR__.'/simlog.log',date('d-m-Y H:i',time())." - $message\n",FILE_APPEND);
	}
	
	if($display){
		echo "<pre>";
		echo "Called from file {$caller['file']} line {$caller['line']}<br><br>";
		print_r($message);
		echo "</pre>";
	}
}

function page_select($select_id,$page_id=null,$class=""){	
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_title',
			'order' 		=> 'asc',
			'post_status' 	=> 'publish',
			'post_type'     => ['page', 'location'],
			'posts_per_page'=> -1,
		)
	);
	
	$html = "";
	$html .= "<select name='$select_id' id='$select_id' class='selectpage $class'>
				<option value=''>---</option>";
	
	foreach ( $pages as $page ) {
		if ($page_id == $page->ID){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$option = '<option value="' . $page->ID . '" '.$selected.'>';
		$option .= $page->post_title;
		$option .= '</option>';
		$html .= $option;
	}
	
	$html .= "</select>";
	return $html;
}

//Function to check if a post has child posts
function has_children($post_id) {  
	$pages = get_pages('child_of=' . $post_id);
	
	if (count($pages) > 0):
	  return $pages;
	else:
	  return false;
	endif;
}

//family flat array
function family_flat_array($user_id){
	
	$family = (array)get_user_meta( $user_id, 'family', true );
	clean_up_nested_array($family);

	//make the family array flat
	if (isset($family["children"])){
		$family = array_merge($family["children"],$family);
		unset($family["children"]);
	}
	
	return $family;
}

//Check if user has partner
function has_partner($user_id) {
	$family = get_user_meta($user_id, "family", true);
	if(is_array($family)){
		if (isset($family['partner']) and is_numeric($family['partner'])){
			return $family['partner'];
		}else{
			return false;
		}
	}else{
		return false;
	}
}

//Get users parents
function get_parents($user_id){
	$family 	= get_user_meta( $user_id, 'family', true );
	$parents 	= [];
	foreach (["father","mother"] as $parent) {
		if (isset($family[$parent])) {
			$parent_userdata = get_userdata($family[$parent]);
			if($parent_userdata != null){
				$parents[] = $parent_userdata;
			}
		}
	}
	return $parents;
}

//Function to check if a certain user is a child
function is_child($user_id) {
	$family = get_user_meta($user_id, "family", true);
	if(is_array($family)){
		if(isset($family["father"]) or isset($family["mother"])){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}

function get_age($user_id){
	$birthday = get_user_meta( $user_id, 'birthday', true );
	if($birthday != ""){
		$birthDate = explode("-", $birthday);
		if (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[2], $birthDate[0]))) > date("md")){
			$age = (date("Y") - $birthDate[0]) - 1;
		}else{
			$age = (date("Y") - $birthDate[0]);
		}
	}else{
		$age = "";
	}
	
	return $age;
}

function number_to_words($number) {
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
		10 => 'twentieth'
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
        return $negative . number_to_words(abs($number));
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
                $string .= $conjunction . number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= number_to_words($remainder);
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

function get_age_in_words($date){
	$start_year = explode('-',$date)[0];
	//get the difference with the current year
	$age = date('Y')-$start_year;

	return number_to_words($age);
}

function get_arriving_users(){
	$date   = new \DateTime(); 
	$arrival_users = get_users(array(
		'meta_key'     => 'arrival_date',
		'meta_value'   => $date->format('Y-m-d'),
		'meta_compare' => '=',
	));
	
	return $arrival_users;
}

function get_user_accounts($return_family=false,$adults=true,$local_nigerians=false,$fields=[],$extra_args=[]){
	$do_not_process 		= [];
	$cleaned_user_array 	= [];
	
	$arg = array(
		'orderby'	=> 'meta_value',
		'meta_key'	=> 'last_name'
	);

	if($local_nigerians == false){
		$arg['meta_query'] = array(
			array(
				'key' => 'local_nigerian',
				'compare' => 'NOT EXISTS'
			),
		);
	}
	
	if(is_array($fields) and count($fields)>0){
		$arg['fields'] = $fields;
	}
	
	$arg = array_merge_recursive($arg,$extra_args);
	
	$users  = get_users($arg);
	
	//Loop over the users
	foreach($users as $user){
		//If we should only return families
		if($return_family == true){
			$family = get_user_meta( $user->ID, 'family', true );
			if ($family == ""){
				$family = [];
			}

			//Current user is a child, exclude it
			if (is_child($user->ID)){
				$do_not_process[] = $user->ID;
			//Check if this adult is not already in the list
			}elseif(!in_array($user->ID, $do_not_process)){
				if (isset($family["partner"])){
					$do_not_process[] = $family["partner"];
					//Change the display name
					$user->display_name = $user->last_name." family";
				}
			}
		//Only returning adults, but this is a child
		}elseif($adults == true and is_child($user->ID)){
			$do_not_process[] = $user->ID;
		}
	}
	
	//Loop over the users again
	foreach($users as $user){
		//Add the user to the cleaned array if not in the donotprocess array
		if(!in_array($user->ID,$do_not_process)){
			$cleaned_user_array[] = $user;
		}
	}
	
	return $cleaned_user_array;
}

//Updated nested array based on array of keys
function add_to_nested_array($keys, &$array=array(), $value=null) {
	//$temp point to the same content as $array
	$temp =& $array;
	if(!is_array($temp)) $temp = [];
	
	//loop over all the keys
	foreach($keys as $key) {
		//$temp points now to $array[$key]
		$temp =& $temp[$key];
	}
	
	//We update $temp resulting in updating $array[X][y][z] as well
	$temp[] = $value;
}

function remove_from_nested_array(&$array, $array_keys){
	$last 		= array_key_last($array_keys);
	$current 	=& $array;
    foreach($array_keys as $index=>$key){
		if($index == $last){
			unset($current[$key]);
		}else{
        	$current =& $current[$key];
		}
    }

    return $current;
}

//clean up an array
function clean_up_nested_array(&$array, $del_empty_arrays=false){
	foreach ($array as $key => $value){
        if(is_array($value)){
            clean_up_nested_array($value);
			if(empty($value) and $del_empty_arrays){
				unset($array[$key]);
			}else{
				$array[$key] = $value;
			}
		}elseif(empty(trim($value))){
            unset($array[$key]);
		}
    }
}

//get array value
function get_meta_array_value($user_id, $metakey, $values=null){
	if(empty($metakey)) return $values;
	
	if($values === null and !empty($metakey)){
		//get the basemetakey in case of an indexed one
		if(preg_match('/(.*?)\[/', $metakey, $match)){
			$base_meta_key	= $match[1];
		}else{
			//just use the whole, it is not indexed
			$base_meta_key	= $metakey;
		}
		$values	= (array)get_user_meta($user_id,$base_meta_key,true);
	}
	//Return the value of the variable whos name is in the keystringvariable
	preg_match_all('/\[(.*?)\]/', $metakey, $matches);
	if(is_array($matches[1])){
		$value	= $values;
		foreach($matches[1] as $match){
			if(!is_array($value)) break;
			$value = $value[$match];
		}
	}

	return $value;
}

//Verify nonce
function verify_nonce($nonce_string){
	if(!isset($_POST[$nonce_string])){
		if(wp_doing_ajax()){
			wp_die("No nonce found! Try again after refreshing the page",500);
		}else{
			return false;
		}
	}elseif(!wp_verify_nonce($_POST[$nonce_string], $nonce_string)){
		if(wp_doing_ajax()){
			wp_die("Invalid nonce! Try again after refreshing the page",500);
		}else{
			return false;
		}
	}

	return true;
}

function add_save_button($element_id, $button_text, $extraclass = ''){
	$html = "<div class='submit_wrapper'>";
		$html .= "<button type='button' class='button form_submit $extraclass' name='$element_id'>$button_text</button>";
		$html .= "<img class='loadergif hidden' src='".LOADERIMAGEURL."'>";
	$html .= "</div>";
	
	return $html;
}
	
function add_to_library($target_file){
	try{
		//print_array("Adding $target_file to library");
		
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}
		 
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $target_file ), null );
		 
		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();
		 
		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           =>	path_to_url($target_file ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $target_file ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		 
		// Insert the attachment.
		$post_id = wp_insert_attachment( $attachment, $target_file, $parent_post_id );
		
		//Store a post meta to create subsizes later as it can be very slow
		update_post_meta($post_id, 'generate_attachment_metadata',true);
		
		return $post_id;
	}catch(\GuzzleHttp\Exception\ClientException $e){
		$result = json_decode($e->getResponse()->getBody()->getContents());
		$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
		print_array($error_result);
		if(isset($post_id)) return $post_id;
	}catch(\Exception $e) {
		$error_result = $e->getMessage();
		print_array($error_result);
		if(isset($post_id)) return $post_id;
	}
}
 
function get_module_option($module_name, $option){
	global $Modules;

	if(!empty($Modules[$module_name][$option])){
		return $Modules[$module_name][$option];
	}else{
		return false;
	}
}

function try_send_signal($message, $recipient, $post_id=""){
	if (function_exists('SIM\send_signal_message')) {
		SIGNAL\send_signal_message($message, $recipient, $post_id);
	}
}

function picture_selector($key, $name, $settings){
	wp_enqueue_media();
	wp_enqueue_script('sim_picture_selector_script', INCLUDESURL.'/js/select_picture.js', array(), '7.0.0',true);
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
			<img class='image-preview' src='<?php echo $src;?>'>
		</div>
		<input type="button" class="button select_image_button" value="<?php echo $text;?> picture for <?php echo strtolower($name);?>" />
		<input type='hidden' class="image_attachment_id" name='picture_ids[<?php echo $key;?>]' value='<?php echo $id;?>'>
	</div>
	<?php
}