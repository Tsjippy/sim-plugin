<?php
namespace SIM\FRONTENDPOSTING;
use SIM;
use WP_Error;

class FrontEndContent{
	function __construct(){
		$this->postId			= $_GET['post_id'];
		$this->user 			= wp_get_current_user();
		$this->post 			= null;
		$this->postType 		= "post";
		$this->name 			= "post";
		$this->postTitle 		= '';
		$this->postCategory 	= [];
		$this->postContent		= '';
		$this->postParent 		= null;
		$this->postImageId		= 0;
		$this->lite 			= false;
		
		if(in_array('editor', $this->user->roles)){
			$this->fullrights		= true;
		}else{
			$this->fullrights		= false;
		}
		
		if(get_class($this) == __NAMESPACE__.'\FrontEndContent'){
			//Add tinymce plugin
			add_filter('mce_external_plugins', array($this, 'addTinymcePlugin'),999);
			
			//add tinymce button
			add_filter('mce_buttons', array($this,'registerButtons'));
		}
	}
	
	/**
	 *
	 * Renders the form to edit existing content or create new content
	 *
	 * @return   string     The form html
	 *
	**/
	function frontendPost(){
		if(!function_exists('_wp_translate_postdata')){
			include ABSPATH . 'wp-admin/includes/post.php';
		}
		
		//Load js
		wp_enqueue_script('sim_frontend_script');
		wp_enqueue_media();
		
		ob_start();
		
		$this->fillPostData();
		
		//Show warning if not allowed to edit
		$this->hasEditRights();
		if(!$this->editRight && is_numeric($_GET['post_id'])){
			return '<div class="error">You do not have permission to edit this page.</div>';
		}
		
		//Show warning if someone else is editing
		$currentEditingUser = wp_check_post_lock($_GET['post_id']);
		if(is_numeric($currentEditingUser)){
			header("Refresh: 30;");
			return "<div class='error' id='	'>".get_userdata($currentEditingUser)->display_name." is currently editing this {$this->postType}, please wait.<br>We will refresh this page every 30 seconds to see if you can go ahead.</div>";
		}
		
		//Current time minus last modified time
		$secondsSinceUpdated = time()-get_post_modified_time('U', true, $this->post);
		
		//Show warning when post has been updated recently
		if($secondsSinceUpdated < 3600 && $secondsSinceUpdated > -1){
			$minutes = intval($secondsSinceUpdated/60);
			echo "<div class='warning'>This {$this->postType} has been updated <span id='minutes'>$minutes</span> minutes ago.</div>";
		}
		
		//Show warning when post is in trash
		if($this->post->post_status == 'trash'){
			echo "<div class='warning'>This {$this->postType} has been deleted.<br>You can republish if that should not be the case.</div>";
		}
		
		//Add extra variables to the main.js script
		wp_localize_script( 'sim_script', 
			'frontendpost', 
			array( 
				'postType'			=> $this->postType,
			) 
		);

		?>
		<div id="frontend_upload_form">			
			<?php
			if(!$this->lite){
				$hidden = 'hidden';
			}

			$this->update	= 'false';
			if(is_numeric($this->postId) && $this->post->post_status == 'publish'){
				$this->update	= 'true';
			}
			echo "<button class='button sim $hidden show' id='showallfields'>Show all fields</button>";
			
			$this->postTypeSelector();
			
			$this->addModals();
			do_action('sim_frontend_post_modal');

			$this->showChanges();

			//Write the form to create all posts except events
			?>
			<form id="postform">
				<input type="hidden" name="post_status" 	value="publish">
				<input type="hidden" name="post_type" 		value="<?php echo $this->postType; ?>">
				<input type="hidden" name="post_image_id" 	value="<?php echo $this->postImageId;?>">
				<input type="hidden" name="update" 			value="<?php echo $this->update;?>">
				<input type='hidden' name='post_id' 		value='<?php echo $this->postId;?>'>
				
				<h4>Title</h4>
				<input type="text" name="post_title" class='block' value="<?php echo $this->postTitle;?>" required>
				
				<?php
				do_action('sim_frontend_post_before_content', $this);
				
				$this->postCategories();
				
				$categories	= get_categories( array(
					'orderby' 		=> 'name',
					'order'   		=> 'ASC',
					'taxonomy'		=> 'attachment_cat',
					'hide_empty' 	=> false,
				) );
				
				$this->showCategories('attachment', $categories);

				?>
				<div class='attachment hidden'>
					<?php
					//Existing media
					if(is_numeric($this->postId)){
						$image	= wp_get_attachment_image($this->postId);
						
						echo "<h4>Attachment preview</h4>";
						echo apply_filters('sim_attachment_preview', $image, $this->postId);
					}else{
						?>
						<h4>Upload your file</h4>
						<?php
						$uploader = new SIM\FILEUPLOAD\FileUpload($this->user->ID);
						echo $uploader->getUploadHtml('attachment', 'private');
					}
					?>
				</div>
				
		 		<div id="featured-image-div" <?php if($this->postImageId == 0){echo ' class="hidden"';}?>>
					<h4 name="post_image_label">Featured image:</h4>

					<span id='featured_image_wrapper'>
						<?php 
						if($this->postImageId != 0){
							echo get_the_post_thumbnail(
								esc_html($this->postId), 
								'thumbnail', 
								array(
									'title' => 'Featured Image',
									'class' => 'postimage'
								)
							);
							$text 	= 'Change';
						}else{
							$text = 'Add';
						}
						?>
					</span>
					<button type='button' class='remove_featured_image button'>X</button>
				</div>
				
				<?php
				//Content wrapper
				if($this->lite){
					echo "<div class='hidden postcontentwrapper lite'>";
				}else{
					echo "<div class='postcontentwrapper lite'>";
				}
					echo "<div class='titlewrapper'>";
						//Post content title
						$class = 'post page';
						if($this->postType != 'post' && $this->postType != 'page'){
							$class .= ' hidden';
						}
						
						echo "<h4 class='$class' name='post_content_label'>";
							echo  '<span class="capitalize replaceposttype">'.ucfirst($this->postType).'</span> content';
						echo "</h4>";

						echo "<h4 class='attachment hidden' name='attachment_content_label'>Description:</h4>";
						
						do_action('sim_frontend_post_content_title', $this->postType);
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
					echo  wp_editor($this->postContent, 'post_content', $settings);
					?>
				</div>
				
				<?php
				try{
					$this->contentManagerOptions();
				}catch(\Exception $e) {
					SIM\printArray($e);
				}
				
				//Add a draft button for new posts
				if($this->postId == null || ($this->post->post_status != 'publish' && $this->post->post_status != 'inherit')){
					if($this->postId == null){
						$button_text = "Save <span class='replaceposttype'>{$this->post_name}</span> as draft";
					}else{
						$button_text = "Update this <span class='replaceposttype'>{$this->post_name}</span> draft";
					}
					
					echo "<div class='submit_wrapper' style='display: flex;'>";
						echo "<button type='button' class='button savedraft' name='draft_post'>$button_text</button>";
						echo "<img class='loadergif hidden' src='".LOADERIMAGEURL."'>";
					echo "</div>";
					
				}
				echo  SIM\addSaveButton('submit_post', $this->action);
				?>
			</form>
			<?php
						
			//Only show delete button for existing posts and not yet deleted
			if($this->postId != null && $this->post->post_status != 'trash' && $this->fullrights){
				?>
				<div class='submit_wrapper' style='display: flex; margin-top:20px;float:right;margin-right:0px;'>
					<form>
						<input hidden name='post_id' value='<?php echo  esc_html($this->postId); ?>'>

						<button type='submit' class='button' name='delete_post'>Delete <?php echo  esc_html($this->post->post_type); ?></button>
						<img class='loadergif hidden' src='<?php echo LOADERIMAGEURL; ?>'>
					</form>
				</div>
				<?php 
			} 
			?>
		</div>
		
		<?php
		
		return ob_get_clean();
	}

	/**
	 *
	 * Add a new plugin to the TinyMCE window to select an user and insert a user shortcode
	 *
	 * @param    array     $plugins	Array of existing plugins
	 * @return   array     			Array of new plugins
	 *
	**/
	function addTinymcePlugin($plugins) {
		wp_localize_script( 'sim_script', 
			'userSelect', 
			SIM\userSelect("Select a person to show the link to",true),
		);

		$plugins['select_user'] = plugins_url("../js/tiny_mce.js?ver=".MODULE_VERSION, __DIR__);
		
		return $plugins;
	}
	
	/**
	 *
	 * Add a new button to the TinyMCE window to select an user and insert a user shortcode
	 *
	 * @param    array     $buttons	Array of existing buttons
	 * @return   array     			Array of new buttons
	 *
	**/
	function registerButtons($buttons) {
		array_push($buttons, 'select_user');
		return $buttons;
	}
	
	/**
	 *
	 * Checks whether the current user has edit rights for the current post
	 *
	 * @return   boolean  true|false   
	 *  
	 *
	**/
	function hasEditRights(){
		//Only set this once
		if(!isset($this->editRight)){			
			$userPageId 	= SIM\maybeGetUserPageId($this->user->ID);
			
			$ministries 	= get_user_meta( $this->user->ID, "user_ministries", true);
			
			$postAuthor		= $this->post->post_author;
				
			//Check if allowed to edit this
			if(
				$postAuthor != $this->user->ID 									&& 
				!isset($ministries[str_replace(" ","_",$this->postTitle )])		&&  
				$userPageId != $this->ID										&&
				!$this->fullrights
			){
				$this->editRight	= false;
			}else{
				$this->editRight	= true;
			}

			$this->editRight	= apply_filters('sim_frontend_content_edit_rights', $this->editRight, $this->postCategory);
		}
	}
	
	/**
	 *
	 * Fills the submit form with existing option values of the current post 
	 *
	**/
	function fillPostData(){
		//Load existing post data
		if(is_numeric($this->postId)){
			// Check if there are pending changes
			$args = array(
				'post_parent' => $this->postId,
				'post_type'   => 'change',
				'post_status' => 'inherit',
			);
		
			$revisions = get_children( $args );

			if(empty($revisions)){
				$this->post 									= get_post($this->postId);
			// Load the first revision if there is one.
			}else{
				$this->post										= array_values($revisions)[0];
				$this->postId									= $this->post->ID;
			}
			$this->postParent 									= $this->post->post_parent;
			$this->postType 									= $this->post->post_type;
			if($this->postType == 'change'){
				$this->postType = get_post_type($this->postParent);
			}
			$this->postTitle 									= $this->post->post_title;
			$this->postContent 									= $this->post->post_content;
			$this->postImageId									= get_post_thumbnail_id($this->postId);
		}
		
		if(!empty($_GET['type'])){
			$this->postType 	= $_GET['type'];
		}
		
		$this->postName 										= str_replace("_lite","",$this->postType);
		$this->postCategory 									= $this->post->post_category;
		
		//show lite version of location by default
		if($this->postName == 'location' && $this->postContent == '' || strpos($this->postType, '_lite') !== false){
			$this->lite 		= true;
		}
		
		if($this->fullrights){
			if($this->postId == null || ($this->post->post_status != 'publish' && $this->post->post_status != 'inherit')){
				$this->action = "Publish <span class='replaceposttype'>{$this->postName}</span>";
			}else{
				$this->action = "Update <span class='replaceposttype'>{$this->postName}</span>";
			}
		}else{
			$this->action = "Submit <span class='replaceposttype'>{$this->postName}</span> for review";
		}
	}
	/**
	 *
	 * Prints pending changes to the screen
	 *
	**/
	function showChanges(){
		if($this->update && $this->post->post_type == 'change'){
			// Get changes in title and content
			if(!function_exists('wp_get_revision_ui_diff')){
				include_once ABSPATH . 'wp-admin/includes/revision.php';
			}

			$result		= wp_get_revision_ui_diff($this->post->post_parent, $this->post->post_parent, $this->post->ID);

			// Get changes in meta values
			$newMeta	= get_post_meta($this->postId);
			$oldMeta	= get_post_meta($this->postParent);

			//exclude certain keys
			$exclusion	= ['pending_notification_send', '_edit_lock'];
			foreach($exclusion as $exclude){
				if(isset($oldMeta[$exclude])){
					unset($oldMeta[$exclude]);
				}

				if(isset($newMeta[$exclude])){
					unset($newMeta[$exclude]);
				}
			}

			$added		= array_diff_assoc($newMeta, $oldMeta);
			SIM\cleanUpNestedArray($added, true);
			$removed	= array_diff_assoc($oldMeta, $newMeta);
			SIM\cleanUpNestedArray($removed, true);
			$changed	= [];

			foreach($added as $key=>$add){
				$changed[$key]	= ['old'=>$add[0], 'new'=>''];
			}

			foreach($removed as $key=>$del){
				$changed[$key]	= ['old'=>'', 'new'=>$del[0]];
			}
			
			foreach(array_intersect($newMeta, $oldMeta) as $key=>$value){
				if($oldMeta[$key] != $value){
					$newValue = maybe_unserialize($value[0]);
					$oldValue = maybe_unserialize($oldMeta[$key][0]);

					if(is_array($newValue)){
						foreach($newValue as $k=>$v){
							$newV	= maybe_unserialize($v);
							$oldV	= maybe_unserialize($oldValue[$k]);

							if($newV != $oldV){
								$changed[$k]	= ['old'=>$oldV, 'new'=>$newV];
							}
						}
					}elseif($newValue != $oldValue){
						$changed[$key]	= ['old'=>$oldValue, 'new'=>$newValue];
					}
				}
			}

			foreach($changed as $key=>$change){
				$diff	= wp_text_diff($change['old'], $change['new']);

				// picture id to picture html
				if($key == '_thumbnail_id'){
					if(is_numeric($change['old'])){
						$diff	= str_replace($change['old'], wp_get_attachment_image( $change['old']), $diff);
					}

					if(is_numeric($change['new'])){
						$diff	= str_replace($change['new'], wp_get_attachment_image( $change['new']), $diff);
					}
					$key	= 'Featured image';
				}

				$result[]	= array(
					'id'	=> 'post_meta',
					'name'	=> ucfirst(str_replace('_', ' ', $key)),
					'diff'	=> $diff
				);
			}

			?>
			<button type='button' class='button small show-diff'>Show what is changed</button>
			<fieldset class='post-diff-wrapper hidden'>
			<legend>
				<h4>Change list</h4>
			</legend>
				<?php
				foreach($result as $r){
					echo  "<h4>{$r['name']}</h4>";
					echo  $r['diff'];
				}
				?>
			</fieldset>
			<?php
		}
	}

	/**
	 *
	 * Show a selector to select or change the post type
	 *
	**/
	function postTypeSelector(){
		//do not show for lite posts
		if($this->lite){
			return;
		}

		// Only show type selector if we do not query a specific one
		if(!empty($_GET['type'])){
			return;
		}

		if($this->postId == null){
			$labelText = 'Select the content type you want to create:';
		}else{
			$labelText = "You are editing a {$this->postType}, use selector below if you want to change the post type";
		}

		$postTypes	= get_post_types(['public'=>true]);
		
		$html	= "<h4>$labelText</h4>";
		$html	.= "<select id='post_type_selector' name='post_type_selector' required>";

		foreach($postTypes as $postType){
			if($this->postType == $postType){
				$selected = 'selected';
			}else{
				$selected = '';
			}

			$typeName	= ucfirst($postType);
			if($postType == 'attachment'){
				$typeName = 'Picture/Video/Audio';
			}
			$html	.= "<option value='$postType' $selected>$typeName</option>";
		}
			
		$html	.=	"</select>";
	
		if(is_numeric($this->postId)){
			?>
			<form action="" method="post" name="change_post_type">
				<input type="hidden" name="userid" value="<?php echo  esc_html($this->user->ID); ?>">
				<input type="hidden" name="postid" value="<?php echo  esc_html($this->postId); ?>">
				<?php
				echo  $html;
				echo SIM\addSaveButton('change_post_type','Change the post type');
				?>
			</form>
			<?php
		}else{
			echo  $html;
		}
	}
	
	/**
	 *
	 * Add a modal form to add a new category for the selected post type
	 *
	**/
	function addModals(){
		$postTypes		= apply_filters('sim_frontend_posting_modals', ['attachment']);

		foreach($postTypes as $type){
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
					<form action="" method="post" id="add_<?php echo $type;?>_type_form" class="add_category">
						<p>Please fill in the form to add a new <?php echo $type;?> category</p>
						<input type="hidden" name="post_type" value="<?php echo $type;?>">
						<input type="hidden" name="userid" value="<?php echo $this->user->ID; ?>">
						
						<label>
							<h4>Category name<span class="required">*</span></h4>
							<input type="text"  name="cat_name" required>
						</label>
						
						<h4>Parent category</h4>
						<select class="" name='cat_parent'>
							<option value=''>---</option>
							<?php
							foreach($categories as $category){
								//Only ouptut categories without a parent
								if($category->parent == 0){
									echo "<option value='$category->cat_ID'>$category->name</opton>";
								}
							}
							?>
						</select>
						
						
						<?php echo SIM\addSaveButton('add_'.$type.'_type', "Add $type category"); ?>
					</form>
				</div>
			</div>
			<?php
		}
	}
	
	/**
	 *
	 * Show the post categories
	 *
	**/
	function postCategories(){
		$categories = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
		) );
		?>
		<div id="post-category" class="categorywrapper post page <?php if(!in_array($this->postType, ['post', 'page', 'attachment'])){echo 'hidden';} ?>">
			<h4>
				<span class="capitalize replaceposttype"><?php echo  esc_html($this->postType);?></span> category
			</h4>
			<div class='categorieswrapper'>
				<?php
				foreach($categories as $category){
					$name 			= $category->name;
					$catId 			= $category->cat_ID;
					$catDescription	= $category->description;
					$class			= 'infobox post';

					if($catId == get_cat_ID('Public') || $catId == get_cat_ID('Confidential') ){
						$class	.= 'page';
					//do not show categories other than public and confidential to non-post types
					}elseif($this->postType != 'post'){
						$class	.= ' hidden';
					}

					$checked	= '';
					if(is_array($this->postCategory) && in_array($catId, $this->postCategory)){
						$checked 	= 'checked';
					}
					
					echo "<div class='$class'>";
					
						echo "<input type='checkbox' name='category_id[]' value='$catId' $checked>";
					
						echo "<label class='option-label category-select'>$name</label>";

						if(!empty($catDescription)){
							echo "<span class='info_text'>$catDescription</span>";
						}

					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
	}
	
	/**
	 *
	 * Adds fields specific for the post post_type
	 *
	**/
	function postSpecificFields(){
		?>
		<div id="post-attributes"  class="post<?php if($this->postType != 'post'){echo ' hidden';}?>">
			<div id="expirydate_div" class="frontendform">
				<h4>Expiry date</h4>
				<label>
					<input type='date' class='' name='expirydate' min="<?php echo date("Y-m-d"); ?>" value="<?php echo esc_html(get_post_meta($this->postId, 'expirydate', true)); ?>" style="display: unset; width:unset;">
					Set an optional expiry date of this post
				</label>
			</div>
		</div>
		<?php
	}
	
	/**
	 *
	 * Adds fields specific for the page post_type
	 *
	**/
	function pageSpecificFields(){
		?>		
		<div id="page-attributes" class="page<?php if($this->postType != 'page'){echo ' hidden';}?>">
			<div id="parentpage" class="frontendform">
				<h4>Select a parent page</h4>
				<?php 
				echo SIM\pageSelect('parent_page', $this->postParent);
				?>
			</div>

			<?php
			do_action('sim_page_specific_fields', $this->postId);
			?>			
			<div id="static_content" class="frontendform">
				<h4>Update warnings</h4>	
				<label>
					<input type='checkbox' name='static_content' value='static_content' <?php if(get_post_meta($this->postId,'static_content',true) != ''){echo 'checked';}?>>
					Do not send update warnings for this page
				</label>
			</div>
		</div>
		<?php
	}
	
	/**
	 *
	 * Display the categories for a specific post_type
	 *
	 * @param    string     $type		The post_type the category is for
	 * @param    array     	$categories	Array of categories
	 *
	**/
	function showCategories($type, $categories){
		?>
		<div class="<?php echo $type; if($this->postType != $type){echo ' hidden';} ?>">
			<div class="frontendform">
				<h4><?php echo ucfirst($type);?> type</h4>
				<div class='categories'>
					<?php
					$parentCategoryHtml 	= '';
					$childCategoryHtml 		= '';
					$hidden					= 'hidden';
				
					foreach($categories as $category){
						$name 				= ucfirst($category->slug);
						$catId 				= $category->cat_ID;
						$catDescription		= $category->description;
						$parent				= $category->parent;
						$checked			= '';
						$class				= 'infobox';
						$taxonomy			= $category->taxonomy;
						
						//This category is a not a child
						if($parent == 0){
							$html = 'parentCategoryHtml';
						//has a parent
						}else{
							$html = 'childCategoryHtml';
						}
						
						//if this cat belongs to this post
						if(has_term($catId, $taxonomy, $this->postId)){
							$checked = 'checked';
							
							//If this type has child types, show the label
							if(count(get_term_children($category->cat_ID, $taxonomy))>0){
								$hidden = '';
							}
						}
						
						//if this is a child, hide it and attach the parent id as attribute
						if($parent != 0){
							//Hide subcategory if parent is not in the cat array
							if(!has_term($parent, $taxonomy, $this->postId)){
								$class .= " hidden";
							}
							
							//Store cat parent
							$class .= "' data-parent='$parent";
						}
						
						//$$html --> use the value of $html as variable name
						$$html .= "<div class='$class'>";
							$checkboxClass = "{$type}type";
							if(count(get_term_children($category->cat_ID, $taxonomy)) > 0){
								$checkboxClass .= " parent_cat";
							}
							$$html .= "<input type='checkbox' class='$checkboxClass' name='{$taxonomy}_ids[]' value='$catId' $checked>";
						
							//Name of the category
							$$html .= "<label class='option-label category-select'>$name</label>";
							
							//Add infobox if needed
							if(!empty($catDescription)){
								$$html .= "<span class='info_text'>$catDescription</span>";
							}

						$$html .= '</div>';
					}
					
					?>
					<div id='<?php echo $type;?>_parenttypes'>
						<?php
						echo $parentCategoryHtml;
						?>
						<button type='button' name='add_<?php echo $type;?>_type_button' class='button add_cat' data-type='<?php echo $type;?>'>Add category</button>
					</div>
					
					<label id='subcategorylabel' class='frontend-profile-label <?php echo $hidden ?>'>Sub-category</label>
					
					<div id='<?php echo $type;?>_childtypes' class='childtypes'>
						<?php
						echo $childCategoryHtml;
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 * Adds options specific for content managers
	 *
	**/
	function contentManagerOptions(){		
		if($this->fullrights){
			$hidden			= '';
		}else{
			$hidden			= 'hidden';
		}
		
		$buttontext			= 'Show';
		
		if(empty($hidden)){
			$buttontext		= 'Hide';
		}
		
		?>
		<button type="button" class="button" id="advancedpublishoptionsbutton" style='display:block; margin-top:15px;'><span><?php echo $buttontext;?></span> advanced options</button>
		
		<div class="advancedpublishoptions <?php echo $hidden;?>">
			<?php	
			// Show change author dropdown 
			$authorId	= $this->post->post_author;
			if(!is_numeric($authorId)){
				$authorId = $this->user->ID;
			}

			echo SIM\userSelect('Author', true, false, '', 'post_author', [], $authorId);
			
			// Only show publish date if not yet published
			if(!in_array($this->post->post_status, ['publish', 'inherit'])){
				if(empty($this->post)){
					$publishDate	= date("Y-m-d");
				}else{
					$publishDate	= max(date("Y-m-d", strtotime($this->post->post_date)), date("Y-m-d"));
				}

				?>
				<label>
					<h4>Publishing date</h4>
					<input type="date" min="<?php echo date("Y-m-d");?>" name="publish_date" value="<?php echo $publishDate;?>">
					Define when the content should be published
				</label>
				<?php
			}

			$this->postSpecificFields();
			
			$this->pageSpecificFields();
				
			do_action('sim_frontend_post_after_content', $this);
			?>
		</div>
		<?php
	}
	
	/**
	 *
	 * saves base64 images as images and adds them to the library
	 *
	 * @param    array     $matches	Array of matches from a regex
	 * @return   string     		Image url
	 *
	**/
	function uploadImages($matches) {
		$ext 			= $matches[1];
		$filename 		= "frontend_picture";
		$basedir 		= wp_upload_dir()['basedir'];
		$newFilePath 	= "$basedir/$filename.$ext";
		$i 				= 0;
		$uploadId		= 0;
		
		//Find an available filename
		while( file_exists( $newFilePath ) ) {
			$i++;
			$newFilePath = "$basedir/$filename"."_$i.$ext";
		}
		
		//Decode the base64
		$fileContents = base64_decode(substr_replace($matches[2] ,"",-1));
		
		//Only continue if the decoding was succesfull
		if( $fileContents !== false){
			//Save the image in the uploads folder
			file_put_contents($newFilePath, $fileContents);
			
			SIM\addToLibrary($newFilePath);
		}else{
			SIM\printArray('Not a valid image');
		}
		
		//Return the image url
		$url = wp_get_attachment_image_url($uploadId,'');
		return '"'.$url;
	}

	/**
	 * Store categories of custom post type
	 * 
	 * @param	string	$taxonomy	The name of the categorie taxonomy
	 * 
	 */
	function storeCustomCategories($post, $taxonomy){
		$cats = [];
		if(is_array($_POST[$taxonomy.'_ids'])){
			foreach($_POST[$taxonomy.'_ids'] as $catId) {
				if(is_numeric($catId)){
					$cats[] = $catId;
				}
			}
			
			//Make sure we only send integers
			$cats = array_map( 'intval', $cats );
			
			// Store
			wp_set_post_terms($post->ID, $cats, $taxonomy);
		}
	}

	/**
	 *
	 * Saves or publishes a new post or updates an existing one
	 *
	 * @param    string     $status	Desired post status
	 * @return   string|WP_Error     		Result message
	 *
	**/
	function submitPost($status=''){
		if($_POST['post_status'] == 'draft'){
			$status = 'draft';
		}elseif(empty($status)){
			if($this->fullrights){
				if(!isset($_POST['publish_date']) || $_POST['publish_date'] == date('Y-m-d')){
					$status = 'publish';
				}else{
					$status = 'future';
				}
			}else{
				$status = 'pending';
			}
		}
		
		$this->postType 	= sanitize_text_field($_POST['post_type']);
		
		//First letter should be capital in the title
		$this->postTitle 	= ucfirst(sanitize_text_field($_POST['post_title']));
		
		$postContent 		= $_POST['post_content'];
		
		//Find any base64 encoded images in the post content and replace the url
		$postContent 	= preg_replace_callback('/"data:image\/(\w+);base64,([^"]*)/m', array($this,'uploadImages'), $postContent);

		// Check if content is just an hyperlink
		//find all urls in the page
		$regex 	= '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?????????????????]))';
		preg_match_all("#$regex#i", $postContent, $matches);
		$url 	= $matches[0][0];

		//if the url is the only post content
		if($url == strip_tags($postContent)){
			//find the post id of the url
			$postId	= url_to_postid($url);

			// If a valid post id
			if($postId > 0){
				$postContent	= "[showotherpost postid='$postId']";
			}
		}
		
		//Find display names in content
		$users = SIM\getUserAccounts(false,false,true);
		foreach($users as $user){
			$privacyPreference = get_user_meta( $user->ID, 'privacy_preference', true );
			//only replace the name with a link if privacy allows
			if(empty($privacyPreference['hide_name'])){
				//Replace the name with a hyperlink
				$url			= SIM\maybeGetUserPageUrl($user->ID);
				$link			= "<a href='$url'>{$user->display_name}</a>";
				$postContent	= str_replace($user->display_name, $link, $postContent);
			}
		}
		
		//Sanitize the post content
		$postContent = wp_kses_post($postContent);
		
		$categories = [];
		if(is_array($_POST['category_id'])){
			foreach($_POST['category_id'] as $categoryId) {
				if(!empty($categoryId)){
					$categories[] = $categoryId;
				}
			}
		}
			
		//Check if editing an existing post
		if(is_numeric($_POST['post_id'])){
			$this->postId = $_POST['post_id'];

			//Retrieve the old post data
			$post = get_post($this->postId);

			// Check if this is a post revison
			if($this->fullrights && $post->post_type == 'change'){
				// delete revision
				$delete = wp_delete_post( $this->postId );
				if ( $delete ) {
					do_action( 'wp_delete_post_revision', $this->postId, $post);
				}

				// Use parent page as post id
				$this->postId	= $post->post_parent;

				// Load parent post data
				$post = get_post($this->postId);
			}
			
			$this->update = true;
			
			$newPostData = ['ID'=>$this->postId];
			
			//Check for updates
			if($this->postTitle != $post->post_title){
				//title
				$newPostData['post_title'] 	= $this->postTitle;

				// name
				$postName	= urldecode($this->postTitle);

				//check if name is unique as it used as slug
				$args	= array(
					'post_type'		=> get_post_types(),
					'post_status'	=> 'any',
					'name'          => $postName,
					'numberposts'	=> -1,
				);
				$posts	= get_posts( $args);

				$i=1;
				while(!empty($posts)){
					$postName	= urldecode($this->postTitle.'_'.$i);
					$args['name']	= $postName;
					$i++;
					$posts	= get_posts( $args);
				}

				$newPostData['post_name'] 	= $postName;

				//attached file
				if($_POST['post_type'] == 'attachment' && explode('/', $post->post_mime_type)[0] == 'video'){
					$newPostData['_wp_attached_file'] 	= $this->postTitle;
				}
			}
			if($postContent != $post->post_content){
				$newPostData['post_content'] 	= $postContent;
			}

			if($status != $post->post_status){
				$newPostData['post_status'] 	= $status;
			}

			if( $_POST['post_author'] != $post->post_author){
				$newPostData['post_author']		= $_POST['post_author'];
			}

			if($_POST['parent_page'] != $post->post_parent){
				$newPostData['post_parent'] 	= $_POST['parent_page'];
			}

			if($categories != $post->post_category){
				$newPostData['post_category'] 	= $categories;
			}

			//we cannot change the post type here
			if($post->post_type != $this->postType && $post->post_type != 'revision'){
				return new WP_Error('frontend_contend', 'You can not change the post type like that!');
			}
			
			//Create a revision post
			if($status == 'pending'){
				$actionText = 'updated'; 

				foreach($newPostData as $key=>$data){
					$post->$key	= $data;
				}

				// Mark new post as inherit
				$post->post_status	= 'inherit';
				$post->post_name	= $post->ID.'-revision-v1';
				$post->post_parent	= $post->ID;
				$post->post_type	= 'change';
				unset($post->ID);

				// Insert the post into the database.
				$postId 	= wp_insert_post( $post, true, false);

				$post->ID	= $postId;
			//Update the post only if we have the rights to so
			}else{
				$result = wp_update_post($newPostData, true, false);
				if(is_wp_error($result)){
					return new WP_Error('Update failed', $result->get_error_message());
				}elseif($post->post_status == 'draft' && $status == 'publish'){
					$actionText = 'published';
				}else{
					$actionText = 'updated';
				}
			}
		}else{
			$this->update	= false;
			$actionText	= 'created';

			//New post
			$post = array(
				'post_type'		=> $this->postType,
				'post_title'    => $this->postTitle,
				'post_content'  => $postContent,
				'post_status'   => $status,
				'post_author'   => $_POST['post_author']
			);

			if($this->postType == 'attachment'){
				$this->postId 	= SIM\addToLibrary(SIM\urlToPath($_POST['attachment'][0]), $this->postTitle, $postContent);
				$post['ID']	= $this->postId;
			}else{				
				if(is_numeric($_POST['parent_page'])){
					$post['post_parent'] = $_POST['parent_page'];
				}
			
				if(!empty(count($categories))){
					$post['post_category'] = $categories;
				}

				//Schedule the post
				if($_POST['publish_date'] != date('Y-m-d')){
					$publishDate			= date("Y-m-d 08:00:00", strtotime($_POST['publish_date']));

					$post['post_date'] 		= $publishDate;
					$post['post_date_gmt'] 	= $publishDate;
				}
				
				// Insert the post into the database.
				$this->postId 	= wp_insert_post( $post,true,false);
				$post['ID']		= $this->postId;
			}
			
			if(is_wp_error($this->postId)){
				return new WP_Error('Inserting post error', $this->postId->get_error_message());
			}elseif($this->postId === 0){
				return new WP_Error('Inserting post error', "Could not create the $this->postType!");
			}
		}
		
		$url 		= get_permalink($this->postId);
		
		//Set the featured image
		if(is_numeric($_POST['post_image_id'])){
			set_post_thumbnail($this->postId, $_POST['post_image_id']);
		}
		
		//Static content
		if(isset($_POST['static_content'])){
			update_metadata( 'post', $this->postId, 'static_content', true);		
		}else{
			delete_post_meta($this->postId, 'static_content');
		}
		
		//Expiry date
		if(isset($_POST['expirydate'])){
			if(empty($_POST['expirydate'])){
				delete_post_meta($this->postId, 'expirydate');
			}else{
				//Store expiry date
				update_metadata( 'post', $this->postId, 'expirydate', $_POST['expirydate']);
			}
		}

		if($post->post_status == 'pending'){
			sendPendingPostWarning($post, $this->update);
		}

		//store attachment categories
		$this->storeCustomCategories($post, 'attachment_cat');
		
		do_action('sim_after_post_save', (object)$post, $this);
		
		//Return result
		if($status == 'publish'){
			return "Succesfully $actionText the $this->postType, view it <a href='$url'>here</a>";
		}elseif($status == 'draft'){
			return "Succesfully $actionText the draft for this $this->postType, preview it <a href='$url'>here</a>";
		}elseif($_POST['publish_date'] > date('Y-m-d') && $status == 'future'){
			return "Succesfully $actionText the $this->postType, it will be published on ".date('d F Y', strtotime($_POST['publish_date'])).' 8 AM';
		}else{
			return "Succesfully $actionText the $this->postType, it will be published after it has been reviewed";			
		}
	}
	
	/**
	 *
	 * Removes an existing post
	 * @return   string|WP_Error     		Result message
	 *
	**/
	function removePost(){
		$postId = $_POST['post_id'];
		
		$post		= wp_trash_post($postId);
		
		$postType	= get_post_type($post);
		
		if($postType){
			return "Succesfully deleted $postType '{$post->post_title}'<br>You can leave this page now";
		}else{
			return new WP_Error('Post removal error', 'Something went wrong');
		}
	}	
	
	/**
	 *
	 * Change the type of an existing post
	 *
	 * @return   string|WP_Error     		Result message
	 *
	**/
	function changePostType(){
		$postType	= $_POST['post_type_selector'];
		
		$postId	= $_POST['postid'];

		$result		= set_post_type($postId, $postType);
		if($result){
			return "Succesfully updated the type to $postType";
		}else{
			return new WP_Error('Update failed', "Could not update the type");
		}
	}

}
