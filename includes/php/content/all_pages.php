<?php
namespace SIM;

//Remove the featured image from the page and post content
add_action( 'init', function() {
	remove_action( 'generate_after_header', 'generate_featured_page_header', 10 );
	remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
	
	//Remove archive title from archive pages
	remove_action( 'generate_archive_title', 'generate_archive_title' );
});

//Do not display page title
add_action( 'after_setup_theme', 'SIM\tu_remove_all_titles' );
function tu_remove_all_titles() {
	//on all page except the newspage
	add_filter( 'generate_show_title', function (){
		if ( is_home() or is_search() or is_category() or is_tax()) {
			//Show the title on the news page
			return true;
		}else{
			//Hide the title
			return false;
		}
	});
};

//Removes the 'Protected:' part from posts titles
add_filter( 'protected_title_format', function () {
	return __('%s');
});

//Add a title section below the menu
add_action('generate_after_header',function (){
	global $post;
	global $LoggedInHomePage;
	if($post){		
		$title = $post->post_title;
	}else{
		$title = '';
	}
		
	//If this page is the news page
	if ( is_home()) {
		$title = "News";
	//Or an archive page (category of news)
	}elseif(is_category() or is_tax()){
		$category = get_queried_object();
		$title = $category->name;
		if(is_tax('recipetype')){
			$title .= ' recipies';
		}elseif(is_tax('eventtype')){
			$title .= ' events';
		}elseif(is_tax('locationtype')){
			//nothing
		}else{
			$title .= " posts";
		}
	}
	
	//change title of all pages except the frontpage
	if($title != 'Home' and !is_page($LoggedInHomePage)){
		//Display featured image in title if it has one
		if ( has_post_thumbnail() and $title != "News" and !is_category() and !is_tax() and get_post_type() != 'recipe') {
			echo '<div id="page-title-image" style="background-image: url('.get_the_post_thumbnail_url().');"></div>';
		}
		//Add the title
		echo '<div id="page-title-div">';
		echo '<h2 id="page-title">'.$title.'</h2>';
		echo '</div>';
	}
});

//Remove the password protect of a page for logged in users
add_filter( 'post_password_required', 
	function( $returned, $post ){
		// Override it for logged in users:
		if( $returned && is_user_logged_in() )
			$returned = false;

		return $returned;
	}
	, 10, 2 
);

//Add content to pages when a user is logged in
//Show PDFs full screen
add_filter( 'the_content', function ( $content ) {
	if (is_user_logged_in()){
		$post_id 	= get_the_ID();
		$content=str_replace('<p>&nbsp;</p>','',$content);
		
		//If the string starts with 0 or more spaces, then a <p> followed by a hyperlink ending in .pdf then the download text ending an optional download button followed with 0 or more spaces.
		$pattern = '/^\s*<p><a href="(.*?\.pdf)">([^<]*<\/a>)(.*\.pdf">Download<\/a>)?<\/p>\s*$/i';
		
		//Execute the regex
		preg_match($pattern, $content,$matches);
		//If an url exists it means there is only a pdf on this page
		if(isset($matches[2])){
			/* IF PEOPLE HAVE TO READ IT, MARK AS READ */
			$audience	= get_post_meta($post_id,"audience",true);
			
			if(!empty($audience)){
				//Get current user id
				$user_id = get_current_user_id();
				
				//get current alread read pages
				$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
				
				//only add if not already there
				if(!in_array($post_id, $read_pages)){
					//add current page
					$read_pages[]	= $post_id;
			
					//update db
					update_user_meta( $user_id, 'read_pages', $read_pages);
				}
			}

			/* SHOW THE PDF */
			//Show the pdf fullscreen only if we are not a content manager
			if(!in_array('contentmanager',wp_get_current_user()->roles)){
				//Get the url to the pdf
				$pdf_url = $matches[1];
				
				//Convert to path
				$path = url_to_path($pdf_url);
				
				//Echo the pdf to screen
				ob_clean();
				ob_start();
				header("Content-type: application/pdf");
				header("Content-Disposition: inline; filename=".$matches[2]);
				@readfile($path);
				ob_end_flush(); 
			}
		}
		
		//missionary page
		$missionary_id = get_post_meta($post_id,'missionary_id',true);
		if(is_numeric($missionary_id)) $content .= missionary_description($missionary_id);
		
		//Print to screen if the button is clicked
		if( isset($_POST['print_as_pdf']))	create_page_pdf();
		
		//pdf button
		if(get_post_meta($post_id,'add_print_button',true) != ''){
			$content .= "<div class='print_as_pdf_div' style='float:right;'>";
				$content .= "<form method='post' id='print_as_pdf_form'>";
					$content .= "<button type='submit' class='button' name='print_as_pdf' id='print_as_pdf'>Print this page</button>";
				$content .= "</form>";
			$content .= "</div>";
		}
	}
	
	return $content;
});

//Default content for ministry pages
function ministry_description($post_id){
	$html = "";
	//Get the post id of the page the shortcode is on
	$ministry = get_the_title($post_id);
	$ministry_name = str_replace(" ","_",$ministry);
	$ministry = strtolower($ministry);
	$args = array(
		'post_parent' => $post_id, // The parent id.
		'post_type'   => 'page',
		'post_status' => 'publish',
		'order'          => 'ASC',
	);
	$child_pages = get_children( $args, ARRAY_A);
	$child_page_html = "";
	if ($child_pages  != false){
		$child_page_html .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($child_pages as $child_page){
			$child_page_html .= '<li><a href="'.$child_page['guid'].'">'.$child_page['post_title']."</a></li>";
		}
		$child_page_html .= "</ul>";
	}		
	
	if (is_user_logged_in()){
		//Loop over all users to see if they work here
		$users = get_users('orderby=display_name');
		
		$original_html = "<p><strong>People working at $ministry are:</strong><br><br>";
		$html = $original_html;
		foreach($users as $user){
			$user_ministries = (array)get_user_meta( $user->ID, "user_ministries", true);
		
			//If user works for this ministry, echo its name and position
			if (isset($user_ministries[$ministry_name])){
				$user_page_url		= get_missionary_page_url($user->ID);
				$privacy_preference = (array)get_user_meta( $user->ID, 'privacy_preference', true );
				
				if(!isset($privacy_preference['hide_ministry'])){
					if(!isset($privacy_preference['hide_profile_picture'])){
						$html .= display_profile_picture($user->ID);
						$style = "";
					}else{
						$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
					}
					
					$page_url = "<a class='missionary_link' href='$user_page_url'>$user->display_name</a>";
					
					$html .= "   <div $style>$page_url ({$user_ministries[$ministry_name]})</div>";
					$html .= '<br>';
				}					
			}
		}
		if($html == $original_html){
			//Check if page has children
			if ($child_pages == false){
				//No children add an message
				$html .= "No one dares to say they are working here!";
			}else{
				$html = "";
			}
		}
		$html .= '</p>';
		
		$latitude = get_post_meta($post_id,'geo_latitude',true);
		$longitude = get_post_meta($post_id,'geo_longitude',true);
		if ($latitude != "" and $longitude != ""){
			$html .= "<p><a class='button' onclick='getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
		}
	}
	if($child_page_html != ""){
		$html = $child_page_html."<br><br>".$html; 
	}
	return $html;	
}

//Display name and mission of missionary
function missionary_description($user_id){
	global $CompoundIconID;
	global $CompoundsPageID;
	$html = "";

	$userdata	= get_userdata($user_id);
	$user 		= wp_get_current_user();
	
	//get the family details
	$family = get_user_meta( $user_id, 'family', true );
	
	//Find compound or address
	$location = get_user_meta( $user_id, 'location', true );
	if(is_array($location)){
		if (empty($location["compound"])){
			if (empty($location["address"])){
				$address = "No clue, since no address is given";
			}else{
				$address = $location["address"];
			}
		}else{
			$url = "";
			$compounds = get_children( array('post_parent' => $CompoundsPageID,));
			foreach($compounds as $compound){
				if($compound->post_title == $location["compound"]){
					$url = $compound->guid;
					$address = '<a href="'.$url.'">'.$location["compound"].'</a>';
				}
			}
			//Compound is set, but no compound page found
			if ( $url == ""){
				$address = $location["compound"];
			}
		}
	}else{
		$address = "No clue, since no address is given";
	}
	
	//Add a useraccount edit button if the user has the usermanagement role
	if (in_array('usermanagement',$user->roles)){
		$edit_user_url = get_site_url().'/update-personal-info/?userid=';
		
		$html .= "<div class='flex edit_useraccounts'><a href='$edit_user_url$user_id' class='button sim'>Edit useraccount for ".$userdata->first_name."</a>";
		if(is_array($family) and isset($family['partner'])){
			$html .= "<a  href='$edit_user_url".$family['partner']."' class='button sim'>Edit useraccount for ".get_userdata($family['partner'])->first_name."</a>";
		}
		if(is_array($family) and isset($family['children'])){
			foreach($family['children'] as $child){
				$html .= "<a href='$edit_user_url$child' class='button sim'>Edit useraccount for ".get_userdata($child)->first_name."</a>";
			}
		}
		$html .= '</div>';
	}
	
	$privacy_preference = get_user_meta( $user_id, 'privacy_preference', true );
	if(!is_array($privacy_preference)) $privacy_preference = [];

	//Build the html
	//Missionary has a family
	if ($family != ""){
		$html .= "<h1>".$userdata->last_name." family</h1>";
		$html .= "<p>
					Lives in: ";
		$html .= $address.' State';
		$html .= "</p>";
		$sending_office = get_user_meta( $user_id, 'sending_office', true );
		if($sending_office != "")
			$html .= "<p>Sending office: $sending_office</p>";
			
		$arrivaldate 	= get_user_meta( $user_id, 'arrival_date', true );
		if($arrivaldate != "" and !isset($privacy_preference['hide_anniversary'])){
			$arrival_epoch	= strtotime($arrivaldate);
			$arrivaldate	= date('F Y',$arrival_epoch);
			if($arrival_epoch < time()){
				$html .= "<p>In Nigeria since $arrivaldate</p>";
			}else{
				$html .= "<p>Expected arrival in Nigeria: $arrivaldate</p>";
			}
		}
		
		//Partner data
		if (isset($family['partner']) and is_numeric($family['partner'])){
			$html .= show_user_info($family['partner']);
		}
		
		//User data
		$html .= show_user_info($user_id);
			
		//Children
		if (isset($family["children"])){
			$html .= "<p>";
			$html .= " They have the following children:<br>";
			foreach($family["children"] as $child){
				$childdata = get_userdata($child);
				$age = get_age($child);
				if($age != '') $age = "($age)";
				
				$html .= display_profile_picture($childdata->ID);
			$html .= "<span class='person_work'> {$childdata->first_name} $age</span><br>";
			}
			$html .= "</p>";
		}
	//Single
	}else{
		$userdata = get_userdata($user_id);
	
		if ($userdata != null){
			if(isset($privacy_preference['hide_name'])){ 
				$displayname = '';
			}else{
				$displayname = $userdata->data->display_name;
			}
			
			$html .= "<h1>";
			if(!isset($privacy_preference['hide_profile_picture'])){
				$html .= display_profile_picture($user_id);
			}
			$html .= "  ".$displayname."</h1>";
			$html .= "<br>";
			if(!isset($privacy_preference['hide_location'])){ 
				$html .= "<p>Lives on: $address</p>";
			}
			if(!isset($privacy_preference['hide_ministry'])){ 
				$html .= "<p>Works with: <br>".add_ministry_links($user_id)."</p>";
			}
			$sending_office = get_user_meta( $user_id, 'sending_office', true );
			if($sending_office != "")
				$html .= "<p>Sending office: $sending_office</p>";
				
			$arrivaldate = get_user_meta( $user_id, 'arrival_date', true );
			if($arrivaldate != "" and !isset($privacy_preference['hide_anniversary'])){
				$arrival_epoch	= strtotime($arrivaldate);
				$arrivaldate	= date('F Y',$arrival_epoch);
				if($arrival_epoch < time()){
					$html .= "<p>In Nigeria since $arrivaldate</p>";
				}else{
					$html .= "<p>Expected arrival in Nigeria: $arrivaldate</p>";
				}
			}
			
			$html .= show_phonenumbers($user_id);
		}else{
			$html .= "<p>Nothing to show here due to privacy settings.</p>";
		}
	}
	
	return $html;
}

function show_user_info($user_id){
	$html = ""; 
	
	$userdata = get_userdata($user_id);
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	//If it is a valid user and privacy allows us to show it
	if ($userdata != null){
		if(isset($privacy_preference['hide_name'])){ 
			$displayname = '';
		}else{
			$displayname = $userdata->first_name;
		}
		
		$html .= "<p>";
		if(empty($privacy_preference['hide_profile_picture'])){
			$html .= display_profile_picture($user_id);
			$style = "";
		}else{
			$style = ' style="margin-left: 55px;"';
		}
		
		if(!isset($privacy_preference['hide_ministry'])){ 
			$html .= "<span class='person_work' $style> $displayname works with: </span><br>";
			$html .= add_ministry_links($user_id);
		}
		$html .= "</p>";
		$html .= show_phonenumbers($user_id);
	}
	
	return $html;
}

function show_phonenumbers($user_id){
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	$html = "";
	if(!isset($privacy_preference['hide_phone'])){
		$phonenumbers = (array)get_user_meta( $user_id, 'phonenumbers', true );
	
		$html .= "<p><span style='font-size: 15px;'>Contact details below are only for you to use.<br>Do not share with other people.</span><br><br>";
		$email	= get_userdata($user_id)->user_email;
		$html .= "E-mail: <a href='mailto:$email'>$email</a><br>";
		if(count($phonenumbers) == 1){
			$html .= "Phone number: ".$phonenumbers[0]."<br><br>";
		}elseif(count($phonenumbers) > 1){
			$html .= "Phone numbers:<br>";
			foreach($phonenumbers as $key=>$phonenumber){
				$html .= "Phone number $key is: $phonenumber<br><br>";
			}
		}
		$html .= add_vcard_download($user_id).'</p>';
	}
	
	return $html;
}

//Build hyperlinks for ministries
function add_ministry_links($UserID){
	$user_ministries = (array)get_user_meta( $UserID, "user_ministries", true);

	$html = "";
	foreach($user_ministries as $key=>$user_ministry){
		if(!empty($user_ministry)){
			$ministry = str_replace("_"," ",$key);
			$ministry_page = get_page_by_title( $ministry);
			if ( $ministry_page != ""){
				$page_url = get_post_permalink($ministry_page->ID);
				$page_url =  '<a class="ministry_link" href="'.$page_url.'">'.$ministry.'</a>';
			}else{
				$page_url = $ministry;
			}
			$html .= $page_url.' as '.$user_ministry.'<br>';
		}
	}

	if($html == "") $html = "No clue, since no one told me.";
	return $html;
}

//Create vcard
function add_vcard_download($UserID){
	global $post;
	//Make vcard
	if (isset($_GET['vcard'])){
		ob_end_clean();
		//ob_start();
		$UserID = $_GET['vcard'];
		header('Content-Type: text/x-vcard');
		header('Content-Disposition: inline; filename= "'.get_userdata($UserID)->data->display_name.'.vcf"');

		$vcard = build_vcard($UserID);
		echo $vcard;
		die();

	//Return vcard hyperlink
	}else{
		$url = add_query_arg( ['vcard' => $UserID], get_permalink( $post->ID ) );
		return '<a href="'.$url.'" class="button sim vcard">Add to your contacts</a>';
	}
}

//Get vcard contents for a person
function build_vcard($user_id){
	//Get the user partner
	$family = (array)get_user_meta( $user_id, 'family', true );
	if (isset($family['partner'])){
		$partner = $family['partner'];
	}else{
		$partner = "";
	}
	
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	if(empty($privacy_preference['hide_location'])){ 
		//Get the user address
		$location = get_user_meta( $user_id, 'location', true );
		if (!empty($location['address'])){
			$address = $location['address'];
			$lat = $location['latitude'];
			$lon = $location['longitude'];
		}
	}
	$gender 	= get_user_meta( $user_id, 'gender', true );
	$userdata 	= get_userdata($user_id);
		
	$vcard = "BEGIN:VCARD\r\n";
	$vcard .= "VERSION:4.0\r\n";
	if(empty($privacy_preference['hide_name'])){
		$vcard .= "FN:".$userdata->data->display_name."\r\n";
		$vcard .= "N:".$userdata->last_name.";".$userdata->first_name.";;;\r\n";
	}
	$vcard .= "ORG:SIM Nigeria\r\n";
	$vcard .= "EMAIL;TYPE=INTERNET;TYPE=WORK:".$userdata->user_email."\r\n";
	
	if(empty($privacy_preference['hide_phone'])){
		$phonenumbers = get_user_meta( $user_id, 'phonenumbers', true );
		if (is_array($phonenumbers)){
			foreach ($phonenumbers as $key=>$phonenumber){
				switch ($key) {
				case 0:
					$type = "cell";
					break;
				case 1:
					$type = "home";
					break;
				case 2:
					$type = "work";
					break;
				default:
					$type = "cell";
				}
				$vcard .= "TEL;TYPE=$type:$phonenumber\r\n";
			}
		}
	}
	if ($address){
		$vcard .= "ADR;TYPE=HOME:;;$address\r\n";
		$vcard .= "GEO:geo:".$lat.",".$lon."\r\n";
	}
	$vcard .= "BDAY:".str_replace("-","",get_user_meta( $user_id, "birthday", true ))."\r\n";
	if ($partner != ""){
		$vcard .= "item1.X-ABRELATEDNAMES:".get_userdata($partner)->data->display_name."\r\n";
		$vcard .= 'item1.X-ABLabel:_$!<Spouse>!$_';
		$vcard .= "X-SPOUSE:".get_userdata($partner)->data->display_name."\r\n";
	}
	
	if ($gender != ""){
		if($gender == "female"){
			$vcard .= "GENDER:F\r\n";
		}else{
			$vcard .= "GENDER:M\r\n";
		}
	}
	
	//User has an profile picture add it
	if (is_numeric(get_user_meta($user_id,'profile_picture',true)) and empty($privacy_preference['hide_profile_picture'])){
		$picture_url 			= str_replace(wp_upload_dir()['url'],wp_upload_dir()['path'],get_profile_picture_url($user_id, "large"));
		$photo               	= file_get_contents($picture_url);
		$b64vcard               = base64_encode($photo);
		$b64mline               = chunk_split($b64vcard,74,"\n");
		$b64final               = preg_replace("/(.+)/", " $1", $b64mline);
		$vcard 					.= "PHOTO;ENCODING=b;TYPE=JPEG:";
		$vcard 					.= $b64final . "\r\n";
	}
	$vcard .= "END:VCARD\r\n";
	return $vcard;
}
