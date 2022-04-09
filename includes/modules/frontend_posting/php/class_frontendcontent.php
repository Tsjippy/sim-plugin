<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

class FrontEndContent{
	function __construct(){
		$this->post_id			= $_GET['post_id'];
		$this->user 			= wp_get_current_user();
		$this->post 			= null;
		$this->post_type 		= "post";
		$this->name 			= "post";
		$this->post_title 		= '';
		$this->post_category 	= [];
		$this->post_content		= '';
		$this->post_parent 		= null;
		$this->post_image_id	= 0;
		$this->lite 			= false;
		
		if(in_array('contentmanager',$this->user->roles)){
			$this->fullrights		= true;
		}else{
			$this->fullrights		= false;
		}
		
		if(get_class($this) == __NAMESPACE__.'\FrontEndContent'){
			//Add tinymce plugin
			add_filter('mce_external_plugins', array($this,'add_tinymce_plugin'),999);
			
			//add tinymce button
			add_filter('mce_buttons', array($this,'register_buttons'));
			
			//Save a post over AJAX
			add_action ( 'wp_ajax_submit_post', array($this,'submit_post'));
			add_action ( 'wp_ajax_draft_post', array($this,'submit_post'));
			
			//add action over ajax to read file contents
			add_action ( 'wp_ajax_remove_post',array($this,'remove_post'));
			
			//add action over ajax to change the post type
			add_action ( 'wp_ajax_change_post_type', array($this,'change_post_type'));
			
			//Lock the post for editing of other users
			add_action ( 'wp_ajax_refresh_post_lock',function(){
				if(!empty($_POST['postid']) and is_numeric($_POST['postid'])){
					//print_array("refreshing post lock for ".$_POST['postid']);
					wp_set_post_lock($_POST['postid']);
				}
			});

			//allow editing by other users again
			add_action ( 'wp_ajax_delete_post_lock',function(){
				if(!empty($_POST['postid']) and is_numeric($_POST['postid'])){
					//print_array("Removing post lock for ".$_POST['postid']);
					delete_post_meta( $_POST['postid'], '_edit_lock');
				}
			});
		}
	}
	
	function add_tinymce_plugin($plugins) {
		$plugins['select_user'] = INCLUDESURL."/js/tiny_mce_action.js?ver=".ModuleVersion;
		
		return $plugins;
	}
	
	function register_buttons($buttons) {
		array_push($buttons, 'separator', 'file_upload','select_user');
		return $buttons;
	}
	
	function has_edit_rights(){
		//Only set this once
		if(!isset($this->edit_right)){
			$user_compound 		= get_user_meta( $this->user->ID, "location", true);
			if(isset($user_compound['compound'])) $user_compound = $user_compound['compound'];
			
			$missionary_page_id = SIM\USERPAGE\get_user_page_id($this->user->ID);
			
			$user_ministries 	= get_user_meta( $this->user->ID, "user_ministries", true);
			
			$post_author		= $this->post->post_author;
				
			//Check if allowed to edit this
			if(
				$post_author != $this->user->ID 									and 
				!isset($user_ministries[str_replace(" ","_",$this->post_title )])	and 
				$user_compound != $this->post_title 								and 
				$missionary_page_id != $this->ID									and
				!$this->fullrights
			){
				$this->edit_right	= false;
			}else{
				$this->edit_right	= true;
			}

			$this->edit_right	= apply_filters('sim_frontend_content_edit_rights', $this->edit_right, $this->post_category);
		}
	}
	
	function fill_post_data(){
		//Load existing post data
		if(is_numeric($this->post_id)){
			$this->post 											= get_post($this->post_id);
			$this->post_type 										= $this->post->post_type;
			$this->post_title 										= $this->post->post_title;
			$this->post_content 									= $this->post->post_content;
			$this->post_parent 										= $this->post->post_parent;
			$this->post_image_id									= get_post_thumbnail_id($this->post_id);
		}
		
		if(!empty($_GET['type'])){
			$this->post_type 	= $_GET['type'];
		}
		
		$this->post_name 									= str_replace("_lite","",$this->post_type);
		$this->post_category 								= $this->post->post_category;
		
		//show lite version of location by default
		if($this->post_name == 'location' and $this->post_content == '' or strpos($this->post_type, '_lite') !== false){
			$this->lite 		= true;
		}
		
		if($this->fullrights == true){
			if($this->post_id == null or $this->post->post_status != 'publish'){
				$this->action = "Publish <span class='replaceposttype'>{$this->post_name}</span>";
			}else{
				$this->action = "Update <span class='replaceposttype'>{$this->post_name}</span>";
			}
		}else{
			$this->action = "Submit <span class='replaceposttype'>{$this->post_name}</span> for review";
		}
	}
		
	function upload_images($matches) {
		$ext 			= $matches[1];
		$filename 		= "frontend_picture";
		$basedir 		= wp_upload_dir()['path'];
		$new_file_path 	= "$basedir/$filename.$ext";
		$i 				= 0;
		$upload_id		= 0;
		
		//Find an available filename
		while( file_exists( $new_file_path ) ) {
			$i++;
			$new_file_path = "$basedir/$filename"."_$i.$ext";
		}
		
		//Decode the base64
		$file_contents = base64_decode(substr_replace($matches[2] ,"",-1));
		
		//Only continue if the decoding was succesfull
		if( $file_contents !== false){
			//Save the image in the uploads folder
			file_put_contents($new_file_path, $file_contents);
			
			SIM\add_to_library($new_file_path);
		}else{
			SIM\print_array('Not a valid image');
		}
		
		//Return the image url
		$url = wp_get_attachment_image_url($upload_id,'');
		return '"'.$url;
	}

	function submit_post($status=''){
		SIM\verify_nonce('frontend_post_nonce');

		$userdata	= wp_get_current_user();
			
		if($_POST['action'] == 'draft_post'){
			$status = 'draft';
		}elseif(empty($status)){
			if($this->fullrights == true and $_POST['publish_date'] == date('Y-m-d')){
				$status = 'publish';
			}else{
				$status = 'pending';
			}
		}
		
		//Check if valid post type
		$this->post_type = $_POST['post_type'];
		if(count(get_post_types(['name' => $this->post_type])) == 0){
			wp_die("{$this->post_type} is not a valid type",500);
		}
		
		//First letter should be capital in the title
		$this->post_title 	= ucfirst(sanitize_text_field($_POST['post_title']));
		if(empty($this->post_title)) wp_die("Please specify a title!",500);
		
		$post_content 	= $_POST['post_content'];
		
		//Find any base64 encoded images in the post content and replace the url
		$post_content 	= preg_replace_callback('/"data:image\/(\w+);base64,([^"]*)/m', array($this,'upload_images'), $post_content);

		// Check if content is just an hyperlink
		//find all urls in the page
		$regex = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
		preg_match_all("#$regex#i", $post_content, $matches);
		$url = $matches[0][0];

		//if the url is the only post content
		if($url == strip_tags($post_content)){
			//find the post id of the url
			$postId	= url_to_postid($url);

			// If a valid post id
			if($postId > 0){
				$post_content	= "[showotherpost postid='$postId']";
			}
		}
		
		//Find display names in content
		$users = SIM\get_user_accounts(false,false,true);
		foreach($users as $user){
			$privacy_preference = get_user_meta( $user->ID, 'privacy_preference', true );
			//only replace the name with a link if privacy allows
			if(empty($privacy_preference['hide_name'])){
				//Replace the name with a hyperlink
				$url			= SIM\USERPAGE\get_user_page_url($user->ID);
				$link			= "<a href='$url'>{$user->display_name}</a>";
				$post_content	= str_replace($user->display_name,$link,$post_content);
			}
		}
		
		//Sanitize the post content
		$post_content = wp_kses_post($post_content);
		
		$categories = [];
		if(is_array($_POST['category_id'])){
			foreach($_POST['category_id'] as $category_id) {
				if(!empty($category_id)) $categories[] = $category_id;
			}
		}
			
		//Check if editing an existing post
		if(is_numeric($_POST['post_id'])){
			$this->post_id = $_POST['post_id'];
			
			$update = true;
			
			//Retrieve the old post data
			$post = get_post($this->post_id);
			
			$new_post_data = ['ID'=>$this->post_id];
			
			//Check for updates
			if($this->post_title != $post->post_title) 		$new_post_data['post_title'] 	= $this->post_title;
			if($post_content != $post->post_content) 		$new_post_data['post_content'] 	= $post_content;
			if($status != $post->post_status)				$new_post_data['post_status'] 	= $status;
			
			//only update author if needed and not in the content manager role
			$new_post_data['post_author'] 	= $_POST['author'];
			
			if($_POST['parent_page'] != $post->post_parent)	$new_post_data['post_parent'] 	= $_POST['parent_page'];
			if($categories != $post->post_category)			$new_post_data['post_category'] = $categories;

			//we cannot change the post type here
			if($post->post_type != $this->post_type) wp_die('You can not change the post type like that!',500);
			
			//Update the post
			$result = wp_update_post($new_post_data,true,false);
			if(is_wp_error($result)){
				wp_die($result->get_error_message(),500);
			}else{
				$action_text = 'updated';
			}
		}else{
			$update = false;

			if($this->post_type == 'attachment'){
				$this->post_id 	= SIM\add_to_library(SIM\url_to_path($_POST['attachment'][0]), $this->post_title, $post_content);
			}else{
				//New post
				$post = array(
					'post_type'		=> $this->post_type,
					'post_title'    => $this->post_title,
					'post_content'  => $post_content,
					'post_status'   => $status,
					'post_author'   => $_POST['author']
				);
				
				if(is_numeric($_POST['parent_page'])){
					$post['post_parent'] = $_POST['parent_page'];
				}
			
				if(count($categories)>0){
					$post['post_category'] = $categories;
				}

				//Schedule the post
				if($_POST['publish_date'] != date('Y-m-d')){
					$publish_date			= date("Y-m-d 08:00:00", strtotime($_POST['publish_date']));

					$post['post_date'] 		= $publish_date;
					$post['post_date_gmt'] 	= $publish_date;
				}
				
				// Insert the post into the database.
				$this->post_id 	= wp_insert_post( $post,true,false);
				$post['ID']		= $this->post_id;
			}
			
			if(is_wp_error($this->post_id)){
				wp_die($this->post_id->get_error_message(),500);
			}elseif($this->post_id === 0){
				wp_die("Could not create the $this->post_type!",500);
			}else{
				$action_text = 'created';
			}
		}
		
		$url 		= get_permalink($this->post_id);
		
		//Set the featured image
		if(is_numeric($_POST['post_image_id'])){
			set_post_thumbnail($this->post_id, $_POST['post_image_id']);
		}
		
		//Static content
		if(isset($_POST['static_content'])){
			//Store static content option
			if($_POST['static_content'] == ''){
				$value = false;
			}else{
				$value = true;
			}
			
			update_post_meta($this->post_id,'static_content',$value);
		}
		
		//Expiry date
		if(isset($_POST['expirydate'])){
			//Store expiry date
			update_post_meta($this->post_id,'expirydate',$_POST['expirydate']);
		}
		
		do_action('sim_after_post_save', (object)$post, $update);
		
		//Return result
		if($status == 'publish'){
			wp_die("Succesfully $action_text the $this->post_type, view it <a href='$url'>here</a>");
		}elseif($status == 'draft'){
			wp_die("Succesfully $action_text the draft for this $this->post_type, preview it <a href='$url'>here</a>");
		}elseif($_POST['publish_date'] != date('Y-m-d')){
			wp_die("Succesfully $action_text the $this->post_type, it will be published on ".date('d F Y', strtotime($_POST['publish_date'])).' 8 AM');
		}else{
			wp_die("Succesfully $action_text the $this->post_type, it will be published after it has been reviewed");			
		}
	}
	
	function remove_post(){
		if($this->fullrights == false) wp_die("You do not have permissions to delete posts!",500);
		
		$post_id = $_POST['post_id'];
		
		if(is_numeric($_POST['post_id'])){
			SIM\verify_nonce("deletepost_nonce_$post_id");
		
			SIM\print_array("Removing post $post_id");
			
			$post = wp_trash_post($post_id);
			
			$post_type = get_post_type($post);
			
			if($post_type != false) wp_die("Succesfully deleted $post_type '{$post->post_title}'<br>You can leave this page now");
			
		}else{
			wp_die("Invalid post id",500);
		}
	}	
	
	function change_post_type(){
		$post_type = $_POST['post_type_selector'];
		
		if(!in_array($post_type,get_post_types())){
			wp_die("$post_type is not a valid post type!",500);
		}
		
		SIM\print_array("Changing post type to ".$post_type);
		
		$post_id = $_POST['postid'];
		SIM\verify_nonce("change_poste_type_nonce");
		
		if(is_numeric($post_id)){
			if($this->fullrights == false) wp_die("You do not have permissions to delete posts!",500);
			
			$result = set_post_type($post_id,$post_type);
			if($result){
				wp_die("Succesfully updated the type to ".$post_type);
			}else{
				wp_die("Could not update the type",500);
			}
				
		}else{
			wp_die("No valid post id",500);
		}
	}

	function post_type_selector(){
		//do not show for lite posts
		if($this->lite == false){
			if($this->post_id == null){
				$label_text = 'Select the content type you want to create:';
			}else{
				$label_text = "You are editing a {$this->post_type}, use selector below if you want to change the post type";
				?>
				<form action="" method="post" name="change_post_type">
				<?php
			}
			
			?>
			<h4><?php echo $label_text; ?></h4>
			<select id="post_type_selector" name="post_type_selector" required>
				<?php
				$post_types	= get_post_types(['public'=>true]);
				
				foreach($post_types as $post_type){
					if($this->post_type == $post_type){
						$selected = 'selected';
					}else{
						$selected = '';
					}

					$type_name	= ucfirst($post_type);
					if($post_type == 'attachment') $type_name = 'Picture/Video/Audio';
					echo "<option value='$post_type' $selected>$type_name</option>";
				}
				
				?>
			</select>
			<?php
			
			if($this->post_id != null){
				?>
					<input type="hidden" name="action"					value="change_post_type">
					<input type="hidden" name="userid"					value="<?php echo $this->user->ID; ?>">
					<input type="hidden" name="postid"					value="<?php echo $this->post_id; ?>">
					<input type="hidden" name="change_poste_type_nonce" value="<?php echo wp_create_nonce("change_poste_type_nonce"); ?>">
				
				<?php
				echo SIM\add_save_button('change_post_type','Change the post type');
				echo '</form>';
			}
		}
	}
	
	function add_modals(){
		$post_types		= apply_filters('sim_frontend_posting_modals', []);

		foreach($post_types as $type){
			$categories = get_categories( array(
				'orderby' 	=> 'name',
				'order'   	=> 'ASC',
				'taxonomy'	=> $type.'type',
				'hide_empty'=> false,
			) );

			?>
			<div id="add_<?php echo $type;?>_type" class="modal hidden">
				<!-- Modal content -->
				<div class="modal-content">
					<span id="modal_close" class="close">&times;</span>
					<form action="" method="post" id="add_<?php echo $type;?>_type_form">
						<p>Please fill in the form to add a new <?php echo $type;?> category</p>
						<input type="hidden" name="action" value="add_<?php echo $type;?>_type">
						<input type="hidden" name="userid" value="<?php echo $this->user->ID; ?>">
						
						<input type="hidden" name="add_<?php echo $type;?>_type_nonce" value="<?php echo wp_create_nonce("add_{$type}_type_nonce"); ?>">
						
						<label>
							<h4>Category name<span class="required">*</span></h4>
							<input type="text"  name="<?php echo $type;?>_type_name" required>
						</label>
						
						<h4>Parent category</h4>
						<select class="" name='<?php echo $type;?>_type_parent'>
							<option value=''>---</option>
							<?php
							foreach($categories as $category){
								//Only ouptut categories without a parent
								if($category->parent == 0){
									$name 	= $category->name;
									$cat_id = $category->cat_ID;
									echo "<option value='$cat_id'>$name</opton>";
								}
							}
							?>
						</select>
						
						
						<?php echo SIM\add_save_button('add_'.$type.'_type',"Add $type category"); ?>
					</form>
				</div>
			</div>
			<?php
		}
	}
	
	//Show categories for posts and pages
	function post_categories(){
		$categories = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
		) );
		?>
		<div id="post-category" class="categorywrapper post page <?php if($this->post_type != 'post' and $this->post_type != 'page') echo 'hidden'; ?>">
			<h4>
				<span class="capitalize replaceposttype"><?php echo $this->post_type;?></span> category
			</h4>
			<div class='categorieswrapper'>
				<?php
				foreach($categories as $category){
					$name 				= $category->name;
					$cat_id 			= $category->cat_ID;
					$cat_description	= $category->description;
					
					echo "<div class='infobox post ";

					if($cat_id == get_cat_ID('Public') or $cat_id == get_cat_ID('Confidential') ){
						echo 'page';
					//do not show categories other than public and confidential to non-post types
					}elseif($this->post_type != 'post'){
						echo ' hidden';
					}
					echo "'>";
					
						echo "<input type='checkbox' name='category_id[]' value='$cat_id'";
							if(is_array($this->post_category) and in_array($cat_id,$this->post_category)) echo ' checked';
						echo '>';
					
						echo "<label class='option-label category-select'>$name</label>";

						if($cat_description != '')	echo "<span class='info_text'>$cat_description</span>";

					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
	}
	
	function post_specific_fields(){
		?>
		<div id="post-attributes"  class="post<?php if($this->post_type != 'post') echo ' hidden'; ?>">
			<div id="expirydate_div" class="frontendform">
				<h4>Expiry date</h4>
				<label>
					<input type='date' class='' name='expirydate' min="<?php echo date("Y-m-d"); ?>" value="<?php echo get_post_meta($this->post_id,'expirydate',true); ?>" style="display: unset;width:unset;">
					Set an optional expiry date of this post
				</label>
			</div>
		</div>
		<?php
	}
	
	function page_specific_fields(){
		?>		
		<div id="page-attributes" class="page<?php if($this->post_type != 'page') echo ' hidden'; ?>">
			<div id="parentpage" class="frontendform">
				<h4>Select a parent page</h4>
				<?php 
				echo SIM\page_select('parent_page',$this->post_parent);
				?>
			</div>

			<?php
			do_action('sim_page_specific_fields', $this->post_id);
			?>			
			<div id="static_content" class="frontendform">
				<h4>Update warnings</h4>	
				<label>
					<input type='checkbox' name='static_content' value='static_content' <?php if(get_post_meta($this->post_id,'static_content',true) != '') echo 'checked';?>>
					Do not send update warnings for this page
				</label>
			</div>
		</div>
		<?php
	}
	
	function show_categories($type, $categories){
		?>
		<div class="<?php echo $type; if($this->post_type != $type) echo ' hidden'; ?>">
			<div class="frontendform">
				<h4><?php echo ucfirst($type);?> type</h4>
				<div class='categories'>
					<?php
					$parent_category_html 	= '';
					$child_category_html 	= '';
					$hidden				= 'hidden';
				
					foreach($categories as $category){
						$name 				= ucfirst($category->slug);
						$cat_id 			= $category->cat_ID;
						$cat_description	= $category->description;
						$parent				= $category->parent;
						$checked			= '';
						$class				= 'infobox';
						
						//This category is a not a child
						if($parent == 0){
							$html = 'parent_category_html';
						//has a parent
						}else{
							$html = 'child_category_html';
						}
						
						//if this cat belongs to this post
						if(has_term($cat_id,$type.'type',$this->post_id)){
							$checked = 'checked';
							
							//If this type has child types, show the label
							if(count(get_term_children($category->cat_ID,$type.'type'))>0) $hidden = '';
						}
						
						
						//if this is a child, hide it and atach the parent id as attribute
						if($parent != 0){
							//Hide subcategory if parent is not in the cat array
							if(!has_term($parent,$type.'type',$this->post_id))	$class .= " hidden";
							
							//Store cat parent
							$class .= "' data-parent='$parent";
						}
						
						//$$html --> use the value of $html as variable name
						$$html .= "<div class='$class'>";
							$checkboxclass = "{$type}type";
							if(count(get_term_children($category->cat_ID,$type.'type'))>0) $checkboxclass .= " parent_cat";
							$$html .= "<input type='checkbox' class='$checkboxclass' name='{$type}type[]' value='$cat_id' $checked>";
						
							//Name of the category
							$$html .= "<label class='option-label category-select'>$name</label>";
							
							//Add infobox if needed
							if($cat_description != '')	$$html .= "<span class='info_text'>$cat_description</span>";

						$$html .= '</div>';
					}
					
					
					?>
					<div id='<?php echo $type;?>_parenttypes'>
						<?php
						echo $parent_category_html;
						?>
						<button type='button' name='add_<?php echo $type;?>_type_button' class='button add_cat' data-type='<?php echo $type;?>'>Add category</button>
					</div>
					
					<label id='subcategorylabel' class='frontend-profile-label <?php echo $hidden ?>'>Sub-category</label>
					
					<div id='<?php echo $type;?>_childtypes' class='childtypes'>
						<?php
						echo $child_category_html;
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	function content_manager_options(){		
		if($this->fullrights){
			$hidden			= '';
		}else{
			$hidden			= 'hidden';
		}
		
		$buttontext		= 'Show';
		
		if($hidden == '') $buttontext		= 'Hide';
		
		?>
		<button type="button" class="button" id="advancedpublishoptionsbutton" style='display:block; margin-top:15px;'><span><?php echo $buttontext;?></span> advanced options</button>
		
		<div class="advancedpublishoptions <?php echo $hidden;?>">
			<?php	
			// Show change author dropdown 
			$author_id	= $this->post->post_author;
			if(!is_numeric($author_id)) $author_id = $this->user->ID;
			echo SIM\user_select('Author', $only_adults=true, $families=false, $class='', $id='author', $args=[], $user_id=$author_id);
			?>
			<label>
				<h4>Publishing date</h4>
				<input type="date" min="<?php echo date("Y-m-d");?>" name="publish_date" value="<?php echo date("Y-m-d");?>">
				Define when the content should be published
			</label>
			<?php

			$this->post_specific_fields();
			
			$this->page_specific_fields();
				
			do_action('frontend_post_after_content', $this);
			?>
		</div>
		<?php
	}
	
	function frontend_post(){
		if(!function_exists('_wp_translate_postdata')){
			include ABSPATH . 'wp-admin/includes/post.php';
		}
		
		//Load js
		wp_enqueue_script('sim_frontend_script');
		wp_enqueue_media();
		
		ob_start();
		
		$this->fill_post_data();
		
		//Show warning if not allowed to edit
		$this->has_edit_rights();
		if(!$this->edit_right and is_numeric($_GET['post_id'])){
			return '<div class="error">You do not have permission to edit this page.</div>';
		}
		
		//Show warning if someone else is editing
		$current_edting_user = wp_check_post_lock($post_id);
		if(is_numeric($current_edting_user)){
			header("Refresh: 30;");
			return "<div class='error' id='	'>".get_userdata($current_edting_user)->display_name." is currently editing this {$this->post_type}, please wait.<br>We will refresh this page every 30 seconds to see if you can go ahead.</div>";
		}
		
		//Current time minus last modified time
		$seconds_since_updated = time()-get_post_modified_time('U',true,$this->post);
		
		//Show warning when post has been updated recently
		if($seconds_since_updated < 3600){
			$minutes = intval($seconds_since_updated/60);
			echo "<div class='warning'>This {$this->post_type} has been updated <span id='minutes'>$minutes</span> minutes ago.</div>";
		}
		
		//Show warning when post is in trash
		if($this->post->post_status == 'trash'){
			echo "<div class='warning'>This {$this->post_type} has been deleted.<br>You can republish if that should not be the case.</div>";
		}
		
		//Add extra variables to the main.js script
		wp_localize_script( 'sim_script', 
			'frontendpost', 
			array( 
				'user_select' 		=> SIM\user_select("Select a person to show the link to",true),
				'post_type'			=> $this->post_type,
			) 
		);

		?>
		<div id="frontend_upload_form">			
			<?php
			if($this->lite == false){
				$hidden = 'hidden';
			}

			$update	= 'false';
			if(is_numeric($this->post_id) and $this->post->post_status == 'publish'){
				$update	= 'true';
			}
			echo "<button class='button sim $hidden show' id='showallfields'>Show all fields</button>";
			
			$this->post_type_selector();
			
			$this->add_modals();
			do_action('frontend_post_modal');

			//Write the form to create all posts except events
			?>
			<form id="postform">
				<input type="hidden" name="action" value="submit_post">
				<input type="hidden" name="frontend_post_nonce" value="<?php echo wp_create_nonce("frontend_post_nonce");?>">
				<input type="hidden" name="userid" value="<?php echo $this->user->ID?>">
				<input type="hidden" name="post_type" value="<?php echo $this->post_type; ?>">
				<input type="hidden" name="post_image_id" value="<?php echo $this->post_image_id;?>">
				<input type="hidden" name="update" value="<?php echo $update;?>">
				
				<?php 
				if($this->post_id != null) echo "<input type='hidden' name='post_id' value='{$this->post_id}'>";
				?>
				
				<h4>Title</h4>
				<input type="text" name="post_title" class='block' value="<?php echo $this->post_title;?>" required>
				
				<?php
				do_action('frontend_post_before_content', $this);
				
				$this->post_categories();
				?>
				
		 		<div id="featured-image-div" <?php if($this->post_image_id == 0) echo ' class="hidden"';?>>
					<h4 name="post_image_label">Featured image:</h4>
					<?php 
					if($this->post_image_id == 0){
						echo get_the_post_thumbnail(
							$this->post_id, 
							'thumbnail', 
							array(
								'title' => 'Featured Image',
								'class' => 'postimage'
							)
						);
						echo "<button type='button' class='remove_document button' data-url='$document' data-userid='{$this->user_id}' data-metakey='$metakey_string' $library_string>X</button>";
						echo "<img class='remove_document_loader src='".LOADERIMAGEURL."' style='display:none; height:40px;' >";
						$text = 'Change';
					}else{
						$text = 'Add';
					}
					?>
				</div>

				<div class='attachment hidden'>
					<h4>Upload your file</h4>
					<?php
					$uploader = new SIM\Fileupload($this->user->ID, 'attachment', 'private', false);
					echo $uploader->get_upload_html();
					?>
				</div>
				
				<?php
				//Content wrapper
				if($this->lite == true){
					echo "<div class='hidden postcontentwrapper lite'>";
				}else{
					echo "<div class='postcontentwrapper lite'>";
				}
					echo "<div class='titlewrapper'>";
						//Post content title
						$class = 'post page';
						if($this->post_type != 'post' and $this->post_type != 'page') $class .= ' hidden';
						echo "<h4 class='$class' name='post_content_label'>";
							echo '<span class="capitalize replaceposttype">'.ucfirst($this->post_type).'</span> content';
						echo "</h4>";

						echo "<h4 class='attachment hidden' name='attachment_content_label'>Description:</h4>";
						
						do_action('frontend_post_content_title',$this->post_type);
					echo "</div>";
					
					//make it possible to select or upload a featured image
					if ( current_user_can( 'upload_files' ) ) {
						add_action(
							'media_buttons',
							function() use ($text){
								echo "<button type='button' name='add-featured-image' class='button add_media'><span class='wp-media-buttons-icon'></span> $text Featured Image</button>";
							},
							5
						);
					}
					
					//output tinymce window
					$settings = array(
						'wpautop'					=> false,
						'forced_root_block'			=> true,
						'convert_newlines_to_brs'	=> true,
						'textarea_name'				=> "post_content",
						'textarea_rows'				=> 10
					);
					echo wp_editor($this->post_content,'post_content',$settings);
				echo "</div>";
				try{
					$this->content_manager_options();
				}catch(\Exception $e) {
					SIM\print_array($e);
				}
				
				//Add a draft button for new posts
				if($this->post_id == null or $this->post->post_status != 'publish'){
					if($this->post_id == null){
						$button_text = "Save <span class='replaceposttype'>{$this->post_name}</span> as draft";
					}else{
						$button_text = "Update this <span class='replaceposttype'>{$this->post_name}</span> draft";
					}
					
					echo "<div class='submit_wrapper' style='display: flex;'>";
						echo "<button type='button' class='button savedraft' name='draft_post'>$button_text</button>";
						echo "<img class='loadergif hidden' src='".LOADERIMAGEURL."'>";
					echo "</div>";
					
				}
				echo SIM\add_save_button('submit_post', $this->action);
				?>
			</form>
			<?php
						
			//Only show delete button for existing posts and not yet deleted
			if($this->post_id != null and $this->post->post_status != 'trash'){
			?>
			<div class='submit_wrapper' style='display: flex; margin-top:20px;float:right;margin-right:0px;'>
				<form>
					<input type="hidden" name="action" value="delete_post">
					<input hidden name='post_id' value='<?php echo $this->post_id; ?>'>
					<input hidden name='deletepost_nonce_<?php echo $this->post_id ?>' value='<?php echo wp_create_nonce("deletepost_nonce_".$this->post_id); ?>'>
					
					<button type='submit' class='button' name='delete_post'>Delete <?php echo $this->post_type; ?></button>
					<img class='loadergif hidden' src='<?php echo LOADERIMAGEURL; ?>'>
				</form>
			</div>
			<?php } ?>
		</div>
		
		<?php
		
		return ob_get_clean();
	}
}

add_action('init', function(){
	if(wp_doing_ajax()){
		new FrontEndContent();
	}
});