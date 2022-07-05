<?php
namespace SIM\FORMS;
use SIM;

ob_start();

if(!class_exists('DisplayFormResults')){
	require_once(__DIR__.'/class_DisplayFormResults.php');
}

class FormResultSettings extends DisplayFormResults{
	function __construct(){
		// call parent constructor
		parent::__construct();
	}
	
	/**
	 * Updates column settings with missing columns
	 */
	function enrichColumnSettings(){
		if(!$this->enriched){
			$this->enriched	= true;
			
			//If we should split an entry, define a regex patterns
			if(!empty($this->formSettings['split'])){
				//find the keyword followed by one or more numbers between [] followed by a  keyword between []
				$pattern			= '/'.$this->formSettings['split']."\[[0-9]+\]\[([^\]]+)\]/i";
				$processed			= [];
			}
			
			//loop over all elements to build a new array
			$elementIds	= [];
			
			foreach ($this->formElements as $element){
				$elementIds[]	= $element->id;
				//check if the element is in the array, if not add it
				if(!isset($this->columnSettings[$element->id])){
					//do not show non-input elements
					if(in_array($element->type, $this->nonInputs)){
						continue;
					}
					
					//Do not show elements that will be splitted
					//Execute the regex
					if(!empty($this->formSettings['split']) && preg_match($pattern, $element->name, $matches)){
						//We found a keyword, check if we already got the same one
						if(!in_array($matches[1], $processed)){
							//Add to the processed array
							$processed[]	= $matches[1];
							
							//replace the name
							$name		= $matches[1];
							
							//check if it was already added a previous time
							$alreadyInSettings = false;
							foreach($this->columnSettings as $el){
								if($el['name'] == $name){
									$alreadyInSettings = true;
									break;
								}
							}
							if($alreadyInSettings){
								continue;
							}
						}else{
							//do not show this element
							continue;
						}
					}else{
						$name			= $element->name;
					}
				
					$this->columnSettings[$element->id] = [
						'name'				=> $name,
						'nice_name'			=> $name,
						'show'				=> '',
						'edit_right_roles'	=> [],
						'view_right_roles'	=> []
					];
				}else{
					if(!isset($this->columnSettings[$element->id]['edit_right_roles'])){
						$this->columnSettings[$element->id]['edit_right_roles']	= [];
					}
					if(!isset($this->columnSettings[$element->id]['view_right_roles'])){
						$this->columnSettings[$element->id]['view_right_roles']	= [];
					}
				}
			}
			
			//check for removed elements
			foreach(array_diff(array_keys($this->columnSettings), $elementIds) as $condition){
				//only unset elements
				if(is_numeric($condition) && $condition > -1){
					unset($this->columnSettings[$condition]);
				}
			}

			//Add a row for each table action as well
			$actions	= [];
			foreach($this->formSettings['actions'] as $action){
				$actions[$action]	= '';
			}
			$actions = apply_filters('sim_form_actions',$actions);
			foreach($actions as $action=>$html){
				if(!is_array($this->columnSettings[$action])){
					$this->columnSettings[$action] = [
						'name'				=> $action,
						'nice_name'			=> $action,
						'show'				=> '',
						'edit_right_roles'	=> [],
						'view_right_roles'	=> []
					];
				}
			}
			
			//also add the id
			if(!is_array($this->columnSettings[-1])){
				$this->columnSettings[-1] = [
					'name'				=> 'id',
					'nice_name'			=> 'ID',
					'show'				=> '',
					'edit_right_roles'	=> [],
					'view_right_roles'	=> []
				];
			}
			
			//put hidden columns on the end
			foreach($this->columnSettings as $key=>$setting){
				if($setting['show'] == 'hide'){
					//remove the element
					unset($this->columnSettings[$key]);
					//add it again, at the end of the array
					$this->columnSettings[$key] = $setting;
				}
			}
		}
	}

	/**
	 * Print the modal to change table settings to the screen
	 */
	function addShortcodeSettingsModal(){
		global $wp_roles;
		
		//Get all available roles
		$userRoles 					= $wp_roles->role_names;
		
		$viewRoles					= $userRoles;
		$viewRoles['everyone']		= 'Everyone';
		$viewRoles['own']	 		= 'Own entries';
		
		$editRoles					= $userRoles;
		$editRoles['own']	 		= 'Own entries';
		
		//Sort the roles
		asort($viewRoles);
		asort($editRoles);
		
		//Table rights active
		if(empty($this->tableSettings)){
			$active1	= '';
			$active2	= 'active';
			$class1		= "hidden";
			$class2		= '';
		//Column settings active
		}else{
			$active1	= 'active';
			$active2	= '';
			$class1		= "";
			$class2		= "hidden";
		}
		
		$this->enrichColumnSettings();
		?>
		<div class="modal form_shortcode_settings hidden">
			<!-- Modal content -->
			<div class="modal-content" style='max-width:90%;'>
				<span id="modal_close" class="close">&times;</span>
				
				<button id="column_settings" class="button tablink <?php echo $active1;?>" data-target="column_settings_<?php echo $this->shortcodeData->id;?>">Column settings</button>
				<button id="table_settings" class="button tablink <?php echo $active2;?>" data-target="table_rights_<?php echo $this->shortcodeData->id;?>">Table settings</button>
				
				<div class="tabcontent <?php echo $class1;?>" id="column_settings_<?php echo $this->shortcodeData->id;?>">
					<form class="sortable_column_settings_rows">
						<input type='hidden' class='shortcode_settings' name='shortcode_id'	value='<?php echo $this->shortcodeData->id;?>'>
						
						<div class="column_setting_wrapper">
							<label class="columnheading formfieldbutton">Sort</label>
							<label class="columnheading column_settings">Field name</label>
							<label class="columnheading column_settings">Display name</label>
							<label style="width: 30px;"></label>
							<label class="columnheading column_settings">Display permissions</label>
							<label class="columnheading column_settings">Edit permissions</label>
						</div>
						<?php
						foreach ($this->columnSettings as $elementIndex=>$columnSetting){
							$nice_name	= $columnSetting['nice_name'];
							
							if($columnSetting['show'] == 'hide'){
								$visibility	= 'invisible';
							}else{
								$visibility	= 'visible';
							}
							$icon			= "<img class='visibilityicon $visibility' src='".PICTURESURL."/$visibility.png'>";
							
							?>
						<div class="column_setting_wrapper" data-id="<?php echo $elementIndex;?>">
							<input type="hidden" class="visibilitytype" name="column_settings[<?php echo $elementIndex;?>][show]" 		value="<?php echo $columnSetting['show'];?>">
							<input type="hidden" name="column_settings[<?php echo $elementIndex;?>][name]"	value="<?php echo $columnSetting['name'];?>">
							<span class="movecontrol formfieldbutton" aria-hidden="true">:::</span>
							<span class="column_settings"><?php echo $columnSetting['name'];?></span>
							<input type="text" class="column_settings" name="column_settings[<?php echo $elementIndex;?>][nice_name]" value="<?php echo $nice_name;?>">
							<span class="visibilityicon"><?php echo $icon;?></span>
							<?php
							//only add view permission for numeric elements others are buttons
							if(is_numeric($elementIndex)){
							?>
							<select class='column_settings' name='column_settings[<?php echo $elementIndex;?>][view_right_roles][]' multiple='multiple'>
							<?php
							foreach($viewRoles as $key=>$roleName){
								if(in_array($key,(array)$columnSetting['view_right_roles'])){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$key' $selected>$roleName</option>";
							}
							?>
							</select>
							<?php
							}else{
								?>
								<div class='column_settings'></div>
								<?php
							}
							?>
							
							<select class='column_settings' name='column_settings[<?php echo $elementIndex;?>][edit_right_roles][]' multiple='multiple'>
							<?php
							foreach($editRoles as $key=>$roleName){
								if(in_array($key,(array)$columnSetting['edit_right_roles'])){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$key' $selected>$roleName</option>";
							}
							?>
							</select>
						</div>
						<?php
						}
						?>
						<?php
						echo SIM\addSaveButton('submit_column_setting','Save table column settings');
						?>
					</form>
				</div>
				
				<div class="tabcontent <?php echo $class2;?>" id="table_rights_<?php echo $this->shortcodeData->id;?>">
					<form>
						<input type='hidden' class='shortcode_settings' name='shortcode_id'	value='<?php echo $this->shortcodeData->id;?>'>
						<input type='hidden' class='shortcode_settings' name='formid'		value='<?php echo $this->formData->id;?>'>
						
						<div class="table_rights_wrapper">
							<label>Select the default column the table is sorted on</label>
							<select name="table_settings[default_sort]">
								<?php
								if($this->tableSettings['default_sort'] == ''){
									?><option value='' selected>---</option><?php
								}else{
									?><option value=''>---</option><?php
								}
								
								foreach($this->columnSettings as $key=>$element){
									$name = $element['nice_name'];
									
									//Check which option is the selected one
									if($this->tableSettings['default_sort'] != '' && $this->tableSettings['default_sort'] == $key){
										$selected = 'selected';
									}else{
										$selected = '';
									}
									echo "<option value='$key' $selected>$name</option>";
								}
								?>
							</select>
						</div>

						<div class="table_filters_wrapper">
							<label>Select the fields the table can be filtered on</label>
							<div class='clone_divs_wrapper'>
								<?php
								$filters	= $this->tableSettings['filter'];

								if(!is_array($this->tableSettings['filter'])){
									$this->tableSettings['filter']	= [];
									$filters	= [''];
								}

								foreach($filters as $index=>$filter){
									echo "<div class='clone_div' data-divid='$index'>";									
										echo "<select name='table_settings[filter][$index][element]' class='inline'>";
											foreach($this->columnSettings as $key=>$element){
												$name = $element['nice_name'];
												
												//Check which option is the selected one
												if($this->tableSettings['filter'][$index]['element'] == $key){
													$selected = 'selected';
												}else{
													$selected = '';
												}
												echo "<option value='$key' $selected>$name</option>";
											}
										echo "</select>";

										echo "   filter type";
										echo "<select name='table_settings[filter][$index][type]' class='inline'>";
											foreach(['>=', '<', '==', 'like'] as $type){
												if($this->tableSettings['filter'][$index]['type'] == $type){
													$selected = 'selected';
												}else{
													$selected = '';
												}
												echo "<option value='$type' $selected>$type</option>";
											}
										echo "</select>";
										echo "   Filter name  ";
										echo "<input name='table_settings[filter][$index][name]' value='{$this->tableSettings['filter'][$index]['name']}'>";
										echo "  <button type='button' class='add button'>+</button>";
										echo "<button type='button' class='remove button'>-</button>";
									echo "</div>";
								}
								?>
							</div>
						</div>
						
						<div class="table_rights_wrapper">
							<label>
								Select a column which determines if a row should be shown.<br>
								The row will be hidden if a cell in this column has no value and the viewer has no right to edit.
							</label>
							<select name="table_settings[hiderow]">
							<?php
							if($this->tableSettings['hiderow'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							foreach($this->columnSettings as $key=>$element){
								$name = $element['nice_name'];
								
								//Check which option is the selected one
								if($this->tableSettings['hiderow'] == $element['name']){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='{$element['name']}' $selected>$name</option>";
							}
							?>
							</select>
						</div>
						
						<div class="table_rights_wrapper">
							<label>Select a field with multiple answers where you want to create seperate rows for</label>
							<select name="form_settings[split]">
							<?php
							if($this->formSettings['split'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							$foundElements = [];
							foreach($this->formElements as $key=>$element){
								$pattern = "/([^\[]+)\[[0-9]+\]/i";
								
								if(preg_match($pattern, $element->name, $matches)){
									//Only add if not found before
									if(!in_array($matches[1], $foundElements)){
										$foundElements[]	= $matches[1];
										$value 				= strtolower(str_replace('_', ' ', $matches[1]));
										$name				= ucfirst($value);
										
										//Check which option is the selected one
										if($this->formSettings['split'] == $value){
											$selected = 'selected';
										}else{
											$selected = '';
										}
										echo "<option value='$value' $selected>$name</option>";
									}
								}
							}
							?>
							</select>
						</div>

						<div class="table_rights_wrapper">
							<label class="label">
								Select which results to display
							</label>
							<br>
							<select name="table_settings[result_type]">
								<option value="personal" <?php if($this->tableSettings['result_type'] == 'personal') echo 'selected';?>>Only personal</option>
								<option value="all" <?php if($this->tableSettings['result_type'] == 'all') echo 'selected';?>>All the viewer has permission for</option>
							</select>
						</div>
						
						<div class="table_rights_wrapper">
							<label class="label">
								Select if you want to view archived results by default<br>
								<?php
								if($this->tableSettings['archived'] == 'true'){
									$checked1	= 'checked';
									$checked2	= '';
								}else{
									$checked1	= '';
									$checked2	= 'checked';
								}
								?>
								<label>
									<input type="radio" name="table_settings[archived]" value="true" <?php echo $checked1;?>>
									Yes
								</label>
								<label>
									<input type="radio" name="table_settings[archived]" value="false" <?php echo $checked2;?>>
									No
								</label>
							</label>
						</div>
						
						<!-- We can define auto archive field both on table and on form settings-->
						<div class="table_rights_wrapper">
							<label class="label">
								Select if you want to auto archive results<br>
								<?php
								if($this->formSettings['autoarchive'] == 'true'){
									$checked1	= 'checked';
									$checked2	= '';
								}else{
									$checked1	= '';
									$checked2	= 'checked';
								}
								?>
								<label>
									<input type="radio" name="form_settings[autoarchive]" value="true" <?php echo $checked1;?>>
									Yes
								</label>
								<label>
									<input type="radio" name="form_settings[autoarchive]" value="false" <?php echo $checked2;?>>
									No
								</label>
							</label>
							<br>
							<br>
							<div class='autoarchivelogic <?php if($checked1 == ''){echo 'hidden';}?>'>
								Auto archive a (sub) entry when field
								<select name="form_settings[autoarchivefield]" style="margin-right:10px;">
								<?php
								if($this->formSettings['autoarchivefield'] == ''){
									?><option value='' selected>---</option><?php
								}else{
									?><option value=''>---</option><?php
								}
								
								foreach($this->columnSettings as $key=>$element){
									$name = $element['nice_name'];
									
									//Check which option is the selected one
									if($this->formSettings['autoarchivefield'] != '' && $this->formSettings['autoarchivefield'] == $key){
										$selected = 'selected';
									}else{
										$selected = '';
									}
									echo "<option value='$key' $selected>$name</option>";
								}
								?>
								</select>
								<label style="margin:0 10px;">equals</label>
								<input type='text' name="form_settings[autoarchivevalue]" value="<?php echo $this->formSettings['autoarchivevalue'];?>">
								
								<div class="infobox" name="info">
									<div>
										<p class="info_icon">
											<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo PICTURESURL."/info.png";?>">
										</p>
									</div>
									<span class="info_text">
										You can use placeholders like '%today%+3days' for a value
									</span>
								</div>
							</div>
						</div>
						
						<div class="table_rights_wrapper">
							<label class="label">Select roles with permission to VIEW the table, finetune it per column on the 'column settings' tab</label>
							<div class="role_info">
							<?php
							foreach($viewRoles as $key=>$roleName){
								if(in_array($key,array_keys((array)$this->tableSettings['view_right_roles']))){
									$checked = 'checked';
								}else{
									$checked = '';
								}
								
								echo "<label class='option-label'>";
									echo "<input type='checkbox' class='formbuilder formfieldsetting' name='table_settings[view_right_roles][$key]' value='$roleName' $checked>";
									echo "$roleName";
								echo "</label><br>";
							}
							?>
							</div>
						</div>
						
						<div class="table_rights_wrapper">
							<label class="label">Select roles with permission to edit ALL form submission data</label>
							<div class="role_info">
							<?php
							foreach($editRoles as $key=>$roleName){
								if(in_array($key,array_keys((array)$this->tableSettings['edit_right_roles']))){
									$checked = 'checked';
								}else{
									$checked = '';
								}
								echo "<label class='option-label'>";
									echo "<input type='checkbox' class='formbuilder formfieldsetting' name='table_settings[edit_right_roles][$key]' value='$roleName' $checked>";
									echo " $roleName";
								echo "</label><br>";
							}
							?>
							</div>
						</div>
					<?php
					echo SIM\addSaveButton('submit_table_setting','Save table access settings');
					?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Processed the table settings
	 */
	function loadTableSettings(){
		//load shortcode settings
		$this->loadShortcodeData();
		
		//load form settings
		$this->getForm();
		
		$this->formSettings		= $this->formData->settings;
		
		if($this->tableSettings['archived'] == 'true' || $_GET['archived']){
			$this->showArchived = true;
		}else{
			$this->showArchived = false;
		}
		
		//check if we have rights on this form
		if(!$this->formEditPermissions){
			if(
				array_intersect((array)$this->userRoles, array_keys((array)$this->formSettings['full_right_roles']))	||
				array_intersect((array)$this->userRoles, array_keys((array)$this->tableSettings['full_right_roles']))	||
				$this->editRights
			){
				$this->formEditPermissions = true;
			}else{
				$this->formEditPermissions = false;
			}
		}
		
		//check if we have rights on this table
		if(!$this->tableEditPermissions){
			if(array_intersect($this->userRoles, array_keys((array)$this->tableSettings['edit_right_roles']))){
				$this->tableEditPermissions = true;
			}else{
				$this->tableEditPermissions = false;
			}
		}
		
		if(
			$_GET['onlyown'] == 'true'							|| 
			$this->tableSettings['result_type'] == 'personal'	||
			!$this->tableEditPermissions						&&
			!array_intersect($this->userRoles, array_keys((array)$this->tableSettings['view_right_roles']))
		){
			$this->tableViewPermissions = false;
			$this->getSubmissionData($this->user->ID);
		}else{
			$this->tableViewPermissions = true;
			$this->getSubmissionData();
		}
	}
	
	/**
	 * Renders the table buttons html
	 * 
	 * @return string	The html
	 */
	function renderTableButtons(){	
		$html	= "<div class='table-buttons-wrapper'>";
			//Show form properties button if we have form edit permissions
			if($this->formEditPermissions){
				$html	.= "<button class='button small edit_formshortcode_settings'>Edit settings</button>";
				$this->addShortcodeSettingsModal();
			}

			// Archived button
			if($_GET['archived']){
				$html	.= "<a href='.' class='button sim'>Hide archived entries</a>";
			}elseif(!$this->showArchived){
				$html	.= "<a href='?archived=true' class='button sim'>Show archived entries</a>";
			}

			// Only own button
			if($_GET['onlyown'] || $this->tableSettings['result_type'] == 'personal'){
				$html	.= "<a href='.' class='button sim'>Show all entries</a>";
			}else{
				$html	.= "<a href='?onlyown=true' class='button sim'>Show only my own entries</a>";
			}

			$html	.= "<button type='button' class='button small show fullscreenbutton'>Show full screen</button>";
			
			$hidden	= '';
			if(empty($this->hiddenColumns)){
				$hidden	= 'hidden';
			}
			$html	.= "<button type='button' class='button small reset-col-vis $hidden'>Reset visibility</button>";
		$html	.= "</div>";

		
		$html	.= "<form method='post' class='filteroptions'>";
			if(!empty($this->tableSettings['filter'])){
				// Load all the data
				if(!$this->tableViewPermissions){
					$userId	= $this->user->ID;
				}else{
					$userId	= '';
				}
				$this->getSubmissionData($userId, null, true);

				$html	.= "<div class='filter-wrapper'>";

					foreach($this->tableSettings['filter'] as $filter){
						$filterElement	= $this->getElementById($filter['element']);
						$filterValue	= $_POST[$filter['name']];

						$name			= str_replace(']', '', end(explode('[', $filterElement->name)));

						// Filter the current submission data
						if(!empty($filterValue)){
							foreach($this->submissionData as $key=>$entry){
								if(!$this->compareFilterValue($entry->formresults[$name], $filter['type'], $filterValue)){
									unset($this->submissionData[$key]);
								}
							}

							// match the page params again
							$this->total			= count($this->submissionData);
							$this->submissionData	= array_chunk($this->submissionData, $this->pageSize)[$this->currentPage];
						}

						$html	.= "<span class='filteroption'>";
							$html	.= ucfirst($filter['name'])." <input type='{$filterElement->type}' name='{$filter['name']}' value='$filterValue'>";
						$html	.= "</span>";
					}
					$html	.= "<button class='button'>Filter</button>";
				$html	.= "</div>";
			}

			if($this->total > $this->pageSize){
				$pageCount	=  ceil($this->total / $this->pageSize);
				$html	.= "<div class='form-result-navigation'>";
					$html	.= "<input type='hidden' name='pagenumber' value='$this->currentPage'>";
					// include a back button if we are not on the first page
					if($this->currentPage > 0){
						$html	.= "<button class='button small prev' name='prev' value='prev'>← Previous</button>";
					}
					//show page numbers
					$html	.= "<span class='page-number-wrapper'>";
						for ($x = 0; $x < $pageCount; $x++) {
							$pageNr	= $x+1;

							if($this->currentPage == $x){
								$html	.= "<strong>$pageNr </strong>";
							}else{
								$html	.= "$pageNr ";
							}
						}
					$html	.= "</span>";
					// Include a next button if we are not on the last page
					if($this->total > $this->pageSize && $this->currentPage != $pageCount-1){
						$html	.= "<button class='button small next' name='next' value='next'>Next →</button>";
					}
				$html	.= "</div>";
			}
		$html	.= "</form>";	
		return $html;
	}

	/**
	 * New form results table
	 * 
	 * @param	int		$formId		the id of the form
	 * 
	 * @return	int					The id of the new formtable
	 */
	function insertInDb($formId){
		global $wpdb;

		//add new row in db
		$wpdb->insert(
			$this->shortcodeTable, 
			array(
				'table_settings'	=> '',
				'form_id'			=> $formId,
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * check for any formresults shortcode and add an id if needed
	 * 
	 * @param	array	$data	The post data
	 * 
	 * @return	array			The filtered post data	
	 */
	function checkForFormShortcode($data) {
		global $wpdb;
		
		//find any formresults shortcode
		$pattern = "/\[formresults([^\]]*formname=([a-zA-Z]*)[^\]]*)\]/s";
		
		//if there are matches
		if(preg_match_all($pattern, $data['post_content'], $matches)) {			
			//loop over all the matches
			foreach($matches[1] as $key=>$shortcodeAtts){
				//this shortcode has no id attribute
				if (strpos($shortcodeAtts, ' id=') === false) {
					$shortcode		= $matches[0][$key];
					
					$this->formName = $matches[2][$key];

					$this->getForm();
					
					$this->insertInDb($this->formData->id);
					
					$shortcodeId	= $wpdb->insert_id;
					$newShortcode	= str_replace('formresults',"formresults id=$shortcodeId", $shortcode);
					
					//replace the old shortcode with the new one
					$pos = strpos($data['post_content'], $shortcode);
					if ($pos !== false) {
						$data['post_content'] = substr_replace($data['post_content'], $newShortcode, $pos, strlen($shortcode));
					}
				}
			}
		}
		
		return $data;
	}
}

add_filter( 'wp_insert_post_data', function($data , $postarr){
	$formtable = new DisplayFormResults();
	return $formtable->checkForFormShortcode($data , $postarr);
}, 10, 2 );