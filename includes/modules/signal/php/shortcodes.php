<?php
namespace SIM\SIGNAL;
use SIM;


//Add Signal messages overview shortcode
add_shortcode('signal_messages',function(){
	$signal_messages = get_option('signal_bot_messages');
	
	$html = '';
	
	//Perform remove action
	if(isset($_POST['recipient_number']) and isset($_POST['key'])){
		$html .= '<div class="success">Succesfully removed the message</div>';
	
		unset($signal_messages[$_POST['recipient_number']][$_POST['key']]);
		
		if(count($signal_messages[$_POST['recipient_number']]) == 0) unset($signal_messages[$_POST['recipient_number']]);
		
		update_option('signal_bot_messages',$signal_messages);
	}
	
	if(is_array($signal_messages) and count($signal_messages) >0){
		foreach($signal_messages as $recipient_number=>$recipient){
			$html .= "<strong>Messages to $recipient_number</strong><br>";
			foreach($recipient as $key=>$signal_message){
				$html .= 'Message '.($key+1).":<br>";
				$html .= $signal_message[0].'<br>';
				$html .= '<form action="" method="post">
					<input type="hidden" id="recipient_number" name="recipient_number" value="'.$recipient_number.'">
					<input type="hidden" id="key" name="key" value="'.$key.'">
					<button class="button remove signal_message sim" type="submit" style="margin-top:10px;">Remove this message</button>
				</form>';
			}
		}
	}else{
		$html = "No Signal messages found";
	}
	return $html;
});