<?php
namespace SIM\FRONTENDPOSTING;
use SIM;
use SIM\ADMIN;

class PendingPostEmail extends ADMIN\MailSetting{

    public $user;
    public $authorName;
    public $actionText;
    public $postType;
    public $url;

    public function __construct($user, $authorName='', $actionText='', $postType='', $url='') {
        // call parent constructor
		parent::__construct('pending_post', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%author_name%']    = $authorName;
        $this->replaceArray['%action_text%']    = $actionText;
        $this->replaceArray['%post_type%']      = $postType;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Please review a %post_type%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "%author_name% just %action_text% a %post_type%. Please review it <a href='%url%'>here</a>";
    }
}
