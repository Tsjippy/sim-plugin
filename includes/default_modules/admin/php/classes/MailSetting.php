<?php
namespace SIM\ADMIN;
use SIM;

abstract class MailSetting{
    public $keyword;
    public $replaceArray;
    public $moduleSlug;
    public $subjectKey;
    public $messageKey;
    public $headerKey;
    public $subject;
    public $message;
    public $defaultSubject;
    public $defaultMessage;
    public $headers;

    /**
     * Initiates the class
     *
     * @param   string  $keyword    The keyword to use in the settings array
     */
    public function __construct($keyword, $moduleSlug) {
        $this->replaceArray     = [
            '%site_url%'    => SITEURL,
            '%site_name%'   => SITENAME
        ];

        $this->keyword          = $keyword;
        $this->moduleSlug       = $moduleSlug;
        $this->subjectKey       = $this->keyword."_subject";
        $this->messageKey       = $this->keyword."_message";
        $this->headerKey        = $this->keyword."_header";
        $this->subject          = '';
        $this->message          = '';
        $this->headers          = [];

        $emailSettings          = SIM\getModuleOption($this->moduleSlug, 'emails');
        if($emailSettings){
            if(isset($emailSettings[$this->subjectKey])){
                $this->subject  = $emailSettings[$this->subjectKey];
            }

            if(isset($emailSettings[$this->messageKey])){
                $this->message  = $emailSettings[$this->messageKey];
            }

            if(isset($emailSettings[$this->headerKey])){
                $this->headers  = $emailSettings[$this->headerKey];
            }
        }
    }

    /**
     * Add replacements for user names
     *
     * @param object    $user   WP_User
     */
    protected function addUser($user){
        if(empty($user)){
            return;
        }
        
        $this->replaceArray['%first_name%']  = $user->first_name;
        $this->replaceArray['%last_name%']   = $user->last_name;
        $this->replaceArray['%full_name%']   = $user->display_name;
    }

    /**
     * Replaces all places holders in subject, message and headers
     */
    public function filterMail(){
        if(empty($this->subject)){
            $this->subject  = $this->defaultSubject;
        }

        if(empty($this->message)){
            $this->message  = $this->defaultMessage;
        }

        $this->subject  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->subject);
        $this->message  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->message);
        foreach($this->headers as &$header){
            $header = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $header);
        }
    }

    /**
     * Prints the e-mail headers input
     */
    protected function printHeadersInput(){
        $headers    = $this->headers;
        if(empty($this->headers)){
            $headers    = [''];
        }
        ?>
        Any additional headers (see <a href='https://developer.wordpress.org/reference/functions/wp_mail/#using-headers-to-set-from-cc-and-bcc-parameters'>here</a>):<br>
        <div class="clone_divs_wrapper">
            <?php
            foreach($headers as $index=>$header){
                ?>
                <style>
                    .add, .remove{
                        max-width:50px;
                    }
                </style>
                <div class="clone_div" data-divid="<?php echo $index;?>">
                    <label name="Header" class=" formfield formfieldlabel">
                        <h4 class="labeltext">Header <?php echo $index + 1;?></h4>
                    </label>
                    <div class="buttonwrapper" style="width:100%; display: flex;">
                        <input type="text" name="emails[<?php echo $this->headerKey;?>][<?php echo $index;?>]" id="headers" class=" formfield formfieldinput" value="<?php echo $header;?>" style="width: 500px;">
                        <?php
                        if(count($this->headers) > 1){
                            ?>
                            <button type="button" class="remove button" style="flex: 1">-</button>
                            <?php
                        }
                        if(end($this->headers) == $header){
                            ?>
                            <button type="button" class="add button" style="flex: 1">+</button>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
        <br>
        <?php
    }

    /**
     * Prints the e-mail subject input
     */
    protected function printSubjectInput(){
        $subject  = $this->subject;
        if(empty($subject)){
            $subject  = $this->defaultSubject;
        }

        ?>
        <label>
            E-mail subject:<br>
            <input type='text' name="emails[<?php echo $this->subjectKey;?>]" value="<?php echo $subject;?>" style="width:100%;">
        </label>
        <br>
        <?php
    }

    /**
     * Prints the e-mail message input to screen
     */
    protected function printMessageInput(){
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

        $this->printHeadersInput($settings);
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