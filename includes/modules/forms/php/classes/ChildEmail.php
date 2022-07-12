<?php
namespace SIM\FORMS;
use SIM;
use SIM\ADMIN;

class ChildEmail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('child_reminder', MODULE_SLUG);

        $this->addUser($user);

        $this->defaultSubject   = "Please update the personal information of %first_name% on the %site_name% website";

        $this->defaultMessage    = "Dear %last_name% family,<br><br>";
        $this->defaultMessage 	.= "Some of the personal information of %first_name% on %site_name% needs to be updated.<br>";
		$this->defaultMessage 	.= 'Please click on the items below to update the data:';
    }
}
