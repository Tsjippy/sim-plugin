<?php
namespace SIM\ADMIN;
use SIM;

abstract class MailSetting{

    protected function __construct() {
        $this->replaceArray   = [
            '%site_url%'    => SITEURL,
            '%site_name%'   => SITENAME
        ];
    }

    protected function addUser($user){
        if(!empty($user)){
            $this->replaceArray['%first_name%']  = $user->first_name;
            $this->replaceArray['%last_name%']   = $user->last_name;
            $this->replaceArray['%full_name%']   = $user->display_name;
        }
    }

    public function filterMail(){
        $this->subject  = SIM\get_module_option($this->moduleSlug, $this->keyword."_subject");
        $this->message  = SIM\get_module_option($this->moduleSlug, $this->keyword."_message");
        $this->subject  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->subject);
        $this->message  = str_replace(array_keys($this->replaceArray), array_values($this->replaceArray), $this->message);
    }

    private function printSubjectInput($settings){
        $subject  = $settings[$this->keyword."_subject"];
        if(empty($subject)){
            $subject  = $this->defaultSubject;
        }

        ?>
        <label>
            E-mail subject:<br>
            <input type='text' name="<?php echo $this->keyword;?>_subject" value="<?php echo $subject;?>" style="width:100%;">
        </label>
        <br>
        <?php
    }

    private function printMessageInput($settings){
        $message  = $settings[$this->keyword."_message"];
        if(empty($message)){
            $message  = $this->defaultMessage;
        }

        ?>
        <label>
            E-mail content
            <?php
            $settings = array(
                'wpautop' => false,
                'media_buttons' => false,
                'forced_root_block' => true,
                'convert_newlines_to_brs'=> true,
                'textarea_name' => $this->keyword."_message",
                'textarea_rows' => 10
            );

            echo wp_editor(
                $message,
                $this->keyword."_message",
                $settings
            );
            ?>
        </label>
        <?php
    }

    public function printInputs($settings){
        $this->printSubjectInput($settings);

        $this->printMessageInput($settings);
    }

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