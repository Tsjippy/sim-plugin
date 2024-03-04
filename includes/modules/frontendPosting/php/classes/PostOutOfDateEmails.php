<?php
namespace SIM\FRONTENDPOSTING;
use SIM;
use SIM\ADMIN;

class PostOutOfDateEmails extends ADMIN\MailSetting{

    public $user;
    public $postTitle;
    public $pageAge;
    public $url;

    public function __construct($user, $postTitle='', $pageAge='', $url='') {
        // call parent constructor
		parent::__construct('page_age_multiple', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%post_title%']     = $postTitle;
        $this->replaceArray['%page_age%']       = $pageAge;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Please update some of the website contents";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "It has been long since several pages have been updated.<br>";
		$this->defaultMessage   .= "Please follow the links below to updated them:<br>";
    }
}
