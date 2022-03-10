<?php
namespace SIM\MAILCHIMP;
use SIM;

//https://mailchimp.com/developer/marketing

if(!class_exists('SIM\MAILCHIMP\Mailchimp')){
	class Mailchimp{
		public $user_id;
		
		function __construct($user_id=''){
			global $Modules;

			$this->settings		= $Modules['mailchimp'];
			
			if(is_numeric($user_id)){
				$this->user			= get_userdata($user_id);
				
				//Get phone number
				$this->phonenumbers = get_user_meta( $this->user->ID, "phonenumbers",true);
				
				//Get mailchimp status from db
				$this->mailchimpstatus = get_user_meta($this->user->ID,"MailchimpStatus",true);	
			}

			$api = explode('-', $this->settings['apikey']);
			$this->client = new \MailchimpMarketing\ApiClient();
			$this->client->setConfig([
				'apiKey' => $api[0],
				'server' => $api[1],
			]);
		}
		
		function build_merge_tags(){
			$merge_fields = array( 
				'FNAME' 	=> $this->user->first_name,
				'LNAME' 	=> $this->user->last_name,
			);
			
			if(is_array($this->phonenumbers)){
				$merge_fields['PHONE'] = $this->phonenumbers[1];
			}
			
			$birthday = get_user_meta( $this->user->ID, "birthday",true);
			if($birthday != ""){
				$birthday = explode('-',$birthday);
				//Mailchimp wants only the month and the day
				$merge_fields['BIRTHDAY'] = $birthday[1].'/'.$birthday[2];
			}
			
			return $merge_fields;
		}
		
		//Add user to mailchimp list
		function add_to_mailchimp(){			
			//Only do if valid e-mail
			if($this->user->user_email != '' and strpos($this->user->user_email,'.empty') === false){
				SIM\print_array("Adding '{$this->user->user_email}' to Mailchimp");
				
				//First add to the audience
				$this->subscribe_member($this->build_merge_tags());
				
				//Build tag list
				$roles = $this->user->roles;
			
				if(!in_array('nigerianstaff',$roles)){
					$TAGs = array_merge(explode(',',$this->settings['user_tags']),explode(',', $this->settings['missionary_tags']));
				}else{
					$TAGs = explode(',',$this->settings['user_tags']);
				}
				
				$this->change_tags($TAGs, 'active');
			}
		}
		
		//Add or remove mailchimp tags
		function change_tags($tags, $status){
			if(!is_array($this->mailchimpstatus)) $this->mailchimpstatus = [];
			
			if($this->user->user_mail == '' or strpos($this->user->user_mail,'.empty') !== false) return;
			
			//Loop over all the segments
			foreach($tags as $tag){
				//print_array("Processing $tag");
				//Only update if needed
				if($tag != ""){
					if($status == 'active' and (!isset($this->mailchimpstatus[$tag]) or $this->mailchimpstatus[$tag] != 'succes')){
						//Process tag
						$response = $this->set_tag($tag, $status);
						
						//Subscription succesfull
						if( $response == 'succes' ){
							SIM\print_array("Succesfully added the $tag tag to {$this->user->display_name}");
						//Subscription failed
						}else{
							SIM\print_array("Tag $tag  was not added to user wih email {$this->user->user_mail}} because: $response" );
						}
					
						//Store result
						$this->mailchimpstatus[$tag] = $response;
					}elseif($status == 'inactive' and isset($this->mailchimpstatus[$tag])){
						//Process tag
						$response = $this->set_tag($tag, $status);
						
						//Unsubscription succesfull
						if( $response == 'succes' ){
							SIM\print_array("Succesfully removed the $tag tag from {$this->user->display_name}");
							unset($this->mailchimpstatus[$tag]);
						//Subscription failed
						}else{
							SIM\print_array("Tag $tag  was not removed from user {$this->user->display_name} because: $response" );
						}
					}
				}
			}
			
			//Store results in db
			update_user_meta($this->user->ID,"MailchimpStatus",$this->mailchimpstatus);
		}
		
		function role_changed($new_roles){			
			if(in_array('nigerianstaff',$new_roles)){
				//Role changed to nigerianstaff, remove tags
				$tags = explode(',', $this->settings['missionary_tags']);
				$this->change_tags($tags, 'inactive');
				//Add office staff tags
				$this->change_tags(explode(',', $this->settings['office_staff_tags']), 'active');
				
			}
			
			if(in_array('nigerianstaff',$this->user->roles) and !in_array('nigerianstaff',$new_roles)){
				//Nigerian staff role is removed
				$tags = array_merge(explode(',', $this->settings['user_tags']), explode(',', $this->settings['missionary_tags']));
				$this->change_tags($tags, 'active');
			}
		}

		function update_merge_tags(){
			try {
				//Only do this if valid e-mail
				if(strpos($this->user->user_email,'.empty') === false){
					$merge_fields = $this->build_merge_tags();
					$this->client->lists->updateListMember(
						$this->settings['audienceids'][0], 
						md5(strtolower($this->user->user_email)), 
						[
							'merge_fields'  => $merge_fields
						]
					);
				}
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Get a list of lists (audiences)
		function get_lists(){
			try {
				$lists = $this->client->lists->getAllLists();
				return $lists->lists;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Get a list of groups
		function get_groups(){
			try {
				$response = $this->client->lists->getListInterestCategories($this->settings['audienceids'][0]);
				return $response->categories;
			}

			//catch exception
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Get a list of interests within a group
		function get_interests(){
			try {
				$response = $this->client->lists->listInterestCategoryInterests(
					$this->settings['audienceids'][0],
					"0dfcd34d95"
				);
				return $response->interests;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Add someone to the audience
		function subscribe_member($merge_fields){
			try {
				$email = $this->user->user_email;
				$response = $this->client->lists->setListMember(
					$this->settings['audienceids'][0], 
					md5(strtolower($email)), 
					[
						"email_address" => strtolower($email),
						"status_if_new" => 'subscribed',
						"merge_fields" => $merge_fields
					]
				);
			
				return $response;
			}

			//catch exception
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Function to deactivate someone from Mailchimp
		function archive_user(){
			try {
				$response = $this->client->lists->deleteListMember($this->settings['audienceids'][0], md5(strtolower($this->user->user_email)),[]);
				return "Deletion succesfull";
			}

			//catch exception
			catch(\Exception $e) {
				$response = $this->client->lists->getListMember($this->settings['audienceids'][0], md5(strtolower($this->user->user_email)));
				return "Deletion unsuccesfull, status is already {$response->status}";
			}
		}

		function set_tag($tagname, $status){
			try {
				$this->client->lists->updateListMemberTags(
					$this->settings['audienceids'][0],
					md5(strtolower($this->user->user_email)),
					['tags'=> [
						[
							"name" => $tagname,
							"status" => $status     
						]
					]]
				);
				
				return 'succes';
			}
			
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());

				if($result->detail == "The requested resource could not be found."){
					$this->add_to_mailchimp();
				}

				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		function send_email($post_id, $segment_id, $from=''){
			try {
				$post 			= get_post($post_id);
				
				$title			= $post->post_title;
				
				$excerpt 		= html_entity_decode(wp_trim_words($post->post_content ,20));
				$excerpt 		= strip_tags(str_replace('<br>',"\n",$excerpt)).'...';
				
				if($from == ''){
					$email		= get_userdata($post->post_author)->user_email;
				}else{
					$email		= $from;
				}
				
				SIM\print_array("Creating mailchimp campain");
				//Create a campain
				try{
					$response = $this->client->campaigns->create(
						[
							"type" 			=> "regular",
							"recipients"	=> [
								"list_id"		=> $this->settings['audienceids'][0],
								"segment_opts"	=> [
									"saved_segment_id"	=> $segment_id
								]
							],
							"settings"		=> [
								"subject_line"	=> $title,
								"preview_text"	=> $excerpt,
								"title"			=> $title,
								"from_name"		=> SITENAME,
								"reply_to"		=> $email,
								"to_name"		=> "*|FNAME|*",
								"template_id"	=> (int)$this->settings['templateid']
							]
						]
					);
				}catch(\GuzzleHttp\Exception\ClientException $e){
					$result = json_decode($e->getResponse()->getBody()->getContents());
					$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
					SIM\print_array($error_result);
					return $error_result;
				}catch(\Exception $e) {
					$error_result = $e->getMessage();
					SIM\print_array($error_result);
					return $error_result;
				}
							
				//Get the campain id
				$campain_id = $response->id;
				SIM\print_array("Campain_id is $campain_id");
				
				//get the campain html
				$response 			= $this->client->campaigns->getContent($campain_id);
				
				$campain_content 	= $response->html;
				
				//Update the html
				$mail_content		= $post->post_content;

				$replace_text 		= '//*THIS WILL BE REPLACED BY THE WEBSITE *//';
				$mail_content 		= str_replace($replace_text, $mail_content, $campain_content);

				$mail_content		= apply_filters('sim_before_mailchimp_send', $mail_content, $post);
				
				//Push the new content
				$response = $this->client->campaigns->setContent(
					$campain_id, 
					[
						"html"	=> $mail_content,
					]
				);
				
				//Send the campain
				$response = $this->client->campaigns->send($campain_id);
				
				SIM\print_array("Mailchimp campain send succesfully");
				return 'succes';
			}
			
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Get an array of available segements in the audience
		function get_segments(){
			if(empty($this->settings['audienceids'][0])){
				$error	= 'No Audience defined in mailchimp module settings';
				SIM\print_array($error);
				return $error;
			}

			try {
				$response = $this->client->lists->listSegments(
					$this->settings['audienceids'][0], 	//Audience id
					null, 						// Fields to return
					null,						// Fields to return
					100,						//	Maximum amount of segments
					0,							// Offset
					'saved'						// Only export segments, not tags
				);

				return $response->segments;
			}
			
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				//print_array($result->detail);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Get an array of templates
		function get_templates(){
			try {
				$response = $this->client->templates->list(
					$fields = null, 
					$exclude_fields = null, 
					$count = '1000', 
					$offset = '0', 
					$created_by = null, 
					$since_date_created = null, 
					$before_date_created = null, 
					$type = 'user'
				);

				return $response->templates;
			}
			
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\print_array($error_result);
				return $error_result;
			}catch(\Exception $e) {
				$error_result = $e->getMessage();
				SIM\print_array($error_result);
				return $error_result;
			}
		}

		//Add or remove mailchimp tags for families
		function update_family_tags($tags, $status){
			$this->change_tags($this->user->ID, $tags, $status);
				
			//Update the meta key for all family members as well
			$family = SIM\family_flat_array($this->user->ID);
			if (count($family)>0){
				foreach($family as $relative){
					//Update the marker for the relative as well
					$this->change_tags($relative, $tags, $status);
				}
			}
		}
	}
}


add_action('sim_after_post_save', function($post){
	//Mailchimp
	if(is_numeric($_POST['mailchimp_segment_id'])){
		if($post->post_status == 'publish'){
			//Send mailchimp
			$Mailchimp = new Mailchimp();
			$result = $Mailchimp->send_email($post->ID, intval($_POST['mailchimp_segment_id']), $_POST['mailchimp_email']);
			
			//delete any post metakey
			delete_post_meta($post->ID,'mailchimp_segment_id');
			delete_post_meta($post->ID,'mailchimp_email');
		}else{
			update_post_meta($post->ID,'mailchimp_segment_id',$_POST['mailchimp_segment_id']);
			update_post_meta($post->ID,'mailchimp_email',$_POST['mailchimp_email']);
		}
	}
});