<?php
namespace SIM\FORMS;
use SIM;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ob_start();
class FormTable extends Formbuilder{
	function __construct(){
		global $wpdb;
		
		$this->shortcodetable			= $wpdb->prefix . 'sim_form_shortcodes';
		$this->enriched					= false;
		
		// call parent constructor
		parent::__construct();
		
		if(is_user_logged_in()){
			$this->user_roles[]	= 'everyone';//used to indicate view rights on permissions
			
			$this->excel_content= [];
		}
	}

	function create_db_shortcode_table(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		}
		
		//create table for this form
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->shortcodetable} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			table_settings text NOT NULL,
			column_settings text NOT NULL,
			form_id int NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		maybe_create_table($this->shortcodetable, $sql );
	}
	
	function transform_inputdata($string, $field_name){
		if(empty($string)) return $string;
		
		//convert arrays to strings
		if(is_array($string)){
			$output = '';

			foreach($string as $key=>$sub){
				if($output != '') $output .= "\n";
				$output .= $this->transform_inputdata($sub,$field_name);
			}
			return $output;
		}else{
			$output		= $string;	
			//open mail programm on click on email
			if (strpos($string, '@') !== false) {
				$output 	= "<a href='mailto:$string'>Send email</a>";
			//Convert link to clickable link if not already
			}elseif(strpos($string, 'https://') !== false and strpos($string, 'href') === false) {
				$output 	= "<a href='$string'>Link</a>";
			//display dates in a nice way
			}elseif(strtotime($string) != false){
				$date		= date_parse($string);

				//Only transform if everything is there
				if($date['year'] and $date['month'] and $date['day']){
					$output		= date('d-M-Y',strtotime($string));
				}
			//show file uploads as links
			}elseif(strpos($string,'uploads/form_uploads') !== false){
				$url		= get_site_url()."/$string";
				$filename	= end(explode('/',$string));
				$output		= "<a href='$url'>$filename</a>";
			}
		
			$output = apply_filters('sim_transform_formtable_data',$output,$field_name);
			return $output;
		}
	}
	
	function enrich_column_settings(){
		if($this->enriched == false){
			$this->enriched	= true;
			
			//If we should split an entry, define a regex patterns
			if($this->form_settings['split'] != ''){
				//find the keyword followed by one or more numbers between [] followed by a  keyword between []
				$pattern			= '/'.$this->form_settings['split']."\[[0-9]+\]\[([^\]]+)\]/i";
				$processed			= [];
			}
			
			//loop over all elements to build a new array
			$element_ids	= [];
			
			foreach ($this->form_elements as $element){
				$element_ids[]	= $element->id;
				//check if the element is in the array, if not add it
				if(!isset($this->column_settings[$element->id])){
					//do not show non-input elements
					if(in_array($element->type,$this->non_inputs)) continue;
					
					//Do not show elements that will be splitted
					//Execute the regex
					if($this->form_settings['split'] != '' and preg_match($pattern, $element->name,$matches)){
						//We found a keyword, check if we already got the same one
						if(!in_array($matches[1],$processed)){
							//Add to the processed array
							$processed[]	= $matches[1];
							
							//replace the name
							$name		= $this->form_settings['split'].'[%index%]['.$matches[1].']';
							
							//check if it was already added a previous time
							$already_in_settings = false;
							foreach($this->column_settings as $el){
								if($el['name'] == $name){
									$already_in_settings = true;
									break;
								}
							}
							if($already_in_settings) continue;
						}else{
							//do not show this element
							continue;
						}
					}else{
						$name			= $element->name;
					}
				
					$this->column_settings[$element->id] = [
						'name'				=> $name,
						'nice_name'			=> $name,
						'show'				=> '',
						'edit_right_roles'	=> [],
						'view_right_roles'	=> []
					];
				}else{
					if(!isset($this->column_settings[$element->id]['edit_right_roles'])){
						$this->column_settings[$element->id]['edit_right_roles']	= [];
					}
					if(!isset($this->column_settings[$element->id]['view_right_roles'])){
						$this->column_settings[$element->id]['view_right_roles']	= [];
					}
				}
			}
			
			//check for removed elements
			foreach(array_diff(array_keys($this->column_settings),$element_ids) as $condition){
				//only unset elements
				if(is_numeric($condition) and $condition > -1){
					unset($this->column_settings[$condition]);
				}
			}

			//Add a row for each table action as well
			$actions	= [];
			foreach($this->form_settings['actions'] as $action){
				$actions[$action]	= '';
			}
			$actions = apply_filters('form_actions',$actions);
			foreach($actions as $action=>$html){
				if(!is_array($this->column_settings[$action])){
					$this->column_settings[$action] = [
						'name'				=> $action,
						'nice_name'			=> $action,
						'show'				=> '',
						'edit_right_roles'	=> [],
						'view_right_roles'	=> []
					];
				}
			}
			
			//also add the id
			if(!is_array($this->column_settings[-1])){
				$this->column_settings[-1] = [
					'name'				=> 'id',
					'nice_name'			=> 'ID',
					'show'				=> '',
					'edit_right_roles'	=> [],
					'view_right_roles'	=> []
				];
			}
			
			//put hidden columns on the end
			foreach($this->column_settings as $key=>$setting){
				if($setting['show'] == 'hide'){
					//remove the element
					unset($this->column_settings[$key]);
					//add it again, at the end of the array
					$this->column_settings[$key] = $setting;
				}
			}
		}
	}
	
	function write_table_row($field_values, $index=-1, $is_archived=false){
		$field_main_name	= $this->form_settings['split'];
		//Loop over the fields in order of the defined columns
		$rowcontents	= '';

		if($field_values['userid'] == $this->user->ID or $field_values['userid'] == $this->user->partner_id){
			$own_entry	= true;
		}else{
			$own_entry	= false;
		}

		foreach($this->column_settings as $id=>$column_setting){
			$field_value	= '';
			//If the column is hidden, do not show this cell
			if($column_setting['show'] == 'hide' or !is_numeric($id)) continue;
			
			//if we lack view permission, do not show this cell
			if(
				(!$own_entry or(																		//not our own entry
					$own_entry and																		//or it is our own
					!in_array('own',(array)$column_setting['view_right_roles'])							//but we are not allowed to see it
				))	and											
				!$this->table_edit_permissions and														//no permission to edit the table and
				!empty($column_setting['view_right_roles']) and 										// there are view right permissions defined
				array_intersect($this->user_roles, (array)$column_setting['view_right_roles']) == false	// and we do not have the view right role
			){
				//later on there will be a row with data in this column
				if($this->owndata and in_array('own',(array)$column_setting['view_right_roles'])){
					$field_value = 'X';
				}else{
					continue;
				}
			}
			
			//if this row has no value in this column remove the row
			if(
				!empty($this->table_settings['hiderow']) and												//There is a column defined
				$column_setting['name'] == $this->table_settings['hiderow'] and 							//We are currently checking a cell in that column
				$field_values[$this->table_settings['hiderow']] == '' and 									//The cell has no value
				array_intersect($this->user_roles, (array)$column_setting['edit_right_roles']) == false and	//And we have no right to edit this specific column
				!$this->table_edit_permissions																//and we have no right to edit all table data
			){
				return;
			}

			if(
				in_array('own',(array)$column_setting['edit_right_roles']) and
				$own_entry or
				array_intersect($this->user_roles, (array)$column_setting['edit_right_roles']) != false or
				$this->table_edit_permissions
			){
				$element_edit_rights = true;
			}else{
				$element_edit_rights = false;
			}
					
			/* 
					Write the content to the cell, convert to something if needed 
			*/
			$fieldname = str_replace('[]', '', $column_setting['name']);
			
			//add field value if we are allowed to see it
			if($field_value != 'X'){
				//If we are dealing with an indexed field
				$pattern = "/$field_main_name\[%index%\]\[([^\]]*)\]/i";
				if(preg_match($pattern, $fieldname,$matches)){
					$field_sub_name		= $matches[1];
					$field_value		= $field_values[$field_main_name][$index][$field_sub_name];
				
					$fieldname			= str_replace('%index%',$index,$fieldname);
				
					$sub_id = "data-subid='$index'";
				}else{
					//Get the field value from the array
					$field_value	= $field_values[$fieldname];
					
					$sub_id = "";
				}		
				
				//transform if needed
				$org_field_value	= $field_value;
				$field_value = $this->transform_inputdata($field_value,$fieldname);
				
				//show original email in excel
				if(strpos($field_value,'@') !== false){
					$excelrow[]		= $org_field_value;
				}else{
					$excelrow[]		= wp_strip_all_tags($field_value);
				}

				//Display an X if there is nothing to show
				if (empty($field_value))	$field_value = "X";
				
				//Limit url cell width, for strings with a visible length of more then 30 characters
				if(strlen(strip_tags($field_value))>30 and strpos($field_value, 'https://') === false){
					$style = 'style="min-width: 300px;white-space: pre-wrap;word-wrap: break-word;"';
				}else{
					$style = '';
				}				
			}

			//Add classes to the cell
			$class = '';
			if($fieldname == "displayname") $class = 'sticky ';
			
			//if the user has one of the roles diffined for this element
			if($element_edit_rights and $fieldname != 'id'){
				$class	.= 'edit_forms_table';
				$class	= " class='$class' data-id='$fieldname'";
			}elseif($class != ''){
				$class = " class='$class'";
			}
			
			//Convert underscores to spaces, but not in urls
			if(strpos($field_value,'href=') === false){
				$field_value	= str_replace('_',' ',$field_value);
			}
			$rowcontents .= "<td $class $style data-original='$org_field_value' $sub_id>$field_value</td>";
		}
		
		$this->no_records = false;
		
		//If this row should be written and it is the first cell then write 
		if($index > -1){
			$sub_id = "data-subid='$index'";
		}else{
			$sub_id = "";
		}
		echo "<tr class='table-row' data-id='{$field_values['id']}' $sub_id>";
		
		echo $rowcontents;
		
		$this->excel_content[] = $excelrow;

		//if there are actions
		if($this->form_settings['actions'] != ""){
			//loop over all the actions
			$buttons_html	= [];
			$buttons		= '';
			foreach($this->form_settings['actions'] as $action){
				if($action == 'archive' and $this->show_archived == 'true' and $is_archived){
					$action = 'unarchive';
				}
				$buttons_html[$action]	= "<button class='$action button forms_table_action' name='{$action}_action' value='$action'/>".ucfirst($action)."</button>";
			}
			$buttons_html = apply_filters('form_actions',$buttons_html,$field_values,$index);
			
			//we have te html now, check for which one we have permission
			foreach($buttons_html as $action=>$button){
				if(
					$this->table_edit_permissions or 																	//if we are allowed to do all actions
					$field_values['userid'] == $this->user->ID or 														//or this is our own entry
					array_intersect($this->user_roles, (array)$this->column_settings[$action]['edit_right_roles']) != false	//or we have permission for this specific button
				){
					$buttons .= $button;
				}
			}
			if(!empty($buttons))	echo "<td>$buttons</td>";
		}
		echo '</tr>';
	}
	
	function loadshortcodedata(){
		global $wpdb;
		
		$query							= "SELECT * FROM {$this->shortcodetable} WHERE id= '{$this->shortcode_id}'";
		
		$this->shortcodedata 			= $wpdb->get_results($query)[0];
		
		$this->table_settings		= unserialize($this->shortcodedata->table_settings);
		$this->column_settings		= unserialize($this->shortcodedata->column_settings);
	}
	
	function export_excel($filename="",$download=true){
		if($filename == "") $filename = get_the_title($this->form_id).".xlsx";

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		//Write the column headers
		$col = 0;
		$row = 1;
		$rowindex	= 1;

		//loop over the rows
		foreach($this->excel_content as $row){
			//Start column
			$col=1;
			//loop over the cells
			foreach ($row as $cell) {
				if(is_array($cell)){
					SIM\clean_up_nested_array($cell);
					$cell	= implode(',',$cell);
				} 
				/* 
						Write the content to the cell
				*/
				$sheet->setCellValueByColumnAndRow($col, $rowindex, $cell);

				$col++;
			}
			//Consider new row for each entry here
			$rowindex++;
		}
		
		//Create Styles Array
		$styleArrayFirstRow = ['font' => ['bold' => true,]];
		//Retrieve Highest Column (e.g AE)
		$highestColumn = $sheet->getHighestColumn();
		//set first row bold
		$sheet->getStyle('A1:' . $highestColumn . '1' )->applyFromArray($styleArrayFirstRow);
		
		$writer = new Xlsx($spreadsheet);
		//Download excel file here
		if($download == true){
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=$filename");
			for ($i = 0; $i < ob_get_level(); $i++) {
				ob_get_clean();
			}
			ob_start();
			$writer->save('php://output');
			ob_end_flush();
			exit;
			die();
		}else{
			//Store xlsx file on server and return the path
			$file = get_temp_dir().$filename;
			$writer->save($file);
			return $file;
		}
	}

	function export_pdf($filename=""){
		global $PDF_Logo_path;
			
		$pdf = new \PDF_HTML();
		$pdf->SetFont('Arial','B',15);
				
		//Determine the column widths
		$col_count	= count($this->excel_content[0]);
		$col_widths	= [];
		
		$header		= array_map('ucfirst',$this->excel_content[0]);

		$title		= $this->form_settings['formname'].' export';
		if(empty($title)) $title = 'Form export';

		//loop over the data to check all cells for their length
		foreach($this->excel_content as $index=>$row_data){
			//loop over the columns to check how wide they need to be
			for ($x = 0; $x <= $col_count-1; $x++) {
				//Add the length to the array
				if(is_array($row_data[$x])) $row_data[$x] = implode("\n",$row_data[$x]);

				//convert to hyperlink if needed
			/* 				if(strpos($row_data[$x],get_site_url()) !== false){
					$this->excel_content[$index][$x]	= "<a href='{$row_data[$x]}'>Link</a>";
				} */

				$col_widths[$x][] = $pdf->GetStringWidth($row_data[$x]);
			}
		}

		//find the biggest cell per column
		for ($x = 0; $x <= $col_count-1; $x++) {
			$col_widths[$x] = max($col_widths[$x]);
		}

		$small_coll_width	= 10;
		$medium_coll_width	= 30;

		//count the columns smaller than 10
		$small_coll_count = count(array_filter(
			$col_widths,
			function ($value) use($small_coll_width){
				return ($value <= $small_coll_width);
			}
		));

		//page with minus the total width of all small collums
		$remaining_width	= $pdf->GetPageWidth() - $small_coll_count*$small_coll_width;

		//width needed by all non-small columns
		$required_width = array_sum(array_filter(
			$col_widths,
			function ($value) use($small_coll_width){
				return ($value > $small_coll_width);
			}
		));

		//columns with a width smaller than 30
		$inter_colls	= array_filter(
			$col_widths,
			function ($value) use($small_coll_width, $medium_coll_width){
				return ($value > $small_coll_width and $value <= $medium_coll_width);
			}
		);

		//count the columns with a proportional medium width of 30 or smaller but an actual width bigger than 30
		$medium_coll_count = count(array_filter(
			$col_widths,
			function ($value) use($remaining_width,$required_width,$medium_coll_width){
				$prop_width	= $remaining_width*($value/$required_width);
				return ($value > $medium_coll_width and $prop_width <= $medium_coll_width);
			}
		));

		$big_colls = array_filter(
			$col_widths,
			function ($value) use($remaining_width,$required_width,$medium_coll_width){
				$prop_width	= $remaining_width*($value/$required_width);
				return ($prop_width > $medium_coll_width);
			}
		);

		//Determine the page size
		$min_width	= $small_coll_count*$small_coll_width + array_sum($inter_colls) + $medium_coll_count*$medium_coll_width + array_sum($big_colls)/6;

		if($min_width > 180){
			if($min_width < 297){
				//Landscape A4 pdf
				$pdf = new \PDF_HTML('L');
			}elseif($min_width < 420){
				//Landscape pdf A3 size
				$pdf = new \PDF_HTML('L','mm','A3');
			}elseif($min_width < 594){
				//Landscape pdf A2 size
				$pdf = new \PDF_HTML('L','mm',array(594,420));
			}elseif($min_width < 841){
				//Landscape pdf A1 size
				$pdf = new \PDF_HTML('L','mm',array(841,594));
			}else{
				//Landscape pdf A0 size
				$pdf = new \PDF_HTML('L','mm',array(1189,841));
			}
			
			//Set font again
			$pdf->SetFont('Arial','B',15);
		}

		$available_width_for_big_columns	= $pdf->GetPageWidth() -20 - $small_coll_count*$small_coll_width - array_sum($inter_colls) - $medium_coll_count*$medium_coll_width;
		$big_coll_width						= $available_width_for_big_columns / count($big_colls);

		//now loop over all columns and set their maximum lengths
		for ($x = 0; $x <= $col_count-1; $x++) {
			if($col_widths[$x] < 10){
				$col_widths[$x]	= 10;
			//min width for medium wide columns
			}elseif($col_widths[$x] > $medium_coll_width and $remaining_width*($col_widths[$x]/$required_width) <= $medium_coll_width){
				$col_widths[$x]	= min($col_widths[$x],$medium_coll_width);
			}elseif($col_widths[$x] > 30){
				//equal spread for big columns
				$col_widths[$x]	= min($big_coll_width,$col_widths[$x]);
			}
		}
		
		$pdf->frontpage($title);
		
		//Write the table headers
		$pdf->table_headers($header,$col_widths);
		
		// Data
		$fill = false;
		
		unset($this->excel_content[0]);
		foreach($this->excel_content as $row_data){
			$pdf->WriteTableRow($col_widths, $row_data, $fill, $header);
			
			$fill = !$fill;
		}
		
		// Closing line which is the sum off all the column widths
		$pdf->Cell(array_sum($col_widths),0,'','T');

		$pdf->printpdf();

		exit;
	}

	function add_shortcode_settings_modal(){
		global $wp_roles;
		
		//Get all available roles
		$user_roles = $wp_roles->role_names;
		
		$view_roles					= $user_roles;
		$view_roles['everyone']		= 'Everyone';
		$view_roles['own']	 		= 'Own entries';
		
		$edit_roles					= $user_roles;
		$edit_roles['own']	 		= 'Own entries';
		
		//Sort the roles
		asort($view_roles);
		asort($edit_roles);
		
		//Table rights active
		if(empty($this->table_settings)){
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
		
		$this->enrich_column_settings();
		?>
		<div class="modal form_shortcode_settings hidden">
			<!-- Modal content -->
			<div class="modal-content" style='max-width:90%;'>
				<span id="modal_close" class="close">&times;</span>
				
				<button id="column_settings" class="button tablink <?php echo $active1;?>" data-target="column_settings_<?php echo $this->shortcodedata->id;?>">Column settings</button>
				<button id="table_settings" class="button tablink <?php echo $active2;?>" data-target="table_rights_<?php echo $this->shortcodedata->id;?>">Table settings</button>
				
				<div class="tabcontent <?php echo $class1;?>" id="column_settings_<?php echo $this->shortcodedata->id;?>">
					<form class="sortable_column_settings_rows">
						<input type='hidden' class='shortcode_settings' name='shortcode_id'							value='<?php echo $this->shortcodedata->id;?>'>
						<input type='hidden' class='shortcode_settings' name='action'								value='save_column_settings'>
						<input type='hidden' class='shortcode_settings' name='save_column_settings_nonce'			value='<?php echo wp_create_nonce('save_column_settings_nonce');?>'>
						
						<div class="column_setting_wrapper" style="display: flex;">
							<label class="columnheading formfieldbutton">Sort</label>
							<label class="columnheading column_settings">Field name</label>
							<label class="columnheading column_settings">Display name</label>
							<label style="width: 30px;"></label>
							<label class="columnheading column_settings">Display permissions</label>
							<label class="columnheading column_settings">Edit permissions</label>
						</div>
						<?php
						foreach ($this->column_settings as $element_index=>$column_setting){
							$nice_name	= $column_setting['nice_name'];
							
							if($column_setting['show'] == 'hide'){
								$visibility	= 'invisible';
							}else{
								$visibility	= 'visible';
							}
							$icon			= "<img class='visibilityicon $visibility' src='".plugins_url()."/sim-plugin/includes/pictures/$visibility.png'>";
							
							?>
						<div class="column_setting_wrapper" data-id="<?php echo $element_index;?>" style="display: flex;">
							<input type="hidden" class="visibilitytype" name="column_settings[<?php echo $element_index;?>][show]" 		value="<?php echo $column_setting['show'];?>">
							<input type="hidden" name="column_settings[<?php echo $element_index;?>][name]"	value="<?php echo $column_setting['name'];?>">
							<span class="movecontrol formfieldbutton" aria-hidden="true">:::</span>
							<span class="column_settings"><?php echo $column_setting['name'];?></span>
							<input type="text" class="column_settings" name="column_settings[<?php echo $element_index;?>][nice_name]" value="<?php echo $nice_name;?>">
							<span class="visibilityicon"><?php echo $icon;?></span>
							<?php
							//only add view permission for numeric elements others are buttons
							if(is_numeric($element_index)){
							?>
							<select class='column_settings' name='column_settings[<?php echo $element_index;?>][view_right_roles][]' multiple='multiple'>
							<?php
							foreach($view_roles as $key=>$role_name){
								if(in_array($key,(array)$column_setting['view_right_roles'])){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$key' $selected>$role_name</option>";
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
							
							<select class='column_settings' name='column_settings[<?php echo $element_index;?>][edit_right_roles][]' multiple='multiple'>
							<?php
							foreach($edit_roles as $key=>$role_name){
								if(in_array($key,(array)$column_setting['edit_right_roles'])){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$key' $selected>$role_name</option>";
							}
							?>
							</select>
						</div>
						<?php
						}
						?>
						<?php
						echo SIM\add_save_button('submit_column_setting','Save table column settings');
						?>
					</form>
				</div>
				
				<div class="tabcontent <?php echo $class2;?>" id="table_rights_<?php echo $this->shortcodedata->id;?>">
					<form>
						<input type='hidden' class='shortcode_settings' name='shortcode_id'					value='<?php echo $this->shortcodedata->id;?>'>
						<input type='hidden' class='shortcode_settings' name='action'						value='save_table_settings'>
						<input type='hidden' class='shortcode_settings' name='save_table_settings_nonce'	value='<?php echo wp_create_nonce('save_table_settings_nonce');?>'>
						<input type='hidden' class='shortcode_settings' name='formname'						value='<?php echo $this->datatype;?>'>
						
						<div class="table_rights_wrapper">
							<label>Select the default column the table is sorted on</label>
							<select name="table_settings[default_sort]">
							<?php
							if($this->table_settings['default_sort'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							foreach($this->column_settings as $key=>$element){
								$name = $element['nice_name'];
								
								//Check which option is the selected one
								if($this->table_settings['default_sort'] != '' and $this->table_settings['default_sort'] == $key){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$key' $selected>$name</option>";
							}
							?>
							</select>
						</div>
						
						<div class="table_rights_wrapper">
							<label>
								Select a column which determines if a row should be shown.<br>
								The row will be hidden if a cell in this column has no value and the viewer has no right to edit.
							</label>
							<select name="table_settings[hiderow]">
							<?php
							if($this->table_settings['hiderow'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							foreach($this->column_settings as $key=>$element){
								$name = $element['nice_name'];
								
								//Check which option is the selected one
								if($this->table_settings['hiderow'] == $element['name']){
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
							if($this->form_settings['split'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							$found_elements = [];
							foreach($this->form_elements as $key=>$element){
								$pattern = "/([^\[]+)\[[0-9]+\]/i";
								
								if(preg_match($pattern, $element->name, $matches)){
									//Only add if not found before
									if(!in_array($matches[1],$found_elements)){
										$found_elements[]	= $matches[1];
										$value 				= strtolower(str_replace('_',' ',$matches[1]));
										$name				= ucfirst($value);
										
										//Check which option is the selected one
										if($this->form_settings['split'] == $value){
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
								<option value="personal" <?php if($this->table_settings['result_type'] == 'personal') echo 'selected';?>>Only personal</option>
								<option value="all" <?php if($this->table_settings['result_type'] == 'all') echo 'selected';?>>All the viewer has permission for</option>
							</select>
						</div>
						
						<div class="table_rights_wrapper">
							<label class="label">
								Select if you want to view archived results<br>
								<?php
								if($this->table_settings['archived'] == 'true'){
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
								if($this->form_settings['autoarchive'] == 'true'){
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
							<div class='autoarchivelogic <?php if($checked1 == '') echo 'hidden';?>' style="display: flex;width: 100%;">
								Auto archive a (sub) entry when field
								<select name="form_settings[autoarchivefield]" style="margin-right:10px;">
								<?php
								if($this->form_settings['autoarchivefield'] == ''){
									?><option value='' selected>---</option><?php
								}else{
									?><option value=''>---</option><?php
								}
								
								foreach($this->column_settings as $key=>$element){
									$name = $element['nice_name'];
									
									//Check which option is the selected one
									if($this->form_settings['autoarchivefield'] != '' and $this->form_settings['autoarchivefield'] == $key){
										$selected = 'selected';
									}else{
										$selected = '';
									}
									echo "<option value='$key' $selected>$name</option>";
								}
								?>
								</select>
								<label style="margin:0 10px;">equals</label>
								<input type='text' name="form_settings[autoarchivevalue]" value="<?php echo $this->form_settings['autoarchivevalue'];?>">
								
								<div class="infobox" name="info" style="min-width: fit-content;">
									<div style="float:right">
										<p class="info_icon">
											<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo plugins_url();?>/sim-plugin/includes/pictures/info.png">
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
							foreach($view_roles as $key=>$role_name){
								if(in_array($key,array_keys((array)$this->table_settings['view_right_roles']))){
									$checked = 'checked';
								}else{
									$checked = '';
								}
								
								echo "<label class='option-label'>";
									echo "<input type='checkbox' class='formbuilder formfieldsetting' name='table_settings[view_right_roles][$key]' value='$role_name' $checked>";
									echo "$role_name";
								echo "</label><br>";
							}
							?>
							</div>
						</div>
						
						<div class="table_rights_wrapper">
							<label class="label">Select roles with permission to edit ALL form submission data</label>
							<div class="role_info">
							<?php
							foreach($edit_roles as $key=>$role_name){
								if(in_array($key,array_keys((array)$this->table_settings['edit_right_roles']))){
									$checked = 'checked';
								}else{
									$checked = '';
								}
								echo "<label class='option-label'>";
									echo "<input type='checkbox' class='formbuilder formfieldsetting' name='table_settings[edit_right_roles][$key]' value='$role_name' $checked>";
									echo " $role_name";
								echo "</label><br>";
							}
							?>
							</div>
						</div>
					<?php
					echo SIM\add_save_button('submit_table_setting','Save table access settings');
					?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
	
	function show_formresults_table($atts){
		global $StyleVersion;

		ob_start();
		
		$this->process_atts($atts);
		
		//load shortcode settings
		$this->loadshortcodedata();
		
		//load form settings
		$this->loadformdata();
		
		$this->form_settings		= $this->formdata->settings;
		
		if($this->table_settings['archived'] == 'true' or $_GET['archived']){
			$this->show_archived = true;
		}else{
			$this->show_archived = false;
		}
		
		//check if we have rights on this form
		if(!$this->form_edit_permissions){
			if(
				array_intersect((array)$this->user_roles, array_keys((array)$this->form_settings['full_right_roles'])) != false	or
				array_intersect((array)$this->user_roles, array_keys((array)$this->table_settings['full_right_roles'])) != false	or
				$this->editrights
			){
				$this->form_edit_permissions = true;
			}else{
				$this->form_edit_permissions = false;
			}
		}
		
		//check if we have rights on this table
		if(!$this->table_edit_permissions){
			if(array_intersect($this->user_roles, array_keys((array)$this->table_settings['edit_right_roles'])) != false){
				$this->table_edit_permissions = true;
			}else{
				$this->table_edit_permissions = false;
			}
		}
		
		if(
			$_GET['onlyown'] == 'true' or 
			$this->table_settings['result_type'] == 'personal'	or
			!$this->table_edit_permissions and
			array_intersect($this->user_roles, array_keys((array)$this->table_settings['view_right_roles'])) == false
		){
			$this->table_view_permissions = false;
			$this->get_submission_data($this->user->ID);
		}else{
			$this->table_view_permissions = true;
			$this->get_submission_data();
		}
		
		//process any $_GET acions
		do_action('formtable_GET_actions');
		do_action('formtable_POST_actions');
		
		//Load js
		wp_enqueue_script('sim_forms_table_script', plugins_url('js/forms_table.min.js', __DIR__), array('sim_table_script','sim_other_script'),$StyleVersion,true);
		
		//do not show if not logged in
		if(!is_user_logged_in()) return;
		
		?>
		<div class='form_table_wrapper'>
			<h2 class="table_title"><?php echo esc_html($this->form_settings['formname']); ?></h2><br>
			
			<?php
			//Show form properties button if we have form edit permissions
			if($this->form_edit_permissions){
				?>
				<button class='button edit_formshortcode_settings'>Edit settings</button>
				<?php
				$this->add_shortcode_settings_modal();
			}

			if($this->show_archived){
				if($_GET['archived']){
				?>
				<a href="." class="button sim">Hide archived entries</a>
				<?php
				}
			}else{
				?>
				<a href="?archived=true" class="button sim">Show archived entries</a>
				<?php
			}

			if($_GET['onlyown'] or $this->table_settings['result_type'] == 'personal'){
				?>
				<a href="." class="button sim">Show all entries</a>
				<?php
			}else{
				?>
				<a href="?onlyown=true" class="button sim">Show only my own entries</a>
				<?php
			}
			
			
			$this->no_records	= true;
			if(count($this->submission_data) != 0){
				$this->enrich_column_settings();
				
				/* 
					Write the header row of the table 
				*/
				//first check if the data contains data of our own
				$this->owndata	= false;
				$this->user->partner_id		= SIM\has_partner($this->user->ID);
				foreach($this->submission_data as $key=>$submission_data){
					//Our own entry or one of our partner
					if($submission_data->userid == $this->user->ID or $submission_data->userid == $this->user->partner_id){
						$this->owndata = true;
						break;
					}
				}
				
				?>
				<table class='table form_data_table' data-id='<?php echo $this->datatype;?>' data-shortcodeid='<?php echo $this->shortcode_id;?>' data-nonce='<?php echo wp_create_nonce('updateforminput');?>'>
					<thead class="table-head">
						<tr>
							<?php
							//add normal fields
							foreach($this->column_settings as $setting_id=>$column_setting){
								if(
									!is_numeric($setting_id) or
									$column_setting['show'] == 'hide' or													//hidden column
									(
										!$this->owndata or 																	//The table does not contain data of our own
										(
											$this->owndata and 																//or it does contain our own data but
											!in_array('own',(array)$column_setting['view_right_roles'])							//we are not allowed to see it
										)
									) and																					
									!$this->table_edit_permissions and														//no permission to edit the table and
									!empty($column_setting['view_right_roles']) and 										// there are view right permissions defined
									array_intersect($this->user_roles, (array)$column_setting['view_right_roles']) == false		// and we do not have the view right role and
								){ 
									continue;
								}
								
								$nice_name			= $column_setting['nice_name'];
								
								if($this->table_settings['default_sort']	== $setting_id){
									$class	= " class='defaultsort'"; 
								}else{
									$class	= ""; 
								}
								
								//Add a heading for each column
								echo "<th id='{$column_setting['name']}' data-nicename='$nice_name'$class>$nice_name</th>";
								
								$excelrow[]	= $nice_name;
							}
							
							//add a Actions heading if needed
							$buttons_html = [];
							foreach($this->form_settings['actions'] as $action){
								$buttons_html[$action]	= "";
							}

							//we have full permissions on this table
							if($this->table_edit_permissions and !empty($buttons_html)){
								$add_heading	= true;
							}else{
								$buttons_html = apply_filters('form_actions',$buttons_html);
								foreach($buttons_html as $action=>$button){
									//we have permission for this specific button
									if(array_intersect($this->user_roles, (array)$this->column_settings[$action]['edit_right_roles']) != false){
										$add_heading	= true;
									}else{
										//Loop over all buttons to see if the current user has permission for them
										foreach($this->submission_data as $key=>$submission_data){
											$field_values			= unserialize($submission_data->formresults);
											foreach($buttons_html as $action=>$button){
												//we have permission on this row for this button
												if($field_values['userid'] == $this->user->ID){
													$add_heading	= true;
												}
											}
										}
									}
								}
							}

							if($add_heading){
								echo "<th id='actions' data-nicename='Actions'>Actions</th>";
							}
							?>
						</tr>
					</thead>
					
					<tbody class="table-body">
				
					<?php
					//write header to excel
					$this->excel_content[] = $excelrow;
					/* 
							WRITE THE CONTENT ROWS OF THE TABLE 
					*/
					//Loop over all the submissions of this form
					foreach($this->submission_data as $key=>$submission_data){
						$field_values			= unserialize($submission_data->formresults);
						$field_values['id']		= $submission_data->id;
						$field_values['userid']	= $submission_data->userid;
						
						if(!empty($this->form_settings['split'])){
							$field_main_name	= $this->form_settings['split'];
							
							//create table rows for as many entries there are for the split key
							//first check for empty rows
							foreach($field_values[$field_main_name] as $split_key=>$array){
								$skip = true;
								//get the current row 
								if(is_array($array)){
									foreach($array as $row_key=>$value){
										//If we should not see archived items, remove this row
										if($row_key == 'archived' and $value == true and $this->show_archived == false){
											//removed archived rows
											unset($field_values[$field_main_name][$split_key]);
										}else{
											//we found at least one value in this row
											if(!empty($value))	$skip = false;
										}
									}
								}
								//Remove empty entry from array
								if($skip){
									unset($field_values[$field_main_name][$split_key]);
								}
							}
							
							//write the rows
							foreach($field_values[$field_main_name] as $split_key=>$array){
								$this->write_table_row($field_values,$split_key,$submission_data->archived);
							}
						}else{
							$this->write_table_row($field_values,-1,$submission_data->archived);
						}
						
					}
					?>
					</tbody>
				</table>
					
				<p id="table_remark">Click on any cell with <span class="edit">underlined text</span> to edit its contents.<br>Click on any header to sort the column.</p>
				
				<?php
				//Add excel export button if allowed
				if($this->table_edit_permissions){
					?>
					<div>
						<form method="post" class="exportform" id="export_xls">
							<button class="button button-primary" type="submit" name="export_xls">Export data to excel</button>
						</form>
						<form method="post" class="exportform" id="export_pdf">
							<button class="button button-primary" type="submit" name="export_pdf">Export data to pdf</button>
						</form>
					</div>
				<?php
				}
			}
			
			if($this->no_records	== true){
				?><p><br><br><br>No records found</p><?php
			}
			
			?>
		</div>
		<?php
		
		//now we have rendered all the content we can export the excel if requested
		if(isset($_POST['export_xls']))		$this->export_excel();

		//now we have rendered all the content we can export the excel if requested
		if(isset($_POST['export_pdf']))		echo $this->export_pdf();
		
		return ob_get_clean();
	}

	function insert_in_db($form_id){
		global $wpdb;

		//add new row in db
		$wpdb->insert(
			$this->shortcodetable, 
			array(
				'table_settings'	=> '',
				'form_id'			=> $form_id,
			)
		);

		return $wpdb->insert_id;
	}

	//check for any formresults shortcode and add an id if needed
	function check_for_form_shortcode($data , $postarr) {
		global $wpdb;
		
		//find any formresults shortcode
		$pattern = "/\[formresults([^\]]*datatype=([a-zA-Z]*)[^\]]*)\]/s";
		
		//if there are matches
		if(preg_match_all($pattern, $data['post_content'], $matches)) {			
			//loop over all the matches
			foreach($matches[1] as $key=>$shortcode_atts){
				//this shortcode has no id attribute
				if (strpos($shortcode_atts, ' id=') === false) {
					$shortcode = $matches[0][$key];
					
					$this->datatype = $matches[2][$key];

					$this->loadformdata();

					$this->create_db_shortcode_table();
					
					$this->insert_in_db($this->formdata->id);
					
					$shortcode_id	= $wpdb->insert_id;
					$new_shortcode	= str_replace('formresults',"formresults id=$shortcode_id",$shortcode);
					
					//replace the old shortcode with the new one
					$pos = strpos($data['post_content'], $shortcode);
					if ($pos !== false) {
						$data['post_content'] = substr_replace($data['post_content'], $new_shortcode, $pos, strlen($shortcode));
					}
				}
			}
		}
		
		return $data;
	}
}

add_filter( 'wp_insert_post_data', function($data , $postarr){
	$formtable = new FormTable();
	return $formtable->check_for_form_shortcode($data , $postarr);
}, 10, 2 );