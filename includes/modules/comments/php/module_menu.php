<?php
namespace SIM\COMMENTS;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	<p>
		This module allows you to define the e-mails send when someone adds a comment to the website.<br>
		You can also define on which post types comments are allowed<br>
		You can turn on or off comments also on a per page level.
	</p>
	<?php
});

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
    ?>
	<label>Which post types should have comments allowed by default?</label><br>
	<?php
	foreach(get_post_types() as $type){
		?>
		<label>
			<input type='checkbox' name='posttypes[]' value='<?php echo $type;?>' <?php if(in_array($type, $settings['posttypes'])){echo 'checked';}?>>
			<?php echo $type;?>
		</label>
		<br>
		<?php
	}
	?>
	<br>
	<br>
	<h4>Define the e-mail people get when someone left a comment to a page they created.</h4>
	<?php
	$email    = new ApprovedCommentEmail([]);
	$email->printPlaceholders();
	$email->printInputs($settings);
	?>
	<br>
	<br>
	<h4>Define the e-mail content managers get when a comment needs approval</h4>
	<?php
	$email    = new CommentWarningEmail([]);
	$email->printPlaceholders();
	$email->printInputs($settings);
	?>
	<br>
	<br>
	<h4>Define the e-mail people get when someone replies to their comment</h4>
	<?php
	$email    = new CommentReplyEmail([]);
	$email->printPlaceholders();
	$email->printInputs($settings);
}, 10, 3);