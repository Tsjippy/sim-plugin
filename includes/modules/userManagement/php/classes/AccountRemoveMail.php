<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class AccountRemoveMail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct('account_removal', MODULE_SLUG);

        $this->addUser($user);

        $this->replaceArray['%account_page%']    = SIM\ADMIN\getDefaultPageLink($this->moduleSlug, 'account_page');

        $this->defaultSubject    = 'Your account on %site_name% has been deleted';

        $this->defaultMessage    = 'Dear %full_name%,<br><br>';
        $this->defaultMessage   .= 'This is to inform you that your account on %site_name% has been deleted.<br>';
        $this->defaultMessage   .= 'You are no longer able to login.';
    }
}

