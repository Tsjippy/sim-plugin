<?php
namespace SIM;

class UserPageLinks {
	public $foundUsers;
	public $string;
	public $replace;
	public $skipHyperlinks;
	public $displayNames;
	public $coupleNames;
	public $family;
	private	$lastMatch;

    public function __construct(&$string, $replace){
        $this->family			= new FAMILY\Family();
        $this->foundUsers		= [];
		$this->replace			= $replace;

        // get all useraccounts
        $users 			        = getUserAccounts(false, false);

        // Clean up the string
        $this->string			= str_replace(['&amp;', chr(194), chr(160)], ['&', ' '], $string);
        $this->skipHyperlinks   = $replace;

        $this->displayNames	    = [];
        $this->coupleNames	    = [];

		$this->lastMatch		= [0, 0];

        foreach($users as $user){
            // store displayname
            $this->displayNames[$user->ID]	= strtolower($user->display_name);
            $partner			= $this->family->getPartner($user->ID, true);

            // store firstname and partner firstname in case last name is omitted
            if($partner){
                $this->coupleNames[$user->ID]	= strtolower("$user->first_name & $partner->first_name");
            }
        }

		$this->findUsers();
    }

    public function replaceWithHyperlink($match){
		if(!isset($match[1])){
			return $match;
		}

		// this match is withing an already succesfull match
		if($match[1][1] > $this->lastMatch[0] && ($match[1][1] + strlen(trim($match[1][0]))) <= $this->lastMatch[1]){
			return '';
		}

		// the full name catch
		$name	= trim($match[1][0]);

		// The first name
		if(!empty($match[2])){
			$firstName	= trim($match[2][0]);

			// The second name
			$secondName	= trim($match[4][0]);

			// Last name
			if(!empty($match[5][0])){
				$lastName	= trim($match[5][0]);
			}else{
				$lastName	= '';
			}
		}

		/**
		 * Just one person found
		 */
		if(count($match) < 3){
			// check if single user
			$userId				= array_search(strtolower($name), $this->displayNames);

			// User not found
			if($userId){

				$this->foundUsers[]	= $userId;

				if($this->replace){
					$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];

					return $this->userPageLink($userId, $name);
				}
			}
		}

		 /**
		 * Two full names found
		 */
		elseif(count($match) == 6 && !empty($match[3][0])){
			$userId1		= array_search(strtolower($firstName), $this->displayNames);
			$userId2		= array_search(strtolower($secondName), $this->displayNames);

			$returnString	= '';

			// Add the names to the array
			if($userId1){
				$this->foundUsers[]		= $userId1;

				if($this->replace){
					$returnString	.= $this->userPageLink($userId1, $firstName);
				}
			}elseif($this->replace){
				$exploded		= explode($secondName, $name);

				$returnString	.= $exploded[0];
			}

			if($userId2){
				$this->foundUsers[]		= $userId2;

				if($this->replace){
					$returnString	.= $this->userPageLink($userId2, $secondName);
				}
			}elseif($this->replace){
				$exploded		= explode($firstName, $name);

				$returnString	.= $exploded[1];
			}

			if(!empty($returnString)){
				$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];
				return $returnString;
			}
		}
		
		 /**
		 * Couple name found
		 */
		elseif($lastName != $secondName){
			$userId1	= array_search(strtolower("$firstName $lastName"), $this->displayNames);
			$userId2	= array_search(strtolower($secondName), $this->displayNames);

			// Both users found, replace the couple string with an link
			if($userId1 && $userId2){
				$this->foundUsers[]		= $userId1;

				if($this->replace){
					$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];
					return $this->userPageLink($userId1, $name);
				}
			}
			// Only the first name is found
			elseif($userId1){
				// Add to the array
				$this->foundUsers[]		= $userId1;

				if($this->replace){
					$exploded			= explode($firstName, $name);

					$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];
					return $this->userPageLink($userId1, $firstName).$exploded[1];
				}
			}
			// Only the second name is found
			elseif($userId2){
				// Add to the array
				$this->foundUsers[]		= $userId2;

				if($this->replace){
					$exploded			= explode($secondName, $name);

					$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];

					return $exploded[0].$this->userPageLink($userId2, $secondName);
				}
			}	
		 /**
		  * Two firstnames found 
		 */
		}elseif($lastName == $secondName){
			// Still not found lets try couples first names without last name

			$name	= trim($name);

			// check if mentioned as a couple without lastname
			$userId1	= array_search(str_replace(' and ', ' & ', strtolower($name)), $this->coupleNames);

			// Add the names to the array
			if($userId1){
				$this->foundUsers[]		= $userId1;

				if($this->replace){
					$this->lastMatch	= [$match[1][1], $match[1][1] + strlen($name)];
					return $this->userPageLink($userId1, $match[1][0]);
				}
			}
		}

		return $match[1][0];
    }

    /**
     * Find users in a string
     *
     * @param	string	$string			The string to search in
     * @param	bool	$skipHyperlinks	Wheter we should skip users contained in a hyperlink
     *
     * @return	array					Array of with found user ids as index and an array of the text found and its start location as value
     */
    public function findUsers(){
        $foundUsers		= [];

        // get all useraccounts
        $users 			= getUserAccounts(false, false);

        // Clean up the string
        $this->string			= str_replace(['&amp;', chr(194), chr(160)], ['&', ' '], $this->string);

        $displayNames	= [];
        $coupleNames	= [];

        foreach($users as $user){
            // store displayname
            $displayNames[$user->ID]	= strtolower($user->display_name);
            $partner					= $this->family->getPartner($user->ID, true);

            // store firstname and partner firstname in case last name is omitted
            if($partner){
                $coupleNames[$user->ID]	= strtolower("$user->first_name & $partner->first_name");
            }
        }

        //Find names in content
        $oneWord	= "[A-Z][^\$%\^*£=~@\d\s:\[\],\"\.\)\(<]+\s?+";				// a word starting with a capital, ending with a space
        $singleRe	= "(?:$oneWord){2,}";										// two or more words starting with a capital after each other 
        $coupleRe	= "(?:(($oneWord)?$oneWord)+(?:&|and).(($oneWord)+))";	// one or more words starting with a capital letter followed by 'and' or '&' followed by one or more words starting with a capital letter 
        $familyRe	= "$oneWord\s(?:F|f)amily";
        if($this->skipHyperlinks){
            $skipHyperlinks	= "<a [^>]+?>.*?<\/a>(*SKIP)(*FAIL)|";
        }else{
            $skipHyperlinks	= "";
        }

        // check if prayer contains a single name or a couples name
        // We use look ahead (?=)to allow for overlap
        $re		= "/(*UTF8)$skipHyperlinks($coupleRe|$singleRe|$familyRe)/m";

		$this->string	= preg_replace_callback($re, [$this, 'replaceWithHyperlink'], $this->string, -1, $count, PREG_OFFSET_CAPTURE);

        return $foundUsers;
    }

    /**
     * Replace a users name with a link to the user page
     *
     * @param	string	$string		The string to scan for users
     *
     * @return	string				The string with userpagelinks
     */
    public function userPageLink($userId, $text){
		$privacyPreference = get_user_meta( $userId, 'privacy_preference', true );

		//only replace the name with a link if privacy allows
		if(!empty($privacyPreference['hide_name'])){
			return $text;
		}

		//Replace the name with a hyperlink
		$url	= maybeGetUserPageUrl($userId);
		if(!$url){
			return $text;
		}

		$name	= trim($text);
		$link	= "<a href=\"$url\">$name</a>";

        return $link;
	}
}