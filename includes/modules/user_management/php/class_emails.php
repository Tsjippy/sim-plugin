<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use SIM\ADMIN;

class AccountCreatedMail extends ADMIN\MailSetting{

    public $user;
    public $loginUrl;

    public function __construct($user, $loginUrl='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'account_created';

        $this->replaceArray['%login_url%']    = $loginUrl;
        $this->replaceArray['%user_name%']    = $user->user_login;

        $this->defaultSubject    = 'We have created an account for you on %site_name%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= "We have created an account for you on  %site_name%.<br>";
		$this->defaultMessage 	.= "Please set a password using this <a href='%login_url%'>link</a>.<br>";
        $this->defaultMessage 	.= 'Your username is: %user_name%.<br>';
        $this->defaultMessage 	.= 'If you have any problems, please contact us by replying to this e-mail.';
    }
}

class AccountApproveddMail extends ADMIN\MailSetting{

    public $user;
    public $loginUrl;

    public function __construct($user, $loginUrl='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'account_approved';

        $this->replaceArray['%login_url%']    = $loginUrl;
        $this->replaceArray['%user_name%']    = $user->user_login;

        $this->defaultSubject    = 'We have approved your account on %site_name%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= "We have approved your account on  %site_name%.<br>";
        $this->defaultMessage 	.= "You can now login on %site_url%.<br>";
        $this->defaultMessage 	.= 'Your username is: %user_name%.<br>';
		$this->defaultMessage 	.= "If you have not yet setup a password you can do so using this <a href='%login_url%'>link</a>.<br>";
        $this->defaultMessage 	.= 'If you have any problems, please contact us by replying to this e-mail.';
    }
}

class AccountExpiryMail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'account_expiry';

        
		$expiryDate		                        = date("d-m-Y", strtotime(" +1 months"));
        $this->replaceArray['%expiry_date%']    = $expiryDate;

        $this->defaultSubject    = 'Your account will expire on %expiry_date%';

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage 	.= 'This is to inform you that your account on %site_name% will expire on %expiry_date%.<br>';
		$this->defaultMessage 	.= 'If you think this should be extended you can contact the STA coordinator (cc).';
    }
}

class AccountRemoveMail extends ADMIN\MailSetting{

    public $user;

    public function __construct($user) {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'account_removal';

        $this->replaceArray['%account_page%']    = get_permalink(SIM\getModuleOption($this->moduleSlug, 'account_page'));

        $this->defaultSubject    = 'Your account on %site_name% has been deleted';

        $this->defaultMessage    = 'Dear %full_name%,<br><br>';
        $this->defaultMessage   .= 'This is to inform you that your account on %site_name% has been deleted.<br>';
        $this->defaultMessage   .= 'You are no longer able to login.';
    }
}

class AdultVaccinationWarningMail extends ADMIN\MailSetting{

    public $user;
    public $reminderHtml;

    public function __construct($user, $reminderHtml='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'adult_vacc_warning';

        $this->replaceArray['%reminder_html%']    = $reminderHtml;

        $this->defaultSubject    = "Please renew your vaccinations";

        $this->defaultMessage    = 'Dear %first_name%,<br><br>';
        $this->defaultMessage   .= '%reminder_html%<br>';
        $this->defaultMessage   .= 'Please renew them as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}

class ChildVaccinationWarningMail extends ADMIN\MailSetting{

    public $user;
    public $reminderHtml;

    public function __construct($user, $reminderHtml='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'child_vacc_warning';

        $this->replaceArray['%reminder_html%']    = $reminderHtml;

        $this->defaultSubject    = "Please renew the vaccinations of %first_name%";

        $this->defaultMessage    = 'Dear %last_name% family,<br><br>';
        $this->defaultMessage   .= '%reminder_html%<br>';
        $this->defaultMessage   .= 'Please renew them as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}

class GreenCardReminderMail extends ADMIN\MailSetting{

    public $user;
    public $reminder;

    public function __construct($user, $reminder='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'greencard_warning';

        $this->replaceArray['%reminder%']    = $reminder;

        $this->defaultSubject    = "Please renew your greencard";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "%reminder%<br>";
        $this->defaultMessage   .= 'Please renew it as soon as possible.<br>';
        $this->defaultMessage   .= 'If you have any questions, just reply to this e-mail';
    }
}

class WeMissYouMail extends ADMIN\MailSetting{

    public $user;
    public $lastLogin;

    public function __construct($user, $lastLogin='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->moduleSlug        = 'user_management';
        $this->keyword           = 'miss_you';

        $this->replaceArray['%lastlogin%']    = $lastLogin;

        $this->defaultSubject    = "We miss you!";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage    = "We miss you! We haven't seen you since %lastlogin%<br>";
        $this->defaultMessage 	.= 'Please pay us a visit on <a href="%site_url%">%site_name%</a><br>';
    }
}