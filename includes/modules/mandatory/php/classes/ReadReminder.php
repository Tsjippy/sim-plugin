<?php
namespace SIM\MANDATORY;
use SIM;
use SIM\ADMIN;

class ReadReminder extends ADMIN\MailSetting{

    public $user;
    public $html;

    public function __construct($user, $html='') {
        // call parent constructor
		parent::__construct( 'read_reminder', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%pages_to_read%']    = $html;

        $this->defaultSubject    = "Please read some website content";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= '%pages_to_read%';
        $this->defaultMessage   .= '<br>';
        $this->defaultMessage   .= 'Please read it as soon as possible.<br>';
        $this->defaultMessage   .= 'Mark as read by clicking on the button on the bottom of each page';
    }
}
