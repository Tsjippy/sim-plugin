<?php
namespace SIM\BANKING;
use SIM;

class AccountStatement{
	public $postiePost;
	public $post;
	public $loginName;
	public $postName;
	public $postDate;
	public $accountId;
	public $users;
	public $statementNames;
	public $user;

	public function __construct($postiePost){
		$this->post			= $postiePost;
	}

	/**
	 * Checks if the e-mail contains an account statement and if so stores it
	 *
	 * @return	bool	true if id found, false otherwise
	 */
	public function checkIfStatement(){
		SIM\printArray($this->post);

		if(str_contains($this->post['post_title'], 'Worker Account Statement - Nigeria')){
			$this->user	= get_userdata($this->post['post_author']);
	
			if(!$this->user){
				return false;
			}

			if($this->user && $this->findAccountStatement()){

				$this->storeAccountStatement();

				return true;
			}
		}

		return false;
	}

    /**
     * Checks the e-mail attachments with a file with the name 'account-statement'
     */
	public function findAccountStatement(){
		$found= false;

		//Find the attachment url
		$attachments = get_attached_media("", $this->post['ID']);

		//Loop over all attachments
		foreach($attachments as $attachment){
			$fileName = $attachment->post_name;
			
			//If this attachment is the account statement
			if (str_contains($fileName, 'sim-nigeria_w') && str_contains($fileName, strtolower($this->user->first_name)) && str_contains($fileName, strtolower($this->user->last_name))) {
				$found	= true;
				break;
			}
		}

		if($found){
			$filePath = get_attached_file($attachment->ID);

			if(file_exists($filePath)){
				//Read the contents of the attachment
				$result					= SIM\PDFTOEXCEL\readPdf($filePath,  wp_upload_dir()['basedir'].'/private/.csv');

				$datestring				= str_replace('/', '-', $result['rows'][0][0]);

				$this->postDate			= date_create($datestring); // first cell of the first row should be a date
			}
		}

		if(!$this->postDate){
			return false;
		}
		
		//Create a string based on the date
		$datestring				= date_format($this->postDate, "Y-m");
		
		//move to account_statements folder
		$append					= $this->user->user_login;
		if(!str_contains($filePath, $datestring)){
			$append				.= "-$datestring";
		}

		// pdf
		$newPath				= STATEMENT_FOLDER."$append-".basename($filePath);
		$this->statementNames	= ["$append-".basename($filePath)];
		rename($filePath, $newPath);

		// csv
		$newPath				= STATEMENT_FOLDER."$append-".pathinfo($result['filepath'])['basename'];
		$this->statementNames[]	= "$append-".pathinfo($result['filepath'])['basename'];
		rename($result['filepath'], $newPath);

		//remove the attachment as it should be private
		wp_delete_attachment($attachment->ID, true);

		return true;
	}

    /**
     * Adds the found statment to the usermeta
     */
	public function storeAccountStatement(){
		$users		= [$this->user];

		$partnerId	= SIM\hasPartner($this->user->ID);
		if(is_numeric($partnerId)){
			$users[]	= get_userdata($partnerId);
		}

		$year = date_format($this->postDate, "Y");
		foreach($users as $user){
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
			$accountStatements[$year][date_format($this->postDate, "F")] = $this->statementNames;
			
			//Update the list
			update_user_meta($user->ID, "account_statements", $accountStatements);
			
			// Get account page
			$accountUrl		= SIM\ADMIN\getDefaultPageLink('usermanagement', 'account_page');
            
			if($accountUrl){
				$message	= "See it here: \n\n$accountUrl";
			}else{
				$message	= '';
			}

			//Send signal message
			$url	= SIM\pathToUrl(STATEMENT_FOLDER.$this->statementNames[0]);
			SIM\trySendSignal(
				"Hi $user->first_name,\n\nThe account statement for the month ".date_format($this->postDate, 'F')." just got available on the website. $message\n\nDirect url to the statement:\n$url",
				$user->ID
			);
		}
	}
}