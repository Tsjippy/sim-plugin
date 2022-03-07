<?php
namespace SIM\TRELLO;
use SIM;
use Unirest\Request;

//Create token: https://trello.com/1/authorize?expiration=never&scope=read,write,account&response_type=token&name=Server%20Token&key=2fbe97b966e413fe5fc5eac889e2146a
//https://developer.atlassian.com
//https://developer.atlassian.com/cloud/trello/guides/rest-api/api-introduction/
//https://developer.atlassian.com/cloud/trello/rest/

// Get member info: https://api.trello.com/1/members/harmsenewald

class Trello{
	function __construct(){
		global $Modules;
		$this->settings		= $Modules['trello'];

		$this->request		= new Request();
		
		//Initialization
		$this->api_key 		= $this->settings['key'];
		$this->api_token	= $this->settings['token'];
		$this->query 		= array(
			'key' 	=> $this->api_key,
			'token' => $this->api_token
		);
		$this->headers = array(
		  'Accept' => 'application/json'
		);
		$this->member_id	= $this->getTokenInfo()->id;
	}

	//Get active boards
	function getBoards(){
		try{
			if (!isset($this->boards)){
				$this->boards	= [];

				$query = $this->query;
				$query['filter'] = 'open';
				$response = $this->request->get(
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
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\print_array($errorResult);
			return [];
		}
	}
	
	//Get all the lists for all the active boards
	function getAllLists(){
		$this->getBoards();
		
		if(!isset($this->lists)) $this->lists = [];
		
		foreach($this->boards as $boardId){
			$this->getBoardList($boardId);
		}
		
		return $this->lists;
	}
	
	//Get lists for specific board
	function getBoardList($boardId){
		try{
			if (!isset($this->lists[$boardId])){			
				$response = $this->request->get(
					"https://api.trello.com/1/boards/$boardId/lists",
					$this->headers,
					$this->query
				);
				
				foreach($response->body as $list){
					$this->lists[$boardId][$list->name] = $list->id;
				}
			}
			
			if(isset($this->lists[$boardId])){
				return $this->lists[$boardId];
			}
				
			return "Board with id $boardId does not exist!";
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Get all cards from a list
	function getListCards($listId){
		try{
			$response = $this->request->get(
			  "https://api.trello.com/1/lists/$listId/cards",
			  $this->headers,
			  $this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Get a card
	function getCard($cardId){
		try{
			$response = $this->request->get(
				"https://api.trello.com/1/cards/$cardId",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//update a field on a card
	function updateCard($cardId, $fieldName, $fieldValue){
		try{
			//Make sure linebreaks stay there
			$fieldValue = str_replace("\n",'%0A',$fieldValue);
			
			$response = $this->request->put(
				"https://api.trello.com/1/cards/$cardId?key={$this->api_key}&token={$this->api_token}&$fieldName=$fieldValue"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Ge specific field on the card
	function getCardField($cardId, $field){
		try{
			$response = $this->request->get(
				"https://api.trello.com/1/cards/$cardId/$field",
				$this->headers,
				$this->query
			);
			
			return $response->body->_value;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Remove member from a card
	function removeCardMember($cardId, $memberId=''){
		try{
			if(empty($memberId))  $memberId = $this->member_id;
			
			$response = $this->request->delete(
				"https://api.trello.com/1/cards/$cardId/idMembers/$memberId?key={$this->api_key}&token={$this->api_token}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Move card to another list
	function moveCardToList($cardId, $listId){
		try{
			$response = $this->request->put(
				"https://api.trello.com/1/cards/$cardId?key={$this->api_key}&token={$this->api_token}&idList=$listId"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Get checklist on a card
	function getCardChecklist($cardId){
		try{
			$response = $this->request->get(
				"https://api.trello.com/1/cards/$cardId/checklists",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Create a checklist on a card
	function createChecklist($cardId, $name){
		try{
			$query 			= $this->query;
			$query['name']	= $name;
			$query['pos']	= 'top';
			
			$response = $this->request->post(
				"https://api.trello.com/1/cards/$cardId/checklists",
				$this->headers,
				$query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Add checklist options
	function addChecklistItem($checklistId,$itemName){
		try{
			$query				= $this->query;
			$query['name']		= $itemName;
			$query['checked']	= true;
			
			$response = $this->request->post(
				"https://api.trello.com/1/checklists/$checklistId/checkItems",
				$this->headers,
				$query
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Make an option checked
	function checkChecklistItem($cardId, $checkItemId){
		try{
			$response = $this->request->put(
				"https://api.trello.com/1/cards/$cardId/checkItem/$checkItemId?key={$this->api_key}&token={$this->api_token}&state=complete",
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//add or update checlist options
	function changeChecklistOption($cardId, $checklist, $itemName){
		$exists			= false;
		
		//Loop over all the checklist items
		foreach($checklist->checkItems as $item){
			//if not checked we should process it
			if($item->name == $itemName){
				if($item->state == 'incomplete'){
					//check the item
					$this->checkChecklistItem($cardId, $item->id);
					return 'Updated';
				}
				//Item already exists and is already checked
				return "Exists already";
			}
		}
		
		if($exists == false){
			$this->addChecklistItem($checklist->id, $itemName);
			return "Created";
		}
		
		return 'Not found';
	}
	
	//Add comment to card
	function addComment($cardId, $comment){
		try{
			$query			= $this->query;
			$query['text']	= $comment;
			$response = $this->request->post(
				"https://api.trello.com/1/cards/$cardId/actions/comments",
				$this->headers,
				$query
			);
			
			return $response;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Get cover image
	function getCoverImage($cardId){
		try{
			$query 				= $this->query;
			$query['filter'] 	= 'cover';
			
			$response = $this->request->get(
				"https://api.trello.com/1/cards/$cardId/attachments?key={$this->api_key}&token={$this->api_token}",
			);
			
			return $response->body[0]->url;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//get info about the user a token belongs to
	function getTokenInfo(){
		try{
			$response = $this->request->get(
				'https://api.trello.com/1/tokens/'.$this->api_token.'/member',
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//list webhooks
	function getWebhooks(){
		try{
			$response = $this->request->get(
				"https://api.trello.com/1/tokens/{$this->api_token}/webhooks",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//create a new webhooks
	function createWebhook($url, $modelid, $description=''){
		try{
			$query 					= $this->query;
			$query['callbackURL']	= $url;
			$query['idModel']		= $modelid;
			$query['description']	= $description;

			$response = $this->request->post(
				'https://api.trello.com/1/webhooks/',
				$this->headers,
				$query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Change webhook id
	function changeWebhookId($webhookid, $pageId){
		try{
			$url			= get_page_link($pageId);
			$trelloUserId = $this->getTokenInfo()->id;
			$response 		= $this->request->put(
				"https://api.trello.com/1/webhooks/$webhookid?key={$this->api_key}&token={$this->api_token}&callbackURL={$url}&idModel=$trelloUserId"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//Delete a webhook
	function deleteWebhook($webhookId){
		try{
			$response = $this->request->delete(
				"https://api.trello.com/1/webhooks/$webhookId?key={$this->api_key}&token={$this->api_token}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
	
	//delete all webhooks
	function deleteAllWebhooks(){
		$webhooks = $this->getWebhooks();
		
		$result = [];
		foreach($webhooks as $webhook){
			$result[] = $this->deleteWebhook($webhook->id);
		}
		
		return $result;
	}
	
	function searchCardItem($cardId, $searchKey){
		try{
			$query				= $this->query;
			$query['query']		= $searchKey;
			$query['idCards']	= $cardId;
			
			$response = $this->request->get(
				'https://api.trello.com/1/search',
				$this->headers,
				$query
			);
			
			return $response->body->cards;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\print_array($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\print_array($e->getMessage());
			return [];
		}
	}
}