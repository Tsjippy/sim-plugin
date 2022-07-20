<?php
namespace SIM\BANKING;
use SIM;

class AccountStatement{
	public $postiePost;

	function __construct($postiePost){
		$this->post			= $postiePost;
		$this->loginName	= '';
		$this->postDate		= false;
	}

	/**
	 * Checks if the e-mail contains an account id and if so stores it
	 * 
	 * @return	bool	true if id found, false otherwise
	 */
	function checkIfStatement(){
		//Get the content of the email
		$content = $this->post['post_content'];
		
		//regex query to find the accountid of exactly 6 digits
		$re = '/.*AccountID:.*-(\d{6})-.*/';

		//execute regex
		preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
		
		//If there is no result return false
		if (is_array($matches) && count($matches[0]) == 2){

			// Get the account id
			$this->accountId	= trim($matches[0][1]);

			if($this->getLoginName() && $this->findAccountStatement()){

				$this->storeAccountStatement();

				return true;
			}
		}

		return false;
	}

	/**
	 * Find an adult who has the account id
	 * 
	 * @return	bool	true if user found, false otherwise
	 */
	function getLoginName(){
		//Change the user to the admin account otherwise get_users will not work
		wp_set_current_user(1);
				
		//Get all users with this financial_account_id meta key
		$this->users = get_users(
			array(
				'meta_query' => array(
					array(
						'key'		=> 'financial_account_id',
						'value'		=> $this->accountId,
						'compare'	=> 'LIKE'
					)
				)
			)
		);

		//Make sure we only continue with an adult
		$this->loginName	= '';
		foreach($this->users as $key=>$user){
			if (SIM\isChild($user->ID)){
				unset($this->users[$key]);
			}else{
				$this->loginName = $user->data->user_login;
				return true;
			}
		}
		
		return false;
	} 

    /**
     * Checks the e-mail attachments with a file with the name 'account-statement'
     */
	function findAccountStatement(){
		$found= false;

		//Find the attachment url
		$attachments = get_attached_media("", $this->post['ID']);

		//Loop over all attachments
		foreach($attachments as $attachment){
			$fileName = $attachment->post_name;
			
			//If this attachment is the account statement
			if (strpos($fileName, 'account-statement') !== false) {
				$found	= true;
				break;
			}
		}

		if($found){
			$filePath = get_attached_file($attachment->ID);

			if(file_exists($filePath)){
				//Read the contents of the attachment					
				$rtf	= file_get_contents($filePath); 

				//Regex to find the month it applies to
				$re = "/.*Date Range.*(\d{2}-[a-zA-Z]*-\d{4}).*/";
				//execute regex
				preg_match_all($re, $rtf, $matches, PREG_SET_ORDER, 0);

				if(isset($matches[0]) && !empty($matches[0][1])){
					//Create a date
					$this->postDate	= date_create($matches[0][1]);
				}
			}
		}

		if(!$this->postDate){
			return false;
		}
		
		//Create a string based on the date
		$datestring		= date_format($this->postDate, "Y-m");
		
		//move to account_statements folder
		$this->statementName	= "$this->loginName-$datestring-".basename($filePath);
		$newPath		= STATEMENT_FOLDER.$this->statementName;
		rename($filePath, $newPath);

		//remove the attachment as it should be private
		wp_delete_attachment($attachment->ID, true);

		return true;
	}

    /**
     * Adds the found statment to the usermeta
     */
	function storeAccountStatement(){
		$year = date_format($this->postDate, "Y");
		foreach($this->users as $user){
			if (SIM\isChild($user->ID)){
				continue;
			}
			
			//Get the account statement list
			$accountStatements				= get_user_meta($user->ID, "account_statements", true);

			//create the array if it does not exist
			if(!is_array($accountStatements)){
				$accountStatements			= [];
			}
			
			//Create the year array if it does not exist
			if(!isset($accountStatements[$year]) || (isset($accountStatements[$year]) && !is_array($accountStatements[$year]))){
				$accountStatements[$year]	= [];
			}
			
			//Add the new statement to the year array
			$accountStatements[$year][date_format($this->postDate, "F")] = $this->statementName;
			
			//Update the list
			update_user_meta($user->ID, "account_statements", $accountStatements);
			
			// Get account page
			$accountUrl		= SIM\ADMIN\getDefaultPageLink('account_page', 'usermanagement');
            
			if($accountUrl){
				$message	= "See it here: \n\n$accountUrl";
			}else{
				$message	= '';
			}

			//Send signal message
			$url	= SIM\pathToUrl(STATEMENT_FOLDER.$this->statementName);
			SIM\trySendSignal(
				"Hi $user->first_name,\n\nThe account statement for the month ".date_format($this->postDate, 'F')." just got available on the website. $message\n\nDirect url to the statement:\n$url",
				$user->ID
			);
		}
	}
}