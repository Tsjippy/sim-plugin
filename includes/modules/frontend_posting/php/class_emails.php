<?php
namespace SIM\FRONTEND_POSTING;
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

class PostOutOfDateEmail extends ADMIN\MailSetting{

    public $user;
    public $postTitle;
    public $pageAge;
    public $url;

    public function __construct($user, $postTitle='', $pageAge='', $url='') {
        // call parent constructor
		parent::__construct('page_age', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%post_title%']     = $postTitle;
        $this->replaceArray['%page_age%']       = $pageAge;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Please update the contents of '%post_title%'";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "It has been %page_age% days since the page with title '%post_title%' on %site_url% has been updated.<br>";
		$this->defaultMessage   .= "Please follow <a href='%url%'>this link</a> to update it.";
    }
}