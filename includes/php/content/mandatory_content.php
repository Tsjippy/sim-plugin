<?php
namespace SIM;

function save_page_audience($post_id){
	if(is_array($_POST['pagetype'])) {
		$pagetype = $_POST['pagetype'];
		
		//Reset to normal if that box is ticked
		if(isset($pagetype['normal']) and $pagetype['normal'] == 'normal'){
			delete_post_meta($post_id,"audience");
		//Store in DB
		}else{
			$audiences = [];
			//Store the audiencetype(s)
			foreach($_POST['pagetype'] as $type){
				if($type != '') $audiences[$type] = $type;
			}
			
			//Only continue if there are audiences defined
			if(count($audiences)>0){
				update_post_meta($post_id,"audience",$audiences);
			
				//Mark existing users as if they have read the page if this pages should be read by new people after arrival
				if(isset($audiences['afterarrival']) and !isset($audiences['everyone'])){
					//Get all users who are longer than 1 month in the country
					$users = get_users(array(
						'meta_query' => array(
							array(
								'key' => 'arrival_date',
								'value' => date('Y-m-d', strtotime("-1 months")),
								'type' => 'date',
								'compare' => '<='
							)
						),
					));
					
					//Loop over the users
					foreach($users as $user){
						//get current already read pages
						$read_pages		= (array)get_user_meta( $user->ID, 'read_pages', true );
		
						//add current page
						$read_pages[]	= $post_id;
						//update
						update_user_meta( $user->ID, 'read_pages', $read_pages);
					}
					
				}
			}
		}
	}
}

add_shortcode("must_read_documents",'SIM\get_must_read_documents');
function get_must_read_documents($user_id='',$exclude_heading=false){
	if(!is_numeric($user_id)) $user_id = get_current_user_id();
	
	//Get all the page this user already read
	$read_pages		= get_user_meta( $user_id, 'read_pages', true );
	if(!is_array($read_pages)) $read_pages = [];
	
	//Array of documents unique for each person
	$personal_document_array = [
		'welcomeletter'		=> 'Welcome Letter',
		'mealschedule'		=> 'Meal Schedule',
		'orientation'		=> 'Orientation Schedule',
		'jobdescription'	=> 'Job Description',
	];
	
	$personnel_documents 	= get_user_meta( $user_id, "personnel_documents",true);
	if(!is_array($personnel_documents ))	$personnel_documents  = [];
	
	$html 			= '';
	$before_html 	= '';
	$arrived_html 	= '';
	
	//Get the users arrival date
	$arrivaldate 	= strtotime(get_user_meta( $user_id, 'arrival_date', true ));
	if($arrivaldate < time()){
		$arrived = true;
	}else{
		$arrived = false;
	}

	//Get all the pages with an audience meta key
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_name',
			'order' 		=> 'asc',
			'post_type' 	=> 'any',
			'post_status' 	=> 'publish',
			'meta_key' 		=> "audience",
			'numberposts'	=> -1,
		)
	);
	
	//Loop over the pages while building the html
	$arrived_pages_count = 0;
	foreach($pages as $page){
		//check is already read
		if(!in_array($page->ID,$read_pages)){
			$audience =  get_post_meta($page->ID,"audience",true);
			
			//Add a link if not yet in the country and should read before arriving
			if(isset($audience['beforearrival']) and !$arrived){
				$before_html .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
			}
			
			//Page has not been read and should be read by all users
			if(isset($audience['afterarrival']) or isset($audience['everyone'])){
				//If this page also needs to be read by users who are not yet arrived, do not show again
				if(!isset($audience['beforearrival']) or ($arrived and isset($audience['beforearrival']))){
					$arrived_html .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
					$arrived_pages_count++;
				}
			}
		}
	}
	
	///Documents to read before arrival
	if($before_html != '' or (count($personal_document_array)>0 and !$arrived)){
		if(!$exclude_heading){
			$html .= "<h3>Welcome!</h3><p>We are so happy to welcome you to Nigeria!<br>";
			$html .= "Please read and/or download the documents below to prepare for your stay.</p>";
		}
		$html .= "<ul>$before_html";
		foreach($personal_document_array as $key=>$document){
			if(isset($personnel_documents[$document])){
				$html .= "<li><a href='".get_site_url().'/'.$personnel_documents[$document]."'>$document</a></li>";
			}
		}
		$html .= "</ul>";
	}
	
	//Documents to read after arrival
	if($arrived_html != ''){
		if($arrived_pages_count == 1){
			$page = "page";
		}else{
			$page = "pages";
		}
		
		if(!$exclude_heading){
			$html .= "<h3>Please read the following $page:</h3>";
		}
		$html .= "<ul>".$arrived_html."</ul>";
	}
	
	if($html != ''){
		return "<div id='personalinfo'>$html</div>";
	}
}

add_filter( 'the_content', function ($content){
	if (is_user_logged_in()){
		$post_id 	= get_the_ID();
		$user_id 	= get_current_user_id();
		$audience 	= (array)get_post_meta($post_id,"audience",true);
		$read_pages	= (array)get_user_meta( $user_id, 'read_pages', true );
		
		//Get the users arrival date
		$arrivaldate 	= strtotime(get_user_meta( $user_id, 'arrival_date', true ));
		if($arrivaldate){
			if($arrivaldate < time()){
				$arrived = true;
			}else{
				$arrived = false;
			}
		}else{
			$arrived = false;
		}
		
		//People should read this, and have not read it yet
		if(
			!in_array($post_id,$read_pages)						and 
			((isset($audience['beforearrival']) and !$arrived)	or 
			isset($audience['afterarrival']) 					or
			isset($audience['everyone']))
		){
			wp_enqueue_script('simnigeria_forms_script');
			$message = '<p style="border: 3px solid #bd2919; padding: 10px; text-align: center;">
				This is mandatory content.<br>
				Make sure you have clicked the "I have read this" button after reading.
			</p>';
			$content	 = $message.$content;
			$content	.= "<button class='mark_as_read button' data-postid='$post_id' data-userid='$user_id'>I have read this</button>";
		}
	}
	
	return $content;
});

add_action ( 'wp_ajax_mark_page_as_read', function(){
	if(is_numeric($_POST['userid']) and is_numeric($_POST['postid'])){
		$user_id = $_POST['userid'];
		$post_id = $_POST['postid'];
		
		//get current alread read pages
		$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
		
		//add current page
		$read_pages[]	= $post_id;
		//update
		update_user_meta( $user_id, 'read_pages', $read_pages);
		
		wp_die("Succesfully marked this page as read");
	}else{
		wp_die('Invalid user or post id',500);
	}
});

function markasread(\WP_REST_Request $request){
	global $wpdb;
	$email		= $request['email'];
	$post_id	= $request['postid'];

	//only continue if valid email and numeric postid
	if (filter_var($email, FILTER_VALIDATE_EMAIL) and is_numeric($post_id)) {
		//set the admin as the user so we can query the db
		wp_set_current_user(1);

		$user_id		= get_user_by( 'email', $email )->ID;

		//no user, check secundairy email
		if(!is_numeric($user_id)){
			$user_id = get_users(['meta_key' => 'email','meta_value' => $email])[0]->ID;
		}

		$title	= get_the_title($post_id);

		if(!is_numeric($user_id)){
			$message	= "We could not find an user with the e-mail '$email'";
			$type		= 'Error';
		}elseif(empty($title)){
			$message	= "We could not find the page";
			$type		= 'Error';
		}else{
			//get current alread read pages
			$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
				
			//add current page
			$read_pages[]	= $post_id;

			//update
			update_user_meta( $user_id, 'read_pages', $read_pages);

			$message	= "Succesfully marked '".get_the_title($post_id)."' as read.";
			$type		= 'Success';
		}

		wp_redirect( home_url("?message=$message&type=$type") );
		exit();

	}
}

add_action( 'rest_api_init', function () {
	//Route to update mark as read from mailchimp
	register_rest_route( 'simnigeria/v1', '/markasread', array(
		'methods' => 'GET',
		'callback' => 'SIM\markasread',
		'permission_callback' => '__return_true',
		)
	);
});