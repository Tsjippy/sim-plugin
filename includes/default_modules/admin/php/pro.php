<?php
namespace SIM\ADMIN;
use SIM;

/**
 * Download new modules or delete them
 */
function mainMenuActions(){
	global $Modules;
	global $moduleDirs;

	if(!empty($_GET['update'])){
		if($_GET['update'] == 'all'){
			SIM\GITHUB\checkForModuleUpdates();
	
			?>
			<div class='success'>All modules updated successfully</div>
			<?php

			return;
		}

		$slug		= sanitize_text_field($_GET['update']);

		$github		= new SIM\GITHUB\Github();

		$result		= $github->downloadFromGithub('Tsjippy', $slug, SIM\MODULESPATH.$slug, true);

		if(is_wp_error($result)){
			echo "<div class='error'>".esc_html($result->get_error_message())."</div>";
		}elseif($result){
			?>
			<div class="success">
				Module <?php echo esc_attr($slug);?> succesfully updated
			</div>
			<?php

			$moduleDirs[$slug]	= $slug;
		}else{
			?>
			<div class="error">';
				Module <?php echo esc_attr($slug);?> not found on github.<br><br>
				<?php
				if(!$github->authenticated){
					$url            = admin_url( "admin.php?page=sim_github&main-tab=settings" );
					?>
					maybe you <a href='<?php echo esc_url($url);?>'>should supply a github token</a> so I can try again while logged in.
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	if(!empty($_GET['download'])){
		$slug		= sanitize_text_field($_GET['download']);

		$github		= new SIM\GITHUB\Github();

		$result		= $github->downloadFromGithub('Tsjippy', $slug, SIM\MODULESPATH.$slug, true);

		if(is_wp_error($result)){
			echo "<div class='error'>".esc_attr($result->get_error_message())."</div>";
		}elseif($result){
			?>
			<div class="success">
				Module <?php echo esc_attr($slug);?> succesfully downloaded
			</div>
			<?php

			$moduleDirs[$slug]	= $slug;
		}else{
			?>
			<div class="error">
				Module <?php echo esc_attr($slug);?> not found on github.<br><br>
				<?php
				if(!$github->authenticated){
					$url            = admin_url( "admin.php?page=sim_github&main-tab=settings" );
					?> maybe you <a href='<?php echo esc_url($url);?>'>should supply a github token</a> so I can try again while logged in.
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	if(!empty($_GET['remove'])){
		$slug		= sanitize_text_field($_GET['remove']);

		if(isset($Modules[$slug])){
			unset($Modules[$slug]);

			update_option('sim_modules', $Modules);
		}

		if(isset($moduleDirs[$slug])){
			WP_Filesystem();
			global $wp_filesystem;
			$result				= $wp_filesystem->rmdir($moduleDirs[$slug], true);

			if($result){

				unset($moduleDirs[$slug]);
				
				?>
				<div class="success">
					Module <?php echo esc_attr($slug);?> succesfully removed
				</div>
				<?php
			}else{
				?>
				<div class="error">
					Module <?php echo esc_attr($slug);?> removal unsuccesfull
				</div>
				<?php
			}
		}
	}
}

/**
 * The main plugin menu
 */
function mainMenuPro(){
	global $Modules;
	global $moduleDirs;

	mainMenuActions();

	$active		= [];
	$inactive	= [];
	$missing	= [];

	// merge the available modules and the installed modules to allow custom modules
	$moduleList	= SIM\MODULELIST;
	foreach($moduleDirs as $path){
		$moduleName	= basename($path);

		if(!in_array($moduleName, $moduleList)){
			$moduleList[]	= $moduleName;
		}
	}

	sort($moduleList);

	foreach($moduleList as $moduleName){
		$moduleSlug	= strtolower($moduleName);
		$moduleName	= SIM\getModuleName($moduleName, ' ');

		// activated and files downloaded
		if(isset($Modules[$moduleSlug]['enable'])){
			if(isset($moduleDirs[$moduleSlug])){
				$active[$moduleSlug]	= $moduleName;
			}
		}else{
			$inactive[$moduleSlug]	= $moduleName;
		}
	}
	
	foreach(array_keys($Modules) as $moduleName){
		if(!in_array($moduleName, array_keys($moduleDirs))){
			$missing[]	= $moduleName;
		}
	}

	$github		= new SIM\GITHUB\Github();
	
	ob_start();

	$updatesAvailable	= false;
	?>
	<div id='release-modal' class='modal hidden'>
		<div class="modal-content" style='width:500px;'>
			<span id="modal-close" class="close">&times;</span>
			<div class="loader-image-trigger"></div>
			<div class="content"></div>
		</div>
	</div>
	<div>
		<strong>Current active modules</strong><br>
		<table class="table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Version</th>
				</tr>
			</thead>
			
			<?php
			foreach($active as $slug=>$name){
				$url		= admin_url("admin.php?page=".$_GET['page']);
				$release	= '';
				$update		= false;
				$content	= '';
				if( defined("SIM\\$slug\\MODULE_VERSION")){
					$content	= constant("SIM\\$slug\\MODULE_VERSION");
				}

				// Default module version
				if(!empty($moduleDirs[$slug]) && str_contains($moduleDirs[$slug], 'default_modules')){

				}

				// Skip the update check if just updated
				elseif(empty($_GET['update']) || ($_GET['update'] != $slug && $_GET['update'] != 'all')){
					// Check if update available
					$force		= false;
					if(!empty($_GET['force'])){
						$force	= true;
					}
					$release	= $github->getLatestRelease('tsjippy', $slug, $force);

					
					if( 
						!is_wp_error($release) &&													// no error during the getting the release info
						isset($release['tag_name'])													// release has a tagname
					){
						// the module version for this module is set
						if( !empty($content)){
							if( 
								version_compare($release['tag_name'], $content)	// the release version is bigger than the current version
							){
								$update	= true;
							}
						}else{
							$update	= true;
						}
					}
				}

				?>
				<tr>
					<td>
						<a href='<?php echo esc_url("{$url}_$slug");?>'>
							<?php echo esc_attr($name);?>
						</a>
					</td>
					<?php
					if($update){
						$updatesAvailable	= true;
						$content 		   .= " <a href='$url&update=$slug' class='button sim small' style='margin-left:15px;margin-right:15px;'>Update to version {$release['tag_name']}</a>";
						$content 		   .= "<button type='button' class='sim small release' data-name='$slug'>Show info</button>";
					}
					echo "<td>$content</td>";
				echo "</tr>";
			}
			?>
		</table>

		<br>
		<strong>Currently not installed modules</strong><br>
		<?php
			if(empty($inactive)){
				echo "All modules are activated";
			}else{
				?>
				<table class="table">
					<?php
					foreach($inactive as $slug=>$name){
						echo "<tr>";
							echo "<td><a href='https://github.com/Tsjippy/$slug' target='_blank'>$name</a></td>";
							// Module is downloaded but inactive
							if(in_array($slug, array_keys($moduleDirs))){
								$url	= admin_url("admin.php?page=sim_$slug");
								$url2	= admin_url("admin.php?page={$_GET['page']}&remove=$slug");
								echo "<td><a href='$url'>Activate</a></td><td><a href='$url2' class='button sim small'>Delete</a></td>";
							}else{
								// Available for download
								$url	= admin_url("admin.php?page={$_GET['page']}&download=$slug");
								echo "<td><a href='$url' class='button sim small'>Download</a></td>";
							}
						echo "</tr>";
					}
					?>
				</table>
				<?php
			}
			?>

		<br>
		<strong>Current uninstalled but active modules</strong><br>
			<?php
			if(empty($missing)){
				echo "No missing modules";
			}else{
				?>
				<table class="table">
					<?php
					foreach($missing as $slug){
						$url	= admin_url("admin.php?page={$_GET['page']}&download=$slug");
						$url2	= admin_url("admin.php?page={$_GET['page']}&remove=$slug");
						?>
						<tr>
							<td><?php echo esc_attr(ucfirst($slug));?></td>
							<td><a href='<?php echo esc_url($url); ?>' class='button sim small'>Download</a></td>
							<td><a href='<?php echo esc_url($url2); ?>' class='button sim small'>Delete</a></td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php
			}
			?>
	</div>
	<?php

	$tableHtml	= ob_get_clean();
	if($updatesAvailable){
		?>
		<a href='<?php echo esc_url(SIM\getCurrentUrl());?>&update=all' class='button sim small'>Update all</a>
		<?php
	}
	echo $tableHtml;
}