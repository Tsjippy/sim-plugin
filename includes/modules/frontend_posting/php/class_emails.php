<?php
namespace SIM\FRONTEND_POSTING;
use SIM;
use SIM\ADMIN;

class PendingPostEmail extends ADMIN\MailSetting{

    public $user;
    public $author_name;
    public $action_text;
    public $post_type;
    public $url;

    public function __construct($user, $author_name='', $action_text='', $post_type='', $url='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->replaceArray['%author_name%']    = $author_name;
        $this->replaceArray['%action_text%']    = $action_text;
        $this->replaceArray['%post_type%']      = $post_type;
        $this->replaceArray['%url%']            = $url;

        $this->moduleSlug        = 'frontend_posting';
        $this->keyword           = 'pending_post';

        $this->defaultSubject    = "Please review a %post_type%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "%author_name% just %action_text% a %post_type%. Please review it <a href='%url%'>here</a>";
    }
}

class PostOutOfDateEmail extends ADMIN\MailSetting{

    public $user;
    public $post_title;
    public $page_age;
    public $url;

    public function __construct($user, $post_title='', $page_age='', $url='') {
        // call parent constructor
		parent::__construct();

        $this->addUser($user);

        $this->replaceArray['%post_title%']     = $post_title;
        $this->replaceArray['%page_age%']       = $page_age;
        $this->replaceArray['%url%']            = $url;

        $this->moduleSlug        = 'frontend_posting';
        $this->keyword           = 'page_age';

        $this->defaultSubject    = "Please update the contents of '%post_title%'";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "It has been %page_age% days since the page with title '%post_title%' on %site_url% has been updated.<br>";
		$this->defaultMessage   .= "Please follow <a href='%url%'>this link</a> to update it.";
    }
}