<?php
namespace SIM\COMMENTS;
use SIM;
use SIM\ADMIN;

class ApprovedCommentEmail extends ADMIN\MailSetting{

    public $commentData;

    public function __construct($commentData) {
        // call parent constructor
		parent::__construct('approved_comment', MODULE_SLUG);

        $postId                 = $commentData['comment_post_ID'];
        $postTitle              = get_the_title($postId);
        $authorId               = get_post_field('post_author', $postId);
        $author                 = get_userdata($authorId);
        $replyLink              = get_permalink( $postId ).'#'.$commentData['commentID'];

        $this->addUser($author);

        $this->replaceArray['%comment_author%']     = $commentData['comment_author'];
        $this->replaceArray['%comment_content%']    = $commentData['comment_content'];
        $this->replaceArray['%post_author%']        = $author;
        $this->replaceArray['%post_title%']         = $postTitle;
        $this->replaceArray['%reply_link%']         = $replyLink;

        $this->defaultSubject   = "A new comment has been made on %post_title%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= "%comment_author% just left a comment on %post_title%.<br>";
		$this->defaultMessage 	.= 'This is what the comment sais:<br>';
        $this->defaultMessage 	.= '%comment_content%<br><br>';
        $this->defaultMessage 	.= "You can reply to this comment using <a href='%reply_link%'>this link</a> if you want.";
    }
}
