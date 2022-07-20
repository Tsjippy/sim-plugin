<?php
namespace SIM\ADMIN;
use SIM;

abstract class MailSetting{
    public $keyword;

    /**
     * Initiates the class
     * 
     * @param   string  $keyword    The keyword to use in the settings array
     */
    protected function __construct($keyword, $moduleSlug) {
        $this->replaceArray     = [
            '%site_url%'    => SITEURL,
            '%site_name%'   => SITENAME
        ];

        $this->keyword          = $keyword;
        $this->moduleSlug       = $moduleSlug;
        $this->subjectKey       = $this->keyword."_subject";
        $this->messageKey       = $this->keyword."_message";
        $this->subject          = '';
        $this->message          = '';

        $emailSettings          = SIM\getModuleOption($this->moduleSlug, 'emails');
        if($emailSettings){
            if(isset($emailSettings[$this->subjectKey])){
                $this->subject  = $emailSettings[$this->subjectKey];
            }

            if(isset($emailSettings[$this->messageKey])){
                $this->message  = $emailSettings[$this->messageKey];
            }
        }
        
    }

    /**
     * Add replacements for user names
     * 
     * @param object    $user   WP_User
     */
    protected function addUser($user){
        if(!empty($user)){
            $this->replaceArray['%first_name%']  = $user->first_name;
            $this->replaceArray['%last_name%']   = $user->last_name;
            $this->replaceArray['%full_name%']   = $user->display_name;
        }
    }

    /**
     * Replaces all places holders in subject and message
     */
    public function filterMail(){
        $this->subject  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->subject);
        $this->message  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->message);
    }

    /**
     * Prints the e-mail subject input
     */
    private function printSubjectInput(){
        $subject  = $this->subject;
        if(empty($subject)){
            $subject  = $this->defaultSubject;
        }

        ?>
        <label>
            E-mail subject:<br>
            <input type='text' name="emails[<?php echo $this->keyword;?>_subject]" value="<?php echo $subject;?>" style="width:100%;">
        </label>
        <br>
        <?php
    }

    /**
     * Prints the e-mail message input to screen
     */
    private function printMessageInput(){
        $message  = $this->message;
        if(empty($message)){
            $message  = $this->defaultMessage;
        }

        ?>
        <label>
            E-mail content
            <?php
            $settings = array(
                'wpautop'                   => false,
                'media_buttons'             => false,
                'forced_root_block'         => true,
                'convert_newlines_to_brs'   => true,
                'textarea_name'             => "emails[$this->messageKey]",
                'textarea_rows'             => 10
            );

            echo wp_editor(
                $message,
                $this->messageKey,
                $settings
            );
            ?>
        </label>
        <?php
    }

    /**
     * Prints both the subject and the content inputs to screen
     * 
     * @param   array   $settings   The module settings array
     */
    public function printInputs($settings){
        $this->printSubjectInput($settings);

        $this->printMessageInput($settings);
    }

    /**
     * Prints all available placeholders to screen
     */
    public function printPlaceholders(){
        ?>
        <p>
            You can use placeholders in your inputs.<br>
		    These ones are available (click on any of them to copy):<br>
            <?php
            foreach(array_keys($this->replaceArray) as $placeholder){
                echo "<span class='placeholders' title='Click to copy'>$placeholder</span>";
            }
        echo '</p>';
    }
}