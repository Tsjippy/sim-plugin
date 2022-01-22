<?php
namespace SIM;
//Create token: https://trello.com/1/authorize?expiration=never&scope=read,write,account&response_type=token&name=Server%20Token&key=2fbe97b966e413fe5fc5eac889e2146a
//https://developer.atlassian.com
//https://developer.atlassian.com/cloud/trello/guides/rest-api/api-introduction/

// Get member info: https://api.trello.com/1/members/harmsenewald

class Trello{
	function __construct(){
		global $TrelloApiKey;
		global $TrelloApiToken;
		
		//Initialization
		$this->api_key 		= $TrelloApiKey;
		$this->api_token	= $TrelloApiToken;
		$this->query 		= array(
			'key' 	=> $TrelloApiKey,
			'token' => $TrelloApiToken
		);
		$this->headers = array(
		  'Accept' => 'application/json'
		);
		$this->member_id	= $this->get_token_info()->id;
	}

	//Get active boards
	function get_boards(){
		try{
			if (!isset($this->boards)){
				$this->boards	= [];

				$query = $this->query;
				$query['filter'] = 'open';
				$response = \Unirest\Request::get(
					"https://api.trello.com/1/members/me/boards",
					$this->headers,
					$query
				);

				foreach($response->body as $board){
					$this->boards[$board->name] = $board->id;
				}
			}
			
			return $this->boards;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return [];
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return [];
		}
	}
	
	//Get all the lists for all the active boards
	function get_all_lists(){
		$this->get_boards();
		
		if(!isset($this->lists)) $this->lists = [];
		
		foreach($this->boards as $board_id){
			$this->get_board_list($board_id);
		}
		
		return $this->lists;
	}
	
	//Get lists for specific board
	function get_board_list($board_id){
		try{
			if (!isset($this->lists[$board_id])){			
				$response = \Unirest\Request::get(
					"https://api.trello.com/1/boards/$board_id/lists",
					$this->headers,
					$this->query
				);
				
				foreach($response->body as $list){
					$this->lists[$board_id][$list->name] = $list->id;
				}
			}
			
			if(isset($this->lists[$board_id])){
				return $this->lists[$board_id];
			}else{
				return "Board with id $board_id does not exist!";
			}
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return [];
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return [];
		}
	}
	
	//Get all cards from a list
	function get_list_cards($list_id){
		try{
			$response = \Unirest\Request::get(
			  "https://api.trello.com/1/lists/$list_id/cards",
			  $this->headers,
			  $this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return [];
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return [];
		}
	}
	
	//Get a card
	function get_card($card_id){
		try{
			$response = \Unirest\Request::get(
				"https://api.trello.com/1/cards/$card_id",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//update a field on a card
	function update_card($card_id, $field_name, $field_value){
		try{
			//Make sure linebreaks stay there
			$field_value = str_replace("\n",'%0A',$field_value);
			
			$response = \Unirest\Request::put(
				"https://api.trello.com/1/cards/$card_id?key={$this->api_key}&token={$this->api_token}&$field_name=$field_value"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Ge specific field on the card
	function get_card_field($card_id, $field){
		try{
			$response = \Unirest\Request::get(
				"https://api.trello.com/1/cards/$card_id/$field",
				$this->headers,
				$this->query
			);
			
			return $response->body->_value;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Remove member from a card
	function remove_card_member($card_id, $member_id=''){
		try{
			if($member_id == '')  $member_id = $this->member_id;
			
			$response = \Unirest\Request::delete(
				"https://api.trello.com/1/cards/$card_id/idMembers/$member_id?key={$this->api_key}&token={$this->api_token}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Move card to another list
	function move_card_to_list($card_id,$list_id){
		global $TrelloApiKey;
		global $TrelloApiToken;
		
		try{
			$response = \Unirest\Request::put(
				"https://api.trello.com/1/cards/$card_id?key={$this->api_key}&token={$this->api_token}&idList=$list_id"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return [];
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return [];
		}
	}
	
	//Get checklist on a card
	function get_card_checklist($card_id){
		try{
			$response = \Unirest\Request::get(
			"https://api.trello.com/1/cards/$card_id/checklists",
			$this->headers,
			$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Create a checklist on a card
	function create_checklist($card_id, $name){
		try{
			$query = $this->query;
			$query['name'] = $name;
			$query['pos'] = 'top';
			
			$response = \Unirest\Request::post(
				"https://api.trello.com/1/cards/$card_id/checklists",
				$this->headers,
				$query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Add checklist options
	function add_checklist_item($checklist_id,$item_name){
		try{
			$query				= $this->query;
			$query['name']		= $item_name;
			$query['checked']	= true;
			
			$response = \Unirest\Request::post(
				"https://api.trello.com/1/checklists/$checklist_id/checkItems",
				$this->headers,
				$query
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Make an option checked
	function check_checklist_item($card_id, $check_item_id){
		try{
			$response = \Unirest\Request::put(
				"https://api.trello.com/1/cards/$card_id/checkItem/$check_item_id?key={$this->api_key}&token={$this->api_token}&state=complete",
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//add or update checlist options
	function change_checklist_option($card_id, $checklist, $item_name){
		$exists			= false;
		
		//Loop over all the checklist items
		foreach($checklist->checkItems as $item){
			//if not checked we should process it
			if($item->name == $item_name){
				if($item->state == 'incomplete'){
					//check the item
					$this->check_checklist_item($card_id, $item->id);
					return 'Updated';
				}else{
					//Item already exists and is already checked
					return "Exists already";
				}
			}
		}
		
		if($exists == false){
			$this->add_checklist_item($checklist->id,$item_name);
			return "Created";
		}
		
		return 'Not found';
	}
	
	//Add comment to card
	function add_comment($card_id, $comment){
		try{
			$query			= $this->query;
			$query['text']	= $comment;
			$response = \Unirest\Request::post(
				"https://api.trello.com/1/cards/$card_id/actions/comments",
				$this->headers,
				$query
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Get cover image
	function get_cover_image($card_id){
		try{
			$query 				= $this->query;
			$query['filter'] 	= 'cover';
			
			$response = \Unirest\Request::get(
				"https://api.trello.com/1/cards/$card_id/attachments?key={$this->api_key}&token={$this->api_token}",
			);
			
			return $response->body[0]->url;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//get info about the user a token belongs to
	function get_token_info(){
		try{
			$response = \Unirest\Request::get(
				'https://api.trello.com/1/tokens/'.$this->api_token.'/member',
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//list webhooks
	function get_webhooks(){
		try{
			$response = \Unirest\Request::get(
				"https://api.trello.com/1/tokens/{$this->api_token}/webhooks",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//create a new webhooks
	function create_webhook($url, $modelid, $description=''){
		try{
			$query = $this->query;
			$query['callbackURL']	= $url;
			$query['idModel']		= $modelid;
			$query['description']	= $description;

			$response = \Unirest\Request::post(
				'https://api.trello.com/1/webhooks/',
				$this->headers,
				$query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Change webhook id
	function change_webhook_id($webhookid, $modelid){
		try{
			$response = \Unirest\Request::put(
				"https://api.trello.com/1/webhooks/$webhookid?key={$this->api_key}&token={$this->api_token}&idModel=$modelid"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//Delete a webhook
	function delete_webhook($webhook_id){
		try{
			$response = \Unirest\Request::delete(
				"https://api.trello.com/1/webhooks/$webhook_id?key={$this->api_key}&token={$this->api_token}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
	
	//delete all webhooks
	function delete_all_webhooks(){
		$webhooks = $this->get_webhooks();
		
		$result = [];
		foreach($webhooks as $webhook){
			$result[] = $this->delete_webhook($webhook->id);
		}
		
		return $result;
	}
	
	function search_card_item($card_id, $search_key){
		try{
			$query				= $this->query;
			$query['query']		= $search_key;
			$query['idCards']	= $card_id;
			
			$response = \Unirest\Request::get(
				'https://api.trello.com/1/search',
				$this->headers,
				$query
			);
			
			return $response->body->cards;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$error_result = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			print_array($error_result);
			return ;
		}catch(\Exception $e) {
			$error_result = $e->getMessage();
			print_array($error_result);
			return;
		}
	}
}

//Only load trello in production
if(strpos(get_site_url(), 'localhost') === false){
	$Trello = new Trello();
}

//Shortcode on the page which will be called by trello when something happens
add_shortcode("trello_webhook",function(){
	global $Trello;

	$data = json_decode(file_get_contents('php://input'));
	
	//only do something when the action is addMemberToCard
	if($data->action->type == 'addMemberToCard'){
		$checklist_name 	= 'Website Actions';
		$card_id 			= $data->action->data->card->id;
		$website_checklist	= '';
		
		//Remove self from the card again
		$Trello->remove_card_member($card_id);
		
		//get the checklists of this card
		$checklists = $Trello->get_card_checklist($card_id);
		
		//loop over the checklists to find the one we use
		foreach($checklists as $checklist){
			if($checklist->name == $checklist_name)	$website_checklist = $checklist;
		}
		
		//If the checklist does not exist, create it
		if($website_checklist == '')	$website_checklist = $Trello->create_checklist($card_id, $checklist_name);
		
		//Get the description of the card
		$desc = $Trello->get_card_field($card_id, 'desc');
		
		//First split on new lines
		$data 		= explode("\n",$desc);
		$user_props	= [];
		foreach($data as $item){
			//then split on :
			$temp = explode(':', $item);
			if($temp[0] != '')	$user_props[trim(strtolower($temp[0]))] = trim($temp[1]);
		}
		
		//useraccount exists
		if(is_numeric($user_props['user_id'])){
			$user_id = $user_props['user_id'];
			
		//create an user account
		}elseif(!empty($user_props['email address']) and !empty($user_props['first name']) and !empty($user_props['last name'])  and !empty($user_props['duration'])){
			print_array('Creating user account from trello',true);
			print_array($user_props,true);
			
			//Find the duration number an quantifier in the result
			$pattern = "/([0-9]+) (months?|years?)/i";
			preg_match($pattern, $user_props['duration'],$matches);
			
			//Duration is defined in years
			if (strpos($matches[2], 'year') !== false) {
				$duration = $matches[1] * 12;
			//Duration is defined in months
			}else{
				$duration = $matches[1];
			}

			//create an useraccount
			$user_id = add_user_account(ucfirst($user_props['first name']), ucfirst($user_props['last name']), $user_props['email address'], true, $duration);
			
			if(is_numeric($user_id)){
				//send welcome e-mail
				wp_new_user_notification($user_id,null,'both');

				//Add a checklist item on the card
				$Trello->change_checklist_option($card_id, $website_checklist, 'Useraccount created');
				
				//Add a comment
				$Trello->add_comment($card_id,"Account created, user id is $user_id");
				
				//Update the description of the card
				$url	= get_site_url()."/update-personal-info/?userid=$user_id";
				$Trello->update_card($card_id, 'desc', $desc."%0A <a href='$url'>user_id:$user_id</a>");
			}
		}else{
			//no account yet and we cannot create one
			return;
		}
		
		$username = get_userdata($user_id)->user_login;
		
		/* 		
			SAVE COVER IMAGE AS PROFILE PICTURE 
		*/
		//Get the cover image url
		$url = $Trello->get_cover_image($card_id);
		//If there is a cover image
		if($url != ''){
			//And an image is not yet set
			if(!is_numeric(get_user_meta($user_id,'profile_picture',true))){
				//Get the extension
				$ext = pathinfo($url, PATHINFO_EXTENSION);
				
				//Save the picture
				$filepath 	= wp_upload_dir()['path']."/private/profile_pictures/$username.$ext";
				
				if(file_exists($filepath)) unlink($filepath);
				
				file_put_contents($filepath, file_get_contents($url));
				
				//Add to the library
				$post_id = add_to_library($filepath);
				
				//Save in the db
				update_user_meta($user_id,'profile_picture',$post_id);
				
				//Add a checklist item on the card
				$Trello->change_checklist_option($card_id, $website_checklist, 'Profile picture');
			}
		}
	}
});