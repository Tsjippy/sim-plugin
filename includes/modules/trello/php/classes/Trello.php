<?php
namespace SIM\TRELLO;
use SIM;

//Create token: https://trello.com/1/authorize?expiration=never&scope=read,write,account&response_type=token&name=Server%20Token&key=2fbe97b966e413fe5fc5eac889e2146a
//https://developer.atlassian.com
//https://developer.atlassian.com/cloud/trello/guides/rest-api/api-introduction/
//https://developer.atlassian.com/cloud/trello/rest/

// Get member info: https://api.trello.com/1/members/harmsenewald

require( __DIR__  . '/../../lib/vendor/autoload.php');

class Trello{
	function __construct(){
		global $Modules;
		$this->settings		= $Modules[MODULE_SLUG];

		$this->request		= new \Unirest\Request();
		
		//Initialization
		$this->apiKey 		= $this->settings['key'];
		$this->apiToken		= $this->settings['token'];
		$this->query 		= array(
			'key' 	=> $this->apiKey,
			'token' => $this->apiToken
		);
		$this->headers = array(
		  'Accept' => 'application/json'
		);
		$this->memberId	= $this->getTokenInfo()->id;
	}

	/**
	 * Get active boards
	 * 
	 * @return	array	boards
	 */
	function getBoards(){
		try{
			if (!isset($this->boards)){
				$this->boards		= [];
				$query				= $this->query;
				$query['filter']	= 'open';
				$response			= $this->request->get(
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			$errorResult = $e->getMessage();
			SIM\printArray($errorResult);
			return [];
		}
	}
	
	/**
	 * Get all the lists for all the active boards
	 * 
	 * @return array	The lists
	 */
	function getAllLists(){
		$this->getBoards();
		
		if(!isset($this->lists)){
			$this->lists = [];
		}
		
		foreach($this->boards as $boardId){
			$this->getBoardList($boardId);
		}
		
		return $this->lists;
	}
	
	/**
	 * Get lists for specific board
	 * 
	 * @param	int	$boardId	the id oft the board you want the lists for
	 * 
	 * @param	array			The lists
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Get all cards from a list
	 * 
	 * @param	int		$listId		The id of the list
	 * 
	 * @return	array				The cards
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Get a card
	 * 
	 * @param	int		$cardId 	the id of an card
	 * 
	 * @return	array				the card
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * update a field on a card
	 * 
	 * @param	int		$cardId 	the id of an card
	 * @param	string	$fieldname	The field to update
	 * @param	string	$fieldValue	The new value
	 * 
	 * @return	object				Result
	 */
	function updateCard($cardId, $fieldName, $fieldValue){
		try{
			//Make sure linebreaks stay there
			$fieldValue = str_replace("\n",'%0A',$fieldValue);
			
			$response = $this->request->put(
				"https://api.trello.com/1/cards/$cardId?key={$this->apiKey}&token={$this->apiToken}&$fieldName=$fieldValue"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Get specific field on the card
	 * 
	 * @param	int		$cardId 	the id of an card
	 * @param	string	$field		The field 
	 * 
	 * @param	object				Response
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Remove member from a card
	 * @param	int		$cardId 	the id of an card
	 * @param	int		$memberId	The member to remove
	 * 
	 * @param	object				Response
	 */
	function removeCardMember($cardId, $memberId){
		try{			
			$response = $this->request->delete(
				"https://api.trello.com/1/cards/$cardId/idMembers/$memberId?key={$this->apiKey}&token={$this->apiToken}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Move card to another list
	 * 
	 * @param	int		$cardId 	the id of an card
	 * @param	int		$listId		The id of the lis to move to
	 * 
	 * @param	object				Response
	 */
	function moveCardToList($cardId, $listId){
		try{
			$response = $this->request->put(
				"https://api.trello.com/1/cards/$cardId?key={$this->apiKey}&token={$this->apiToken}&idList=$listId"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Get checklist on a card
	 * 
	 * @param	int		$cardId 	the id of an card
	 * 
	 * @return	array				The checklist
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Create a checklist on a card
	 * 
	 * @param	int		$cardId 	the id of an card
	 * @param	string	$name		Name for the checklist
	 * 
	 * @return	object				The reponse
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Add checklist options
	 * 
	 * @param	int		$checklistId	The id of the checklist
	 * @param	string	$itemName		The name of the item to add
	 * 
	 * @return object					Response
	 */
	function addChecklistItem($checklistId, $itemName){
		try{
			$query				= $this->query;
			$query['name']		= $itemName;
			$query['checked']	= true;
			
			return $this->request->post(
				"https://api.trello.com/1/checklists/$checklistId/checkItems",
				$this->headers,
				$query
			);
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Make an option checked
	 * 
	 * @param	int		$cardId			The id of the card
	 * @param	int		$checkItemId	The id of the checklist item
	 * 
	 * @return object					Response
	 */
	function checkChecklistItem($cardId, $checkItemId){
		try{
			return $this->request->put(
				"https://api.trello.com/1/cards/$cardId/checkItem/$checkItemId?key={$this->apiKey}&token={$this->apiToken}&state=complete",
			);
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * add or update checlist options
	 * 
	 * @param	int		$cardId		the card id
	 * @param	object	$checklist	The checklist
	 * @param	string	$itemNam	THe name
	 * 
	 * @return bool					True on creation false if already exists
	 */
	function changeChecklistOption($cardId, $checklist, $itemName){
		$exists			= false;
		
		//Loop over all the checklist items
		foreach($checklist->checkItems as $item){
			//if not checked we should process it
			if($item->name == $itemName){
				if($item->state == 'incomplete'){
					//check the item
					$this->checkChecklistItem($cardId, $item->id);
					return true;
				}
				//Item already exists and is already checked
				return false;
			}
		}
		
		if(!$exists){
			$this->addChecklistItem($checklist->id, $itemName);
			return "Created";
		}
		
		return 'Not found';
	}
	
	/**
	 * Add comment to card
	 * 
	 * @param	int		$cardId		the card id
	 * @param	string	$comment	The comment
	 * 
	 * @return object					Response
	 */
	function addComment($cardId, $comment){
		try{
			$query			= $this->query;
			$query['text']	= $comment;
			
			return $this->request->post(
				"https://api.trello.com/1/cards/$cardId/actions/comments",
				$this->headers,
				$query
			);
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Get cover image
	 * 
	 * @param	int		$cardId		the card id
	 * 
	 * @return	string				The url
	 */
	function getCoverImage($cardId){
		try{
			$query 				= $this->query;
			$query['filter'] 	= 'cover';
			
			$response = $this->request->get(
				"https://api.trello.com/1/cards/$cardId/attachments?key={$this->apiKey}&token={$this->apiToken}",
			);
			
			return $response->body[0]->url;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * get info about the user a token belongs to
	 *
	 * @return object					Response
	 */
	function getTokenInfo(){
		try{
			$response = $this->request->get(
				'https://api.trello.com/1/tokens/'.$this->apiToken.'/member',
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * list webhooks
	 * 
	 * @return object					Response
	 */
	function getWebhooks(){
		try{
			$response = $this->request->get(
				"https://api.trello.com/1/tokens/{$this->apiToken}/webhooks",
				$this->headers,
				$this->query
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * create a new webhooks
	 * 
	 * @param	string	$url		The webhook url
	 * @param	int		$modelId	The trello id
	 * @param	string	$description	Optional description
	 * 
	 * @return object					Response
	 */
	function createWebhook($url, $modelId, $description=''){
		try{
			$query 					= $this->query;
			$query['callbackURL']	= $url;
			$query['idModel']		= $modelId;
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * Change webhook id
	 * 
	 * @param	int		$webhookId	The id of the webhook to change
	 * @param	int		$pageId		WP_Post id
	 * 
	 * @return object					Response
	 */
	function changeWebhookId($webhookid, $pageId){
		try{
			$url			= get_page_link($pageId);
			$trelloUserId = $this->getTokenInfo()->id;
			$response 		= $this->request->put(
				"https://api.trello.com/1/webhooks/$webhookid?key={$this->apiKey}&token={$this->apiToken}&callbackURL={$url}&idModel=$trelloUserId"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	//Delete a webhook
	function deleteWebhook($webhookId){
		try{
			$response = $this->request->delete(
				"https://api.trello.com/1/webhooks/$webhookId?key={$this->apiKey}&token={$this->apiToken}"
			);
			
			return $response->body;
		}catch(\GuzzleHttp\Exception\ClientException $e){
			$result = json_decode($e->getResponse()->getBody()->getContents());
			$errorResult = $result->detail."<pre>".print_r($result->errors,true)."</pre>";
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
	
	/**
	 * delete all webhooks
	 * 
	 * @return object					Response
	 */
	function deleteAllWebhooks(){
		$webhooks = $this->getWebhooks();
		
		$result = [];
		foreach($webhooks as $webhook){
			$result[] = $this->deleteWebhook($webhook->id);
		}
		
		return $result;
	}
	
	/**
	 * @param	int		$cardId		the card id
	 * @param	string	$searchKey	The search param
	 * 
	 * @return	array				The found cards
	 */
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
			SIM\printArray($errorResult);
			return [];
		}catch(\Exception $e) {
			SIM\printArray($e->getMessage());
			return [];
		}
	}
}