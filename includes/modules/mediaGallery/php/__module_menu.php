<?php
namespace SIM\MEDIAGALLERY;
use SIM;

use function SIM\printArray;

const MODULE_VERSION		= '7.0.36';
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
		This module adds a media gallery of downloadable pictures, video's and audio files.
	</p>
	<?php
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'mediagallery_pages');
	if(!empty($url)){
		?>
		<p>
			<strong>Auto created page:</strong><br>
			<a href='<?php echo $url;?>'>Media gallery</a><br>
		</p>
		<?php
	}

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<label>Select the categories you do not want to be selectable</label>
	<br>
	<?php
	$categories	= get_categories( array(
		'orderby' 		=> 'name',
		'order'   		=> 'ASC',
		'taxonomy'		=> 'attachment_cat',
		'hide_empty' 	=> false,
	) );

	foreach($categories as $category){
		$checked	= '';
		if(is_array($settings['categories']) && in_array($category->slug, $settings['categories'])){
			$checked	= 'checked';
		}
		echo "<label>";
			echo "<input type='checkbox' name='categories[]' value='$category->slug' $checked>";
			echo $category->name;
		echo "</label><br>";
	}
	?>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_functions', function($functionHtml, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $functionHtml;
	}
	
	ob_start();
	?>
	<h4>Duplicate files</h4>
	<?php
	if(isset($_POST['fix-duplicates'])){
		$removed	=	duplicateFinder(wp_upload_dir()['basedir'], wp_upload_dir()['basedir'].'/private');

		if(empty($removed)){
			echo "<div class='success'>There was nothing to remove</div>";
		}else{
			?>
			<div class='success'>
				Succesfully removed the following files:<br>
				<?php
				foreach($removed as $path){
					echo "$path<br>";
				}
				?>
			</div>
			<?php
		}
	}elseif(isset($_POST['scan-for-orphans'])){
		checkOrphanMedia();
	}elseif(!empty($_POST['delete'])){
		if(!empty($_POST['path'])){
			// move all to recycle bin
			$path	= $_POST['path'];
			moveAttachmentToRecycleBin($path);

			echo "<div class='success'>Attachment succesfully deleted</div>";
		}

		if($_POST['delete'] == 'delete-all' && !empty($_POST['paths'])){
			$paths	= json_decode(SIM\deslash($_POST['paths']));

			foreach($paths as $path){
				moveAttachmentToRecycleBin($path);
			}

			$count	= count($paths);
			echo "<div class='success'>$count attachments succesfully deleted</div>";
		}
	}elseif(!empty($_POST['used']) && is_numeric($_POST['id'])){
		$excludeIds[]	= $_POST['id'];
		update_option('excludeAttachmentIds', $excludeIds);
	}elseif(!empty($_POST['ignore']) && !empty($_POST['table'])){
		$excludedTables[]	= $_POST['table'];
		update_option('excludedAttachmentTables', $excludedTables);
	}
	?>
	<p>
		Scan and remove duplicate files in the uploads and uploads/private folder.
	</p>
	<form method='POST'>
		<button type='submit' name='fix-duplicates'>Remove duplicate files</button>
	</form>
	<p>
		Scan for pictures and other media which are not referenced in any content
	</p>
	<form method='POST'>
		<button type='submit' name='scan-for-orphans'>Scan for orphan attachments</button>
	</form>
	<?php
	return ob_get_clean();
}, 10, 2);


add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create account page
	$options	= SIM\ADMIN\createDefaultPage($options, 'mediagallery_pages', 'Media Gallery', '[mediagallery]', $oldOptions);

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) {

	if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'mediagallery_pages', false)) ) {
		$states[] = __('Media gallery page');
	}

	return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	foreach($options['mediagallery_pages'] as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);


/**
 * Checks for duplicate files in a given dir
 *
 * @param	string	$dir	the directory to scan
 * @param	string	$dir2	An optional second directory to scan
 */
function duplicateFinder($dir, $dir2=''){
	global $wpdb;

	$dir	= trim($dir, '/\\');
	//get a files array
	$files = array_filter(glob($dir.'/*'), 'is_file');

	$logoId			= get_option('site_logo');
	$iconId			= get_option('site_icon');

	if(!empty($dir2)){
		$dir2	= trim($dir2, '/\\');
		$files = array_merge($files, array_filter(glob($dir2.'/*'), 'is_file'));
	}

	unset(
		$files[array_search('.',$files)],
		$files[array_search('..', $files)]
	);

	//get an array of file hashes
	$hashArr = [];
	foreach ($files as $file) {
		$fileHash 		= md5_file($file);
		if($fileHash){
			$hashArr[$file] = $fileHash;
		}
	}

	// Get an array of unique hashes
	$unique 		= array_unique($hashArr);
	// get all unique hashes who have a duplicate
	$duplicates 	= array_unique(array_diff_assoc($hashArr, $unique));

	$removed		= [];

	// Loop over all duplicates
	foreach($duplicates as $duplicate){
		// get all files with this hash
		$dubs	= array_filter($hashArr, function($value)use($duplicate){
			return $value	== $duplicate;
		});

		$paths	= array_keys($dubs);
		rsort($paths);

		// Only keep the first
		$newPath	= $paths[0];
		unset($paths[0]);

		// Remove the rest
		foreach($paths as $key=>$path){
			// delete the attachment
			$attachmentId	= attachment_url_to_postid(SIM\pathToUrl($path));
			if(!$attachmentId){
				// remove the file
				unlink($path);
			}else{
				// Update the attachment id
				$newPostId		= attachment_url_to_postid(SIM\pathToUrl($newPath));

				$postIds		= $wpdb->get_results("SELECT post_id from $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value=$attachmentId", ARRAY_N);

				// post with this attachment as featured image is found
				if(!empty($postIds)){
					foreach($postIds as $postId){
						set_post_thumbnail($postId[0], $newPostId);
					}
				}

				// Check if used as profile image
				$userIds	= $wpdb->get_results("SELECT user_id from $wpdb->usermeta WHERE meta_key='profile_picture' AND meta_value=$attachmentId");
				if(!empty($userIds)){
					foreach($userIds as $userId){
						update_user_meta($userId, 'profile_picture', $newPostId);
					}
				}

				// Check if used as logo
				if($attachmentId == $logoId){
					update_option('site_logo', $newPostId);
				}
				
				// Check if used as icon
				if($attachmentId == $iconId){
					update_option('site_icon', $newPostId);
				}

				// remove the file and the attached db entries
				wp_delete_attachment($attachmentId);
			}

			// update all references to it
			SIM\urlUpdate($path, $newPath);
		}

		$removed	= array_merge($removed, $paths);
	}

	$removed	= array_unique($removed);
	sort($removed);
	return $removed;
}

/**
 * Move an attachment to the attachment recycle folder
 */
function moveAttachmentToRecycleBin($path){
	$recycleBin	= WP_CONTENT_DIR.'/attachment-recylce';
	if (!is_dir($recycleBin)) {
		mkdir($recycleBin, 0777, true);
	}

	preg_match('/(.*)-\d{2,4}x\d{2,4}(\..*)/i', $path, $matches);
	if(isset($matches[1])){
		$path	= $matches[1];
	}
	$paths			= array_filter(glob($path.'*'), 'is_file');

	if(is_array($paths)){
		foreach($paths as $path){
			rename($path, $recycleBin.'/'.basename($path));
		}
	}
}

/**
 * Finds media which is not used anywhere
 */
function checkOrphanMedia(){
	if(!current_user_can('delete_published_posts')){
		return;
	}

	global $wpdb;

	$excludeIds		= get_option('excludeAttachmentIds', []);
	$excludedTables	= get_option('excludedAttachmentTables', []);

	$dir			= wp_upload_dir()['basedir'];
	$files			= array_merge(glob($dir.'/*'), glob($dir.'/private/*'));
	$dirs			= array_filter($files, function($file){
		if(is_dir($file) && !in_array(basename($file), ['form_uploads', 'account_statements', 'profile_pictures', 'visa_uploads'])){
			return true;
		}
		return false;
	});

	$paths			= array_filter($files, 'is_file');
	foreach($dirs as $d){
		$paths			= array_merge($paths, array_filter(glob($d.'/*'), 'is_file'));
	}
	$orphans		= [];
	$processed		= [];
	$attachmentIds	= [];
	$logoId			= get_option('site_logo');
	$iconId			= get_option('site_icon');

	foreach($paths as $key=>$path){
		$ext	= pathinfo($path)['extension'];

		// only process the base image not all formats
		preg_match('/(.*)-\d{2,4}x\d{2,4}(\..*)/i', $path, $matches);

		if(isset($matches[1])){
			$path	= $matches[1];
		}

		if(in_array($path, $processed) || in_array(str_replace('.'.$ext, '', $path), $processed)){
			continue;
		}

		$processed[]	= $path;

		// check if url shows up anywhere in post content
		$query = new \WP_Query( array( 's' => str_replace(ABSPATH, '', $path)) );

		// url is found in at least one post
		if($query->post_count > 0){
			continue;
		}

		// add the extension
		if(isset($matches[2])){
			$path	.= $matches[2];

			if(!file_exists($path)){
				$path	= $matches[0];
			}
		}

		// check if attachment id shows up anywhere in the db
		$postId			= attachment_url_to_postid(SIM\pathToUrl($path));
		$featuredImage	= !empty($wpdb->get_results("SELECT post_id from $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value=$postId"));
		$profileImage	= !empty($wpdb->get_results("SELECT user_id from $wpdb->usermeta WHERE meta_key='profile_picture' AND meta_value=$postId"));

		if(in_array($postId, $excludeIds) || $featuredImage || $profileImage || $postId == $logoId || $postId == $iconId){
			continue;
		}else{
			// search db for url
			$results	= SIM\searchAllDB(
				SIM\pathToUrl($path),
				array_merge($excludedTables, [$wpdb->postmeta, $wpdb->prefix.'sim_statistics']),
				['guid']
			);

			// search db for postId
			$results	= array_merge($results, SIM\searchAllDB(
				$postId,
				array_merge($excludedTables, [$wpdb->postmeta, $wpdb->prefix.'sim_statistics']),
				['ID', 'meta_id', 'post_id', 'id', 'email_id', 'postId', 'log_id', 'object_id', 'mediaId', 'umeta_id', 'action_id', 'option_id', 'user_id', 'hitID']
			));

			if(empty($results)){
				$orphans[] = $path;
			}else{
				$attachmentIds[$postId]	= array_values($results);
			}
		}
	}

	if(!empty($orphans)){
		$count	= count($orphans);
		echo "<h1>Orphan media ($count)</h1>";
		
		?>
		<form method='post' style='margin-bottom:10px;'>
			<input type='hidden' name='paths' value='<?php echo json_encode($orphans);?>'>
			<button class='button' name='delete' value='delete-all'>Delete all <?php echo $count;?> orphan attachments</button>
		</form>
		<?php

		foreach($orphans as $orphan){
			?>
			<div>
				<?php
					$url	= SIM\pathToUrl($orphan);
					$name	= basename($orphan);
					if(@is_array(getimagesize($orphan))){
						echo "<a href='$url'><img src='$url' alt='$name' width='100' height='100' title='$orphan' loading='lazy'></a>";
						echo "<span>".basename($orphan)."</span>";
					} else {
						echo "<a href='$url' title='$orphan'>$name</a>";
					}
				?>
				
				<form method='post' style='display: inline-block;'>
					<input type='hidden' name='path' value='<?php echo $orphan;?>'>
					<input type='submit' class='button' name='delete' value='Delete'>
				</form>
			</div>
			<?php
		}
	}

	if(!empty($attachmentIds)){
		?>
		<h1>Referenced media</h1>
		<table>
			<thead>
				<tr>
					<th>Attachment</th>
					<th>Table</th>
					<th>Column</th>
					<th>Value</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($attachmentIds as $attachmentId=>$data){
					$type	= explode('/', get_post_mime_type($attachmentId))[0];
					if($type == 'image'){
						$html	= wp_get_attachment_image( $attachmentId );
					}else{
						$url	= wp_get_attachment_url($attachmentId);
						$title	= get_the_title($attachmentId);
						$html	= "<a href='$url'>$title</a>";
					}

					foreach($data as $index=>$table){
						echo "<tr>";
							if($index == 0){
								$rowspan	= count($data);
								echo "<td rowspan='$rowspan' style='max-width: 300px;'>$html</td>";
							}

							// data
							foreach($table as $value){
								echo "<td>$value</td>";
							}

							// actions
							?>
							<td>
								<form method='post' style='margin-bottom:10px;'>
									<input type='hidden' name='id' value='<?php echo $attachmentId;?>'>
									<input type='hidden' name='table' value='<?php echo $table['table'];?>'>
									<button class='button' name='ignore' value='ignore'>Ignore this table</button>
								</form>
								<form method='post'>
									<input type='hidden' name='id' value='<?php echo $attachmentId;?>'>
									<input type='submit' class='button' name='used' value='Mark as used'>
								</form>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}
}