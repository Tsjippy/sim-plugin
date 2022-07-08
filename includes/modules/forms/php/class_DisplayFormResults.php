<?php
namespace SIM\FORMS;
use SIM;

ob_start();

if(!class_exists('SimForms')){
	require_once(__DIR__.'/class_SimForms.php');
}

if(!trait_exists(__NAMESPACE__.'\ExportFormResults')){
	require_once(__DIR__.'/trait_ExportFormResults.php');
}

class DisplayFormResults extends SimForms{
	use ExportFormResults;

	function __construct(){
		global $wpdb;
		
		$this->shortcodeTable			= $wpdb->prefix . 'sim_form_shortcodes';
		$this->enriched					= false;
		
		// call parent constructor
		parent::__construct();
		
		if(function_exists('is_user_logged_in') && is_user_logged_in()){
			$this->userRoles[]	= 'everyone';//used to indicate view rights on permissions
			
			$this->excelContent= [];
		}
	}

	/**
	 * Get formresults of the current form
	 * 
	 * @param	int		$userId			Optional the user id to get the results of. Default null
	 * @param	int		$submissionId	Optional a specific id. Default null
	 * 
	 * 
	 */
	function getSubmissionData($userId=null, $submissionId=null, $all=false){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->submissionTableName} WHERE form_id={$this->formData->id}";
		if(is_numeric($submissionId)){
			$query .= " and id='$submissionId'";
		}elseif(is_numeric($userId)){
			$query .= " and userid='$userId'";
		}
		
		if(!$this->showArchived && $submissionId == null){
			$query .= " and archived=0";
		}

		// Limit the amount to 100
		if(isset($_POST['pagenumber']) && is_numeric($_POST['pagenumber'])){
			$this->currentPage	= $_POST['pagenumber'];

			if(isset($_POST['prev'])){
				$this->currentPage--;
			}
			if(isset($_POST['next'])){
				$this->currentPage++;
			}
			$start	= $this->currentPage * $this->pageSize;
		}else{
			$start				= 0;
			$this->currentPage	= 0;
		}

		$query	= apply_filters('sim_formdata_retrieval_query', $query, $userId, $this->formName);

		// Get the total
		$result	= $wpdb->get_results(str_replace('*', 'count(*) as total', $query));
		if(empty($result)){
			$this->total	= 0;
		}else{
			$this->total	= $result[0]->total;
		}

		if(!$all){
			$query	.= " LIMIT $start, $this->pageSize";
		}

		// Get results
		$result	= $wpdb->get_results($query);
		$result	= apply_filters('sim_retrieved_formdata', $result, $userId, $this->formName);

		$this->submissionData		= $result;
		
		if(is_numeric($submissionId)){
			$this->submissionData	= $this->submissionData[0];
			$this->formResults 		= maybe_unserialize($this->submissionData->formresults);
		}else{
			// unserialize
			foreach($this->submissionData as &$data){
				$data->formresults	= unserialize($data->formresults);
			}

			$this->processSplittedData();
		}
		
		if($wpdb->last_error !== ''){
			SIM\printArray($wpdb->print_error());
		}
	}

	/**
	 * This function creates seperated entries from entries with an splitted value
	 */
	function processSplittedData(){
		if(empty($this->formData->settings['split'])){
			return;
		}

		$fieldMainName	= $this->formData->settings['split'];
		
		//loop over all submissions
		foreach($this->submissionData as $key=>$entry){
			// loop over all entries of the split key
			foreach($entry->formresults[$fieldMainName] as $subKey=>$array){
				// Should always be an array
				if(!is_array($array)){
					continue;
				}

				// Check if it has data
				$hasData	= false;
				foreach($array as $value){
					if(!empty($value)){
						$hasData = true;
						break;
					}
				}

				if(!$hasData){
					continue;
				}

				// If it has data add as a seperate item to the submission data
				$newSubmission	= clone $entry;
				// Mark this submission as archived if needed
				if(isset($array['archived'])){
					if($this->showArchived){
						$newSubmission->archived	= true;
						unset($array['archived']);
					}else{
						continue;
					}
				}

				// Add the array to the formresults array
				$newSubmission->formresults = array_merge($entry->formresults, $array);

				// remove the index value from the copy
				unset($newSubmission->formresults[$fieldMainName]);

				// Add the subkey
				$newSubmission->sub_id	= $subKey;

				// Copy the entry
				$this->submissionData[]	= $newSubmission;
			}

			// remove the original entry
			unset($this->submissionData[$key]);
		}
	}

	/**
	 * Creates the db table to hold the short codes and their settings
	 */
	function createDbShortcodeTable(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		}
		
		//create table for this form
		global $wpdb;
		
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->shortcodeTable} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			table_settings text NOT NULL,
			column_settings text NOT NULL,
			form_id int NOT NULL,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->shortcodeTable, $sql );
	}
	
	/**
	 * Transforms a given string to hyperlinks or other formats
	 * 
	 * @param 	string	$string		the string to convert
	 * @param	string	$fieldname	The name of the value the string value belongs to
	 * 
	 * @return	string				The transformed string
	 */
	function transformInputData($string, $fieldName){
		if(empty($string)){
			return $string;
		}
		
		//convert arrays to strings
		if(is_array($string)){
			$output = '';

			foreach($string as $sub){
				if(!empty($output)){
					$output .= "\n";
				}
				$output .= $this->transformInputData($sub, $fieldName);
			}
			return $output;
		}else{
			$output		= $string;	
			//open mail programm on click on email
			if (strpos($string, '@') !== false) {
				$output 	= "<a href='mailto:$string'>Send email</a>";
			//Convert link to clickable link if not already
			}elseif(strpos($string, 'https://') !== false && strpos($string, 'href') === false) {
				$output 	= "<a href='$string'>Link</a>";
			//display dates in a nice way
			}elseif(strtotime($string)){
				$date		= date_parse($string);

				//Only transform if everything is there
				if($date['year'] && $date['month'] && $date['day']){
					$output		= date('d-M-Y',strtotime($string));
				}
			//show file uploads as links
			}elseif(strpos($string,'uploads/form_uploads') !== false){
				$url		= SITEURL."/$string";
				$filename	= end(explode('/',$string));
				$output		= "<a href='$url'>$filename</a>";
			// Convert phonenumber to signal link
			}elseif($string[0] == '+'){
				$output	= "<a href='https://signal.me/#p/$string'>$string</a>";
			}
		
			$output = apply_filters('sim_transform_formtable_data', $output, $fieldName);
			return $output;
		}
	}
	
	/**
	 * Adds a new column setting for a new element
	 * 
	 * @param object	$element	the element to check if column settings exists for
	 */
	function addColumnSetting($element){
		//do not show non-input elements
		if(in_array($element->type, $this->nonInputs)){
			return;
		}

		//If we should split an entry, define a regex patterns
		if(!empty($this->formSettings['split'])){
			//find the keyword followed by one or more numbers between [] followed by a  keyword between []
			$pattern	= '/'.$this->formSettings['split']."\[[0-9]+\]\[([^\]]+)\]/i";
			$processed	= [];
		}

		$name	= $element->name;

		//Do not show elements that will be splitted
		//Execute the regex
		if(!empty($this->formSettings['split']) && preg_match($pattern, $element->name, $matches)){
			//We found a keyword, check if we already got the same one
			if(in_array($matches[1], $processed)){
				//do not show this element
				return;
			}

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
				return;
			}
		}

		$this->columnSettings[$element->id] = [
			'name'				=> $name,
			'nice_name'			=> $name,
			'show'				=> '',
			'edit_right_roles'	=> [],
			'view_right_roles'	=> []
		];
	}

	/**
	 * Updates column settings with missing columns
	 */
	function enrichColumnSettings(){
		if($this->enriched){
			return;
		}

		$this->enriched	= true;
		$elementIds		= [];
		
		//loop over all elements to build a new array		
		foreach ($this->formElements as $element){
			$elementIds[]	= $element->id;

			//check if the element is in the array, if not add it
			if(!isset($this->columnSettings[$element->id])){
				$this->addColumnSetting($element);
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

	function getRowContents($fieldValues, $index){
		$rowContents	= '';
		$excelRow		= [];

		if($fieldValues['userid'] == $this->user->ID || $fieldValues['userid'] == $this->user->partnerId){
			$ownEntry	= true;
		}else{
			$ownEntry	= false;
		}

		foreach($this->columnSettings as $id=>$columnSetting){
			$fieldValue	= '';

			//If the column is hidden, do not show this cell
			if($columnSetting['show'] == 'hide' || !is_numeric($id)){
				continue;
			}
			
			//if we lack view permission, do not show this cell
			if(
				(
					!$ownEntry ||
					(																						//not our own entry
						$ownEntry &&																		//or it is our own
						!in_array('own', (array)$columnSetting['view_right_roles'])							//but we are not allowed to see it
					)
				)	&&											
				!$this->tableEditPermissions &&															//no permission to edit the table and
				!empty($columnSetting['view_right_roles']) && 											// there are view right permissions defined
				!array_intersect($this->userRoles, (array)$columnSetting['view_right_roles'])			// and we do not have the view right role
			){
				//later on there will be a row with data in this column
				if($this->ownData && in_array('own',(array)$columnSetting['view_right_roles'])){
					$fieldValue = 'X';
				}else{
					continue;
				}
			}
			
			//if this row has no value in this column remove the row
			if(
				!empty($this->tableSettings['hiderow']) &&												//There is a column defined
				$columnSetting['name'] == $this->tableSettings['hiderow'] && 							//We are currently checking a cell in that column
				$fieldValues[$this->tableSettings['hiderow']] == '' && 									//The cell has no value
				!array_intersect($this->userRoles, (array)$columnSetting['edit_right_roles'])	&&	//And we have no right to edit this specific column
				!$this->tableEditPermissions																//and we have no right to edit all table data
			){
				return '';
			}

			if(
				in_array('own',(array)$columnSetting['edit_right_roles']) &&
				$ownEntry ||
				array_intersect($this->userRoles, (array)$columnSetting['edit_right_roles']) ||
				$this->tableEditPermissions
			){
				$elementEditRights = true;
			}else{
				$elementEditRights = false;
			}
					
			/* 
					Write the content to the cell, convert to something if needed 
			*/
			$fieldName 	= str_replace('[]', '', $columnSetting['name']);
			$class 		= '';

			//add field value if we are allowed to see it
			if($fieldValue != 'X'){
				//Get the field value from the array
				$fieldValue	= $fieldValues[$fieldName];
					
				// Add sub id if this is an sub value
				$subId 		= "";
				$element	= $this->getElementById($id);
				if($index > -1 && strpos($element->name, $this->formSettings['split'].'[') !== false){
					$subId = "data-subid='$index'";
				}

				if($fieldValue == null){
					$fieldValue = '';
				}
				
				//transform if needed
				$orgFieldValue	= $fieldValue;
				$fieldValue 	= $this->transformInputData($fieldValue, $fieldName);
				
				//show original email in excel
				if(strpos($fieldValue,'@') !== false){
					$excelRow[]		= $orgFieldValue;
				}else{
					$excelRow[]		= wp_strip_all_tags($fieldValue);
				}

				//Display an X if there is nothing to show
				if (empty($fieldValue)){
					$fieldValue = "X";
				}
				
				//Limit url cell width, for strings with a visible length of more then 30 characters
				if(strlen(strip_tags($fieldValue))>30 && strpos($fieldValue, 'https://') === false){
					$class .= ' limit-length';
				}			
			}

			//Add classes to the cell
			if($fieldName == "displayname"){
				$class .= ' sticky';
			}

			if(!empty($this->hiddenColumns[$columnSetting['name']])){
				$class	.= ' hidden';
			}
			
			//if the user has one of the roles diffined for this element
			if($elementEditRights && $fieldName != 'id'){
				$class	.= ' edit_forms_table';
				$class	= trim($class);
				$class	= " class='$class' data-id='$fieldName'";
			}elseif(!empty($class)){
				$class	= trim($class);
				$class = " class='$class'";
			}
			
			//Convert underscores to spaces, but not in urls
			if(strpos($fieldValue, 'href=') === false){
				$fieldValue	= str_replace('_',' ',$fieldValue);
			}

			$oldValue		= json_encode($orgFieldValue);
			$rowContents .= "<td $class data-oldvalue='$oldValue' $subId>$fieldValue</td>";
		}

		$this->excelContent[] = $excelRow;

		return $rowContents;
	}
	
	/**
	 * Writes a row of the table to the screen
	 * 
	 * @param	array	$fieldValues	Array containing all the values of a form submission
	 * @param	int		$index			The index of the row. Default -1 for none
	 * @param	bool	$isArchived		Whether the current submission is archived. Default false.
	 */
	function writeTableRow($fieldValues, $index=-1, $isArchived=false){
		//Loop over the fields in order of the defined columns	
		
		$this->noRecords = false;
		
		//If this row should be written and it is the first cell then write 
		if($index > -1){
			$subId = "data-subid='$index'";
		}else{
			$subId = "";
		}
		echo "<tr class='table-row' data-id='{$fieldValues['id']}' $subId>";
		
		echo $this->getRowContents($fieldValues, $index);

		//if there are actions
		if($this->formSettings['actions'] != ""){
			//loop over all the actions
			$buttonsHtml	= [];
			$buttons		= '';
			foreach($this->formSettings['actions'] as $action){
				if($action == 'archive' && $this->showArchived == 'true' && $isArchived){
					$action = 'unarchive';
				}
				$buttonsHtml[$action]	= "<button class='$action button forms_table_action' name='{$action}_action' value='$action'/>".ucfirst($action)."</button>";
			}
			$buttonsHtml = apply_filters('sim_form_actions', $buttonsHtml, $fieldValues, $index);
			
			//we have te html now, check for which one we have permission
			foreach($buttonsHtml as $action=>$button){
				if(
					$this->tableEditPermissions || 																			//if we are allowed to do all actions
					$fieldValues['userid'] == $this->user->ID || 															//or this is our own entry
					array_intersect($this->userRoles, (array)$this->columnSettings[$action]['edit_right_roles'])			//or we have permission for this specific button
				){
					$buttons .= $button;
				}
			}
			if(!empty($buttons)){
				echo "<td>$buttons</td>";
			}
		}
		echo '</tr>';
	}
	
	/**
	 * Get shortcode settings from db
	 */
	function loadShortcodeData(){
		global $wpdb;
		
		$query						= "SELECT * FROM {$this->shortcodeTable} WHERE id= '{$this->shortcodeId}'";
		
		$this->shortcodeData 		= $wpdb->get_results($query)[0];
		
		$this->tableSettings		= unserialize($this->shortcodeData->table_settings);
		$this->columnSettings		= unserialize($this->shortcodeData->column_settings);
	}

	function columnSettingsForm($class, $viewRoles, $editRoles){
		?>
		<div class="tabcontent <?php echo $class;?>" id="column_settings_<?php echo $this->shortcodeData->id;?>">
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
					$niceName	= $columnSetting['nice_name'];
					
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
						<input type="text" class="column_settings" name="column_settings[<?php echo $elementIndex;?>][nice_name]" value="<?php echo $niceName;?>">
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
		<?php
	}

	function tableRightsForm($class, $viewRoles, $editRoles){
		?>
		<div class="tabcontent <?php echo $class;?>" id="table_rights_<?php echo $this->shortcodeData->id;?>">
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
						$pattern = "/([^\[]+)\[\d+\]/i";
						
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
						<option value="personal" <?php if($this->tableSettings['result_type'] == 'personal'){echo 'selected';}?>>Only personal</option>
						<option value="all" <?php if($this->tableSettings['result_type'] == 'all'){echo 'selected';}?>>All the viewer has permission for</option>
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
		<?php
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
				
				<?php
				$this->columnSettingsForm($class1, $viewRoles, $editRoles);

				$this->tableRightsForm($class2, $viewRoles, $editRoles);
				?>
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
	 * Compares 2 values according to a given comparison string
	 */
	function compareFilterValue ($var1, $op, $var2) {
		if(empty($var1) || empty($var2)){
			return true;
		}
		switch ($op) {
			case "=":  		return $var1 == $var2;
			case "!=": 		return $var1 != $var2;
			case ">=": 		return $var1 >= $var2;
			case "<=": 		return $var1 <= $var2;
			case ">":  		return $var1 >  $var2;
			case "<":  		return $var1 <  $var2;
			case "like":	return strpos(strtolower($var1), strtolower($var2)) !== false;
			default:       return true;
		}   
	}

	/**
	 * Creates the formresult table html
	 * 
	 * @param	array	$atts	WP Shortcode attributes
	 * 
	 * @return	string			The html
	 */
	function showFormresultsTable(){
		//do not show if not logged in
		if(!is_user_logged_in()){
			return;
		}

		$this->loadTableSettings();

		$buttons	= $this->renderTableButtons();

		$this->noRecords	= true;

		ob_start();
		//process any $_GET acions
		do_action('sim_formtable_GET_actions');
		do_action('sim_formtable_POST_actions');
		
		//Load js
		wp_enqueue_script('sim_forms_table_script');

		//Get personal visibility
		$this->hiddenColumns	= get_user_meta($this->user->ID, 'hidden_columns_'.$this->formData->id, true);	
		
		?>
		<div class='form table-wrapper'>
			<div class='form-table-head'>
				<h2 class="table_title"><?php echo esc_html($this->formSettings['formname']); ?></h2><br>
				<?php
					echo $buttons;
				?>
			</div>
			<?php

			if(empty($this->submissionData)){
				?>
				<p><br><br><br>No records found</p>
				<?php

				return;
			}

			$this->enrichColumnSettings();

			// Check if we should sort the data
			if($this->tableSettings['default_sort']){
				$defaultSortElement	= $this->tableSettings['default_sort'];
				$sortElement		= $this->getElementById($defaultSortElement);
				$sort				= str_replace(']', '', end(explode('[', $sortElement->name)));
				$sortElementType	= $sortElement->type;
				//Sort the array
				usort($this->submissionData, function($a, $b) use ($sort, $sortElementType){
					if($sortElementType == 'date'){
						return strtotime($a->formresults[$sort]) <=> strtotime($b->formresults[$sort]);
					}
					return $a->formresults[$sort] > $b->formresults[$sort];
				});
			}

			/* 
				Write the header row of the table 
			*/
			//first check if the data contains data of our own
			$this->ownData	= false;
			$this->user->partnerId		= SIM\hasPartner($this->user->ID);
			foreach($this->submissionData as $submissionData){
				//Our own entry or one of our partner
				if($submissionData->userid == $this->user->ID || $submissionData->userid == $this->user->partnerId){
					$this->ownData = true;
					break;
				}
			}
			
			?>
			<table class='sim-table form-data-table' data-formid='<?php echo $this->formData->id;?>' data-shortcodeid='<?php echo $this->shortcodeId;?>'>
				<thead>
					<tr>
						<?php
						//add normal fields
						foreach($this->columnSettings as $settingId=>$columnSetting){
							if(
								!is_numeric($settingId)				||
								$columnSetting['show'] == 'hide'	||													//hidden column
								(
									!$this->ownData				|| 																	//The table does not contain data of our own
									(
										$this->ownData			&& 																//or it does contain our own data but
										!in_array('own',(array)$columnSetting['view_right_roles'])							//we are not allowed to see it
									)
								) &&																					
								!$this->tableEditPermissions 				&&														//no permission to edit the table and
								!empty($columnSetting['view_right_roles']) 	&& 										// there are view right permissions defined
								!array_intersect($this->userRoles, (array)$columnSetting['view_right_roles'])		// and we do not have the view right role and
							){ 
								continue;
							}
							
							$niceName			= $columnSetting['nice_name'];
							
							if($this->tableSettings['default_sort']	== $settingId){
								$class	= "defaultsort"; 
							}else{
								$class	= ""; 
							}
		
							if(!empty($this->hiddenColumns[$columnSetting['name']])){
								$class	.= ' hidden';
							}
							$icon			= "<img class='visibilityicon visible' src='".PICTURESURL."/visible.png' width=20 height=20>";
							
							//Add a heading for each column
							echo "<th class='$class' id='{$columnSetting['name']}' data-nicename='$niceName'>$niceName $icon</th>";
							
							$excelRow[]	= $niceName;
						}
						
						//add a Actions heading if needed
						$buttonsHtml = [];
						foreach($this->formSettings['actions'] as $action){
							$buttonsHtml[$action]	= "";
						}

						//we have full permissions on this table
						if($this->tableEditPermissions && !empty($buttonsHtml)){
							$addHeading	= true;
						}else{
							$buttonsHtml = apply_filters('sim_form_actions', $buttonsHtml);
							foreach($buttonsHtml as $action=>$button){
								//we have permission for this specific button
								if(array_intersect($this->userRoles, (array)$this->columnSettings[$action]['edit_right_roles'])){
									$addHeading	= true;
								}else{
									//Loop over all buttons to see if the current user has permission for them
									foreach($this->submissionData as $submissionData){
										foreach($buttonsHtml as $action=>$button){
											//we have permission on this row for this button
											if($submissionData->formresults['userid'] == $this->user->ID){
												$addHeading	= true;
											}
										}
									}
								}
							}
						}

						if($addHeading){
							echo "<th id='actions' data-nicename='Actions'>Actions</th>";
						}
						?>
					</tr>
				</thead>
				
				<tbody class="table-body">
			
				<?php
				//write header to excel
				$this->excelContent[] = $excelRow;
				/* 
						WRITE THE CONTENT ROWS OF THE TABLE 
				*/
				//Loop over all the submissions of this form
				foreach($this->submissionData as $submissionData){
					$fieldValues			= $submissionData->formresults;
					$fieldValues['id']		= $submissionData->id;
					$fieldValues['userid']	= $submissionData->userid;
					$index					= -1;
					if(is_numeric($submissionData->sub_id)){
						$index				= $submissionData->sub_id;
					}
					
					$this->writeTableRow($fieldValues, $index, $submissionData->archived);						
				}
				?>
				</tbody>
			</table>
				
			<p id="table_remark">Click on any cell with <span class="edit">underlined text</span> to edit its contents.<br>Click on any header to sort the column.</p>
			
			<?php
			//Add excel export button if allowed
			if($this->tableEditPermissions){
				?>
				<div>
					<form method="post" class="exportform" id="export_xls">
						<button class="button button-primary" type="submit" name="export_xls">Export data to excel</button>
					</form>
					<?php
					if(SIM\getModuleOption('PDF', 'enable')){
						?>
						<form method="post" class="exportform" id="export_pdf">
							<button class="button button-primary" type="submit" name="export_pdf">Export data to pdf</button>
						</form>
						<?php
					}
					?>
				</div>
				<?php
			}
			
			if($this->noRecords){
				?><p><br><br><br>No records found</p><?php
			}
			
			?>
		</div>
		<?php
		
		//now we have rendered all the content we can export the excel if requested
		if(isset($_POST['export_xls'])){
			$this->exportExcel();
		}

		//now we have rendered all the content we can export the pdf if requested
		if(isset($_POST['export_pdf'])){
			echo $this->exportPdf();
		}
		
		return ob_get_clean();
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
	if(function_exists('wp_get_current_user')){
		$formtable = new DisplayFormResults();
		return $formtable->checkForFormShortcode($data , $postarr);
	}

	return $data;
}, 10, 2 );