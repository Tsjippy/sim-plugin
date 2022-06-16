<?php
namespace SIM\SIMNIGERIA;
use SIM;
use SIM\ADMIN;

class ContactList extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('contactlist', MODULE_SLUG);

        $this->addUser($user);

        $this->defaultSubject    = "Contact list";

        $this->defaultMessage    = 'Dear all,<br><br>';
        $this->defaultMessage   .= 'Attached you can find a list off all missionary contact info.<br>';
        $this->defaultMessage   .= 'This information is for SIM use only. Do not share this informations with others.<br>';
        $this->defaultMessage   .= 'Visit <a href="%account_page%"> the %site_name% website</a> if your contactinfo is not listed or not up to date.';
    }
}