<?php
namespace SIM\MAILCHIMP;
use SIM;
use WP_Error;

//https://mailchimp.com/developer/marketing

if(!class_exists(__NAMESPACE__.'\Mailchimp')){
	require_once( __DIR__  . '/../../lib/vendor/autoload.php');

	class Mailchimp{
		public $userId;

		public function __construct($userId=''){
			global $Modules;

			$this->settings		= $Modules[MODULE_SLUG];

			if(is_numeric($userId)){
				$this->user			= get_userdata($userId);

				//Get phone number
				$this->phonenumbers = get_user_meta( $this->user->ID, "phonenumbers", true);

				//Get mailchimp status from db
				$this->mailchimpStatus = get_user_meta($this->user->ID, "MailchimpStatus", true);
			}

			$api = explode('-', $this->settings['apikey']);
			$this->client = new \MailchimpMarketing\ApiClient();
			$this->client->setConfig([
				'apiKey' => $api[0],
				'server' => $api[1],
			]);
		}

		/**
		 * Creates Mailchimp merge tags
		 *
		 * @return	array	merge tags
		 */
		public function buildMergeTags(){
			$mergeFields = array(
				'FNAME' 	=> $this->user->first_name,
				'LNAME' 	=> $this->user->last_name,
			);

			if(is_array($this->phonenumbers)){
				$mergeFields['PHONE'] = $this->phonenumbers[1];
			}

			$birthday = get_user_meta( $this->user->ID, "birthday",true);
			if(!empty($birthday)){
				$birthday				= explode('-',$birthday);
				//Mailchimp wants only the month and the day
				$mergeFields['BIRTHDAY'] = $birthday[1].'/'.$birthday[2];
			}

			return $mergeFields;
		}

		/**
		 * Add user to mailchimp list
		 */
		public function addToMailchimp(){
			//Only do if valid e-mail
			if($this->user->user_email != '' && strpos($this->user->user_email,'.empty') === false && $_SERVER['HTTP_HOST'] != 'localhost'){
				SIM\printArray("Adding '{$this->user->user_email}' to Mailchimp");

				//First add to the audience
				$this->subscribeMember($this->buildMergeTags());

				//Build tag list
				$roles = $this->user->roles;

				$confidentialGroups	= (array)SIM\getModuleOption('contentfilter', 'confidential-roles');
				if(array_intersect($confidentialGroups, $roles)){
					$tags = explode(',', $this->settings['user_tags']);
				}else{
					$tags = array_merge(explode(',', $this->settings['user_tags']), explode(',', $this->settings['missionary_tags']));
				}

				$this->changeTags($tags, 'active');
			}
		}

		/**
		 * Add or remove mailchimp tags
		 *
		 * @param	array	$tags	The tags to add to a user
		 * @param	string	$status	On of active or inactive
		 */
		public function changeTags($tags, $status){
			if(!is_array($this->mailchimpStatus)){
				$this->mailchimpStatus = [];
			}

			if($this->user->user_mail == '' || strpos($this->user->user_mail,'.empty') !== false){
				return;
			}

			//Loop over all the segments
			foreach($tags as $tag){
				//Only update if needed
				if($tag != ""){
					if($status == 'active' && (!isset($this->mailchimpStatus[$tag]) || $this->mailchimpStatus[$tag] != 'succes')){
						//Process tag
						$response = $this->setTag($tag, $status);

						//Subscription succesfull
						if( $response){
							SIM\printArray("Succesfully added the $tag tag to {$this->user->display_name}");
						//Subscription failed
						}else{
							SIM\printArray("Tag $tag  was not added to user wih email {$this->user->user_mail}} because: $response" );
						}

						//Store result
						$this->mailchimpStatus[$tag] = $response;
					}elseif($status == 'inactive' && isset($this->mailchimpStatus[$tag])){
						//Process tag
						$response = $this->setTag($tag, $status);

						//Unsubscription succesfull
						if( $response){
							SIM\printArray("Succesfully removed the $tag tag from {$this->user->display_name}");
							unset($this->mailchimpStatus[$tag]);
						//Subscription failed
						}else{
							SIM\printArray("Tag $tag  was not removed from user {$this->user->display_name} because: $response" );
						}
					}
				}
			}

			//Store results in db
			update_user_meta($this->user->ID, "MailchimpStatus", $this->mailchimpStatus);
		}

		/**
		 * Get a list of lists (audiences)
		 *
		 * @return	array|string	the lists or an error string
		 */
		public function getLists(){
			try {
				$lists = $this->client->lists->getAllLists();
				return $lists->lists;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result			= json_decode($e->getResponse()->getBody()->getContents());
				$errorResult	= $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return $errorResult;
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}
		}

		/**
		 * Add someone to the audience of Mailchimp
		 *
		 * @return	array|string	The result or error
		 */
		public function subscribeMember($mergeFields){
			try {
				$email = $this->user->user_email;
				return $this->client->lists->setListMember(
					$this->settings['audienceids'][0],
					md5(strtolower($email)),
					[
						"email_address" => strtolower($email),
						"status_if_new" => 'subscribed',
						"merge_fields" 	=> $mergeFields
					]
				);
			}

			//catch exception
			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return $errorResult;
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}
		}

		/**
		 * Add a tag to the current user
		 *
		 * @param	string	$tagname	the name of the tag
		 * @param	string	$status		active or inactive
		 *
		 * @return	true|WP_Error				true on succes else failure
		 */
		public function setTag($tagname, $status){
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

				return true;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());

				if($result->detail == "The requested resource could not be found."){
					$this->addToMailchimp();
				}

				$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return new WP_Error('mailchimp', $errorResult);
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return new WP_Error('mailchimp', $errorResult);
			}
		}

		/**
		 * Send an e-mail via Mailchimp
		 *
		 * @param	int		$postId			The post id to send
		 * @param	int		$segmentId		The id of the Mailchimp segment to e-mail to
		 * @param	string	$from			The from e-mail to use
		 * @param	string	$extraMessage	THe extra message to prepend the -mail contents with
		 */
		public function sendEmail(int $postId, int $segmentId, $from='', $extraMessage=''){
			try {
				$post 			= get_post($postId);

				$title			= $post->post_title;

				$excerpt 		= html_entity_decode(wp_trim_words($post->post_content ,20));
				$excerpt 		= strip_tags(str_replace('<br>',"\n",$excerpt)).'...';

				if($from == ''){
					$email		= get_userdata($post->post_author)->user_email;
				}else{
					$email		= $from;
				}

				SIM\printArray("Creating mailchimp campain");
				//Create a campain
				try{
					$response = $this->client->campaigns->create(
						[
							"type" 			=> "regular",
							"recipients"	=> [
								"list_id"		=> $this->settings['audienceids'][0],
								"segment_opts"	=> [
									"saved_segment_id"	=> $segmentId
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
					$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
					SIM\printArray($errorResult);
					return $errorResult;
				}catch(\Exception $e) {
					$errorResult = $e->getMessage();
					SIM\printArray($errorResult);
					return $errorResult;
				}

				//Get the campain id
				$campainId = $response->id;
				SIM\printArray("Campain_id is $campainId");

				//get the campain html
				$response 			= $this->client->campaigns->getContent($campainId);

				$campainContent 	= $response->html;

				//Update the html
				$mailContent		= $extraMessage.'<br>'.$post->post_content;

				$replaceText 		= '//*THIS WILL BE REPLACED BY THE WEBSITE *//';

				$mailContent		= apply_filters('sim_before_mailchimp_send', $mailContent, $post);

				$mailContent 		= str_replace($replaceText, $mailContent, $campainContent);

				//Push the new content
				$response = $this->client->campaigns->setContent(
					$campainId,
					[
						"html"	=> $mailContent,
					]
				);

				//Send the campain
				$response = $this->client->campaigns->send($campainId);

				SIM\printArray("Mailchimp campain send succesfully");
				return 'succes';
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return $errorResult;
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}
		}

		/**
		 * Get an array of available segements in the audience
		 * Store in transient for 24 hours as this is a slow action
		 *
		 * @return	array|string	Segments array or error string
		 */
		public function getSegments(){
			if(empty($this->settings['audienceids'][0])){
				$error	= 'No Audience defined in mailchimp module settings';
				SIM\printArray($error);
				return new \WP_Error('mailchimp', $error);
			}

			$segments	= get_transient( 'mailchimp_segments' );
			if(is_array($segments)){
				return $segments;
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

				set_transient( 'mailchimp_segments', $response->segments, DAY_IN_SECONDS );

				return $response->segments;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				return $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}
		}

		/**
		 * Get an array of templates
		 *
		 * @return	array|string	Templates or error string
		 */
		public function getTemplates(){
			try {
				$response = $this->client->templates->list(
					null,		//fields
					null,		// excludeFields
					'1000',		// count
					'0',		// offset
					null,		// createdBy
					null,		// sinceDateCreated
					null,		// beforeDateCreated
					'user'		// type
				);

				return $response->templates;
			}

			//catch exception
			catch(\GuzzleHttp\Exception\ClientException $e){
				$result = json_decode($e->getResponse()->getBody()->getContents());
				$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
				SIM\printArray($errorResult);
				return $errorResult;
			}catch(\Exception $e) {
				$errorResult = $e->getMessage();
				SIM\printArray($errorResult);
				return $errorResult;
			}
		}

		/**
		 * Add or remove mailchimp tags for families
		 * @param	array	$tags	array of tags
		 * @param	string	$status	active or inactive
		 */
		public function updateFamilyTags($tags, $status){
			$this->changeTags($this->user->ID, $tags, $status);

			//Update the meta key for all family members as well
			$family = SIM\familyFlatArray($this->user->ID);
			if (count($family)>0){
				foreach($family as $relative){
					//Update the marker for the relative as well
					$this->changeTags($relative, $tags, $status);
				}
			}
		}
	}
}
