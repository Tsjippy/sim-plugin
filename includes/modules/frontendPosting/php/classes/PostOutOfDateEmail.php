<?php
namespace SIM\FRONTENDPOSTING;
use SIM;
use SIM\ADMIN;

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
