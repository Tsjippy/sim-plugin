<?php
namespace SIM\MANDATORY;
use SIM;

use function SIM\getModuleOption;
use function SIM\getValidPageLink;

const MODULE_VERSION		= '7.0.7';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();

	?>
	<p>
		This module adds the possibility to make certain posts and pages mandatory.<br>
		That means people have to mark the content as read.<br>
		If they do not do so they will be reminded to read it until they do.<br>
		A "I have read this" button will be automatically added to the e-mail if it is send by mailchimp.<br>
		<br>
		Adds one shortcode 'must_read_documents', which displays the pages to be read as links.<br>
		Use like this <code>[must_read_documents]</code>.<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<label for="reminder_freq">How often should people be reminded of remaining content to read</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<h4>E-mail with read reminders</h4>
	<label>Define the e-mail people get when they shour read some mandatory content.</label>
	<?php
	$readReminder    = new ReadReminder(wp_get_current_user());
	$readReminder->printPlaceholders();
	$readReminder->printInputs($settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	//Get all the pages with an audience meta key
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_name',
			'order' 		=> 'asc',
			'post_type' 	=> 'any',
			'post_status' 	=> 'publish',
			'meta_query'	=> [
				[
					'key' 		=> "audience",
					'compare'	=> 'EXISTS'
				],
				[
					'key' 		=> "audience",
					'value'		=> 'a:0:{}',
					'compare'	=> '!='
				],
				[
					'key' 		=> "audience",
					'value'		=> '',
					'compare'	=> '!='
				]
			],
			'numberposts'	=> -1				// all posts
		)
	);

	$keys	= getAudienceOptions(['empty'], 1);
	unset($keys['everyone']);

	$html	= '<script>';
		$html	.= "function showUserList(pageId, button){";
			$html	.= "document.querySelector(`#wrapper-\${pageId}`).classList.toggle('hidden');"; 
			$html	.= "if(button.textContent.includes('Show')){";
				$html	.= "button.textContent	= button.textContent.replace('Show', 'Hide')"; 
			$html	.= "}else{";
				$html	.= "button.textContent	= button.textContent.replace('Hide', 'Show')"; 
			$html	.= "}";
		$html	.= "}";
	$html	.= '</script>';

	$html	.= "<table class='mandatory-pages-overview'>";
		$html	.= "<thead>";
			$html	.= "<tr>";
				$html	.= "<th>Page</th>";
				$html	.= "<th>Users</th>";
			$html	.= "</tr>";
		$html	.= "</thead>";
		$html	.= "<tbody>";
			foreach($pages as $page){
				$audience   = get_post_meta($page->ID, 'audience', true);
				if(!is_array($audience) && !empty($audience)){
					$audience  = json_decode($audience, true);
				}

				$url	= get_permalink($page->ID);

				$users	= [];

				$html	.= "<tr>";
					$html	.= "<td><a href='$url'>{$page->post_title}</a></td>";

					// Evryone should read this
					if(isset($audience['everyone']) || ( isset($audience['beforearrival']) && isset($audience['afterarrival']))){
						$metaQuery	= array(
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							)
						);
					}elseif(isset($audience['beforearrival'])){
						$metaQuery	=  array(
							'relation' => 'AND',
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							),
							array(
								'key' 		=> 'arrival_date',
								'value' 	=> Date('Y-m-d'),
								'compare' 	=> '>'
							),
						);
					}elseif(isset($audience['afterarrival'])){
						$metaQuery	=  array(
							'relation' => 'AND',
							array(
								'key' 		=> 'read_pages',
								'value' 	=> $page->ID,
								'compare' 	=> 'NOT LIKE'
							),
							array(
								'key' 		=> 'arrival_date',
								'value' 	=> Date('Y-m-d'),
								'compare' 	=> '<'
							),
						);
					}

					// get all users who have not read this page/post
					$users	= get_users(
						array(
							'orderby'		=> 'display_name',
							'count_total'	=> false,
							'fields'		=> ['display_name', 'ID'],
							'meta_query' 	=> $metaQuery
						)
					);
					
					if(!empty($users)){
						$count			= count($users);
						$cell			= "$count users still have to read this.";
						$userEditPage	= getValidPageLink(getModuleOption('usermanagement', 'user_edit_page'));
						$cell	.= "<div id='wrapper-$page->ID' class='hidden'>";
							foreach($users as $user){
								$cell	.= "<a href='$userEditPage?userid=$user->ID'>$user->display_name<br>";
							}
						$cell	.= "</div>";
					}else{
						$cell	= "Read by everyone";
					}
					$html	.= "<td>$cell</td>";

					$html	.= "<td><button class='small show-user-list' onclick='showUserList($page->ID, this)'>Show who</button></td>";
					
				$html	.= "</tr>";
			}			
		$html	.= "</tbody>";
	$html	.= "</table>";


	return $dataHtml.$html;
}, 10, 3);


add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	scheduleTasks();

	return $options;
}, 10, 2);