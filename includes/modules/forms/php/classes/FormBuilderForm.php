<?php
namespace SIM\FORMS;
use SIM;
use stdClass;

class FormBuilderForm extends SimForms{
	use ElementHtml;

	public $isInDiv;
	public $defaultArrayValues;
	public $defaultValues;
	public $nextElement;
	public $prevElement;
	public $currentElement;

	public function __construct($atts=[]){
		parent::__construct();

		if(!empty($atts)){
			$this->processAtts($atts);
			$this->getForm();
			$this->getAllFormElements();
		}
	}

	/**
	 * Creates a dropdown with all form elements
	 *
	 * @param	int		$selectedId	The id of the current selected element in the dropdown. Default empty
	 * @param	int		$elementId	the id of the element
	 *
	 * @return	string	The dropdown html
	 */
	protected function inputDropdown($selectedId, $elementId=''){
		$html = "";
		if($selectedId == ''){
			$html = "<option value='' selected>---</option>";
		}else{
			$html = "<option value=''>---</option>";
		}

		// Add booking date elements
		$elements	= apply_filters('sim-forms-elements', $this->formElements, $this, true);
		
		foreach($elements as $element){
			//do not include the element itself do not include non-input types
			if($element->id != $elementId && !in_array($element->type, ['label', 'info', 'datalist', 'formstep', 'div_end'])){
				$name = ucfirst(str_replace('_', ' ', $element->name));

				// add the id if non-unique name
				if(str_contains($name, '[]')){
					$name	.= " ($element->id)";
				}
				
				//Check which option is the selected one
				if(
					!empty($selectedId) &&					// there is an option selected
					(
						$selectedId == $element->id	||		// its the current element
						is_array($selectedId)		&&		// multiple elements are selected
						in_array($element->id, $selectedId)	// current element is one of the selected

					)
				){
					$selected = 'selected="selected"';
				}else{
					$selected = '';
				}
				$html .= "<option value='{$element->id}' $selected>$name</option>";
			}
		}
		
		return $html;
	}

	/**
	 * Build all html for a particular element including edit controls.
	 *
	 * @param	object	$element		The element
	 * @param	int		$key			The key in case of a multi element. Default 0
	 *
	 * @return	string					The html
	 */
	public function buildHtml($element, $key=0){
		if(isset($this->formElements[$key+1])){
			$this->nextElement		= $this->formElements[$key+1];
		}else{
			$this->nextElement		= '';
		}

		//store the prev rendered element before updating the current element
		$prevRenderedElement	= $this->currentElement;
		$this->currentElement	= $element;
				
		//Set the element width to 85 percent so that the info icon floats next to it
		if($key != 0 && $prevRenderedElement->type == 'info'){
			$width = 85;
		//We are dealing with a label which is wrapped around the next element
		}elseif($element->type == 'label'	&& !isset($element->wrap) && is_numeric($this->nextElement->width)){
			$width = $this->nextElement->width;
		}elseif(is_numeric($element->width)){
			$width = $element->width;
		}else{
			$width = 100;
		}

		//Load default values for this element
		$elementHtml = $this->getElementHtml($element);
		
		//Check if element needs to be hidden
		if(!empty($element->hidden)){
			$hidden = ' hidden';
		}else{
			$hidden = '';
		}
		
		//if the current element is required or this is a label and the next element is required
		if(
			!empty($element->required)		||
			!empty($element->mandatory)		||
			$element->type == 'label'		&&
			(
				$this->nextElement->required	||
				$this->nextElement->mandatory
			)
		){
			$hidden .= ' required';
		}
		
		$idHidden	= ' hidden';
		if(isset($_REQUEST['showid'])){
			$idHidden	= '';
		}

		$marginLeft	= '';
		if($this->isInDiv && $element->type != 'div_end'){
			$marginLeft	= 'margin-left: 30px;';
		}

		//Add form edit controls if needed
		$html = " <div class='form_element_wrapper' data-id='{$element->id}' data-formid='{$this->formData->id}' data-priority='{$element->priority}' data-type='$element->type' style='display: flex; $marginLeft'>";
			$html 	.= "<span class='movecontrol formfieldbutton' aria-hidden='true'>:::<br><span class='elid$idHidden' style='font-size:xx-small'>$element->id</span></span>";
			$html 	.= "<div class='resizer_wrapper'>";
				if($element->type == 'info'){
					$html .= "<div class='show inputwrapper$hidden'>";
				}else{
					$html .= "<div class='resizer show inputwrapper$hidden' data-widthpercentage='$width' style='width:$width%;'>";
				}
				
				if($element->type == 'formstep'){
					$html .= ' ***Formstep element***</div>';
				}elseif($element->type == 'datalist'){
					$html .= " ***Datalist element $element->name***";
				}elseif($element->type == 'multi_start'){
					$html .= ' ***Multi answer start***';
					$elementHtml	= '';
				}elseif($element->type == 'multi_end'){
					$html .= ' ***Multi answer end***';
					$elementHtml	= '';
				}elseif($element->type == 'div_start'){
					$name			= ucfirst(str_replace('_', ' ', $element->name));
					$html 			.= " ***$name div container start***";
					$elementHtml	= '';
					$this->isInDiv	= true;
				}elseif($element->type == 'div_end'){
					$name			= ucfirst(str_replace('_', ' ', $element->name));
					$html 			.= " ***$name div container end***";
					$elementHtml	= '';
					$this->isInDiv	= false;
				}
				
				$hidden	= ' hidden';
				if(isset($_REQUEST['showname'])){
					$hidden	= '';
				}

				$html .= $elementHtml;
					$html	.= "<span class='elname$hidden' style='font-size:xx-small;'>$element->name</span>";
					
					//Add a symbol if this field has conditions or is required
					if(!empty($element->conditions) || !empty($element->required) || !empty($element->mandatory)){
							$icons		= [];
							if(!empty($element->conditions)){
								$icons[]		= [
									'content' 	=> '*',
									'explainer'	=> 'This element has conditions',
									'class'		=> '',
									'right'		=> 20
								];
							}

							if(!empty($element->required)){
								$right			= 20;
								if(count($icons) > 0){
									$right	= 50;
								}
								$icons[]		= [
									'content' 	=> '!',
									'explainer'	=> 'This element is required',
									'class'		=> '',
									'right'		=> $right
								];
							}

							if(!empty($element->mandatory)){
								$right			= 20;
								if(count($icons) == 1){
									$right	= 50;
								}elseif(count($icons) == 2){
									$right	= 80;
								}

								$icons[]		= [
									'content' 	=> '!',
									'explainer'	=> 'This element is conditionally required',
									'class'		=> 'conditional',
									'right'		=> $right
								];
							}

							if(!empty($icons)){
								$right	= $icons[array_key_last($icons)]['right'] + 30;
								
								foreach($icons as $icon){
									$style	= "position: absolute;margin: 0;right: {$right}px;top: 5px;height: 30px;";
									$html .= "<div class='infobox' style='position: absolute;top: 0;width: 100%;'>";
										$html .= "<span class='conditions_info formfieldbutton {$icon['class']}' style='right:{$icon['right']}px'>{$icon['content']}</span>";
										$html .= "<span class='info_text conditions' style='$style'>{$icon['explainer']}</span>";
									$html .= "</div>";
								}
							}
						
					}

					$html .= "<span class='widthpercentage formfieldbutton'></span>";
				$html .= "</div>";
			$html .= "</div>";
			$html .= "<button type='button' class='add_form_element button formfieldbutton' 	title='Add an element after this one'>+</button>";
			$html .= "<button type='button' class='remove_form_element button formfieldbutton'	title='Remove this element'>-</button>";
			$html .= "<button type='button' class='edit_form_element button formfieldbutton'	title='Change this element'>Edit</button>";
		$html .= "</div>";

		return $html;
	}

	/**
	 * Main function to show all
	 *
	 * @param	array	$atts	The attribute array of the WP Shortcode
	 */
	public function showForm(){
		// Load js
		wp_enqueue_script('sim_forms_script');

		// make sure we use unique priorities
		

		ob_start();

		if(isset($_POST['export-form']) && is_numeric($_POST['export-form'])){
			$this->exportForm($_POST['export-form']);
		}

		if(isset($_POST['delete-form']) && is_numeric($_POST['delete-form'])){
			$saveFormSettings	= new SaveFormSettings();
			$saveFormSettings->deleteForm($_POST['delete-form']);

			return "<div class='success'>Form successfully deleted.</div>";
		}

		//Formbuilder js
		wp_enqueue_script( 'sim_formbuilderjs');
		
		?>
		<div class="sim-form-wrapper">
			<?php
			$this->addElementModal();

			?>
			<button class="button tablink formbuilderform<?php if(!empty($this->formElements)){echo ' active';}?>"	id="show_element_form" data-target="element_form">Form elements</button>
			<button class="button tablink formbuilderform<?php if(empty($this->formElements)){echo ' active';}?>"	id="show_form_settings" data-target="form_settings">Form settings</button>
			<button class="button tablink formbuilderform"															id="show_form_emails" data-target="form_emails">Form emails</button>
			
			<div class="tabcontent<?php if(empty($this->formElements)){echo ' hidden';}?>" id="element_form">
				<?php
				if(empty($this->formElements)){
					?>
					<div name="formbuildbutton">
						<p>No formfield defined yet.</p>
						<button name='createform' class='button' data-formname='<?php echo $this->formName;?>'>Add fields to this form</button>
					</div>
					<?php
				}else{
					?>
					<div class="formeditbuttons_wrapper">
						<button name='showid' class='button' data-action='show' style='padding-top:0px;padding-bottom:0px;'>Show element id's</button>
						<button name='showname' class='button' data-action='show' style='padding-top:0px;padding-bottom:0px;'>Show element name's</button>
						<button class='button formbuilder-switch-back small'>Show enduser form</button>
					</div>
					<?php
				}
			
				?>
				<form action='' method='post' class='sim-form builder'>
					<div class='form_elements'>
						<input type='hidden' name='formid'		value='<?php echo $this->formData->id;?>'>

						<?php
						foreach($this->formElements as $key=>$element){
							echo $this->buildHtml($element, $key);
						}
						?>
					</div>
				</form>
			</div>
				
			<div class="tabcontent<?php if(!empty($this->formElements)){echo ' hidden';}?>" id="form_settings">
				<?php $this->formSettingsForm();?>
			</div>
			
			<div class="tabcontent hidden" id="form_emails">
				<?php $this->formEmailsForm();?>
			</div>
		</div>
		<?php
		
		//needed for force_balance_tags
		include_once( ABSPATH . 'wp-includes/formatting.php');

		$html	= ob_get_clean();
		//close any open html tags
		return force_balance_tags($html);
	}
	
	/**
	 * The modal to add an element to the form
	 */
	public function addElementModal(){
		?>
		<div class="modal add_form_element_modal hidden">
			<!-- Modal content -->
			<div class="modal-content" style='max-width:90%; width:max-content;'>
				<span id="modal_close" class="close">&times;</span>
				
				<button class="button tablink formbuilderform active"	id="show_element_builder" data-target="element_builder">Form element</button>
				<button class="button tablink formbuilderform"			id="show_element_conditions" data-target="element_conditions">Element conditions</button>
				
				<div class="tabcontent" id="element_builder">
					<?php echo $this->elementBuilderForm();?>
				</div>
				
				<div class="tabcontent hidden" id="element_conditions">
					<div class="element_conditions_wrapper"></div>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Form to change form settings
	 */
	public function formSettingsForm(){

		global $wp_roles;

		//Get all available roles
		$userRoles = $wp_roles->role_names;
		
		//Sort the roles
		asort($userRoles);
		
		?>
		<div class="element_settings_wrapper">
			<form action='' method='post' class='sim-form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="block">
						<h4>Submit button text</h4>
						<input type='text' class='formbuilder formfieldsetting' name='button_text' value="<?php echo $this->formData->button_text?>">
					</label>
					
					<label class="block">
						<h4>Succes message</h4>
						<input type='text' class='formbuilder formfieldsetting' name='succes_message' value="<?php echo $this->formData->succes_message?>">
					</label>

					<label class="block">
						<h4>Include submission ID in message</h4>
						<label>
							<input type='radio' class='formbuilder formfieldsetting' name='include_id' value="yes" <?php if(!isset($this->formData->include_id) || $this->formData->include_id){echo 'checked';}?>>
							Yes
						</label>
						<label>
							<input type='radio' class='formbuilder formfieldsetting' name='include_id' value="no" <?php if(isset($this->formData->include_id) && !$this->formData->include_id){echo 'checked';}?>>
							No
						</label>
					</label>
					
					<label class="block">
						<h4>Form name</h4>
						<input type='text' class='formbuilder formfieldsetting' name='form_name' value="<?php echo $this->formData->form_name?>">
					</label>
					<br>
					
					<label class='block'>
						<?php
						if($this->formData->save_in_meta){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='save_in_meta' value='1' <?php echo $checked;?>>
						Save submissions in usermeta table
					</label>
					<br>

					<div class='recurring-submissions<?php if($this->formData->save_in_meta){ echo ' hidden';}?>'>
						<h4>Recurring Submissions</h4>
						Request new form submissions every 
						<input type='number' name='reminder_frequency' value='<?php echo $this->formData->reminder_frequency;?>' style='max-width: 70px;'>

						<?php
							foreach(['years', 'months', 'days'] as $period) {
								if(isset($this->formData->reminder_period) && $this->formData->reminder_period == $period){
									$checked = 'checked';	
								}else{
									$checked = '';
								}

								?>
								<label>
									<input type='radio' name='reminder_period' id='reminder_period' value='<?php echo $period;?>' <?php echo $checked;?>>
									<?php echo $period;?>
								</label>
								<?php
							}
						?>

						<div class='<?php if($this->formData->save_in_meta){ echo 'hidden';}?>'>
							<label>
								Start reminding from 
								<input type='date' name='reminder_startdate' value='<?php echo $this->formData->reminder_startdate;?>'>
							</label>
						</div>

						<?php echo $this->warningConditionsForm('reminder_conditions', maybe_unserialize($this->formData->reminder_conditions));?>
					</div>

					<label class="block">
						<h4>Form url</h4>
						<?php
						if(!empty($this->formData->form_url)){
							$url	= $this->formData->form_url;
						}else{
							$url	= str_replace(['?formbuilder=yes', '&formbuilder=yes'], '', SIM\currentUrl(true));
						}

						?>
						<input type='url' class='formbuilder formfieldsetting' name='form_url' value="<?php echo $url?>">
					</label>
					<br>
					
					<?php
					//check if we have any upload fields in this form
					$hideUploadEl	= true;
					foreach($this->formElements as $el){
						if($el->type == 'file' || $el->type == 'image'){
							$hideUploadEl	= false;
							break;
						}
					}
					?>
					<label class='block <?php if($hideUploadEl){echo 'hidden';}?>'>
						<h4>Save form uploads in this subfolder of the uploads folder:<br>
						If you leave it empty the default form_uploads will be used</h4>
						<input type='text' class='formbuilder formfieldsetting' name='upload_path' value='<?php echo $this->formData->upload_path;?>'>
					</label>
					<br>
					
					<label>
						<?php
						if($this->formData->form_reset){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='form_reset' value='form_reset' <?php echo $checked;?>>
						Reset form after succesfull submission
					</label>
					<br>
						
					<h4>Available actions</h4>
					<?php
					$actions = ['archive','delete'];
					foreach($actions as $action){
						if(!empty($this->formData->actions[$action])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<label class='option-label'>
							<input type='checkbox' class='formbuilder formfieldsetting' name='actions[<?php echo $action;?>]' value='<?php echo $action;?>' <?php echo $checked;?>>
							<?php echo ucfirst($action);?>
						</label><br>
						<?php
					}
					?>
					
					<div class="formsettings_wrapper">
						<label class="block">
							<h4>Auto archive results</h4>
							<br>
							<?php
							if($this->formData->autoarchive){
								$checked1	= 'checked';
								$checked2	= '';
							}else{
								$checked1	= '';
								$checked2	= 'checked';
							}
							?>
							<label>
								<input type="radio" name="autoarchive" value="true" <?php echo $checked1;?>>
								Yes
							</label>
							<label>
								<input type="radio" name="autoarchive" value="false" <?php echo $checked2;?>>
								No
							</label>
						</label>
						<br>
						<div class='autoarchivelogic <?php if(empty($checked1)){echo 'hidden';}?>' style="display: flex;width: 100%;">
							Auto archive a (sub) entry when field
							<select name="autoarchive_el" style="margin-right:10px;">
								<?php
								if(empty($this->formData->autoarchive_el)){
									?><option value='' selected>---</option><?php
								}else{
									?><option value=''>---</option><?php
								}
								
								$processed = [];
								foreach($this->formElements as $key=>$element){
									if(in_array($element->type, $this->nonInputs)){
										continue;
									}
									
									$pattern			= "/\[[0-9]+\]\[([^\]]+)\]/i";
									
									$name = $element->name;
									if(preg_match($pattern, $element->name,$matches)){
										//We found a keyword, check if we already got the same one
										if(!in_array($matches[1],$processed)){
											//Add to the processed array
											$processed[]	= $matches[1];
											
											//replace the name
											$name		= $matches[1];
										}else{
											//do not show this element
											continue;
										}
									}
									
									//Check which option is the selected one
									if(!empty($this->formData->autoarchive_el) && $this->formData->autoarchive_el == $element->id){
										$selected = 'selected="selected"';
									}else{
										$selected = '';
									}
									echo "<option value='{$element->id}' $selected>$name</option>";
								}
								
								?>
							</select>
							<label style="margin:0 10px;">equals</label>
							<input type='text' name="autoarchive_value" value="<?php echo $this->formData->autoarchive_value;?>">
							<div class="infobox" name="info" style="min-width: fit-content;">
								<div style="float:right">
									<p class="info_icon">
										<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo SIM\PICTURESURL;?>/info.png" loading='lazy' >
									</p>
								</div>
								<span class="info_text">
									You can use placeholders like '%today%+3days' for a value
								</span>
							</div>
						</div>
					</div>

					<?php do_action('sim-forms-extra-form-settings', $this); ?>

					<div style='margin-top:10px;'>
						<button class='button builder-permissions-rights-form' type='button'>Advanced</button>
						<div class='permission-wrapper hidden'>
							<?php
							// Splitted fields
							$foundElements = [];
							foreach($this->formElements as $key=>$element){
								$pattern = "/([^\[]+)\[[0-9]*\]/i";
								
								if(preg_match($pattern, $element->name, $matches)){
									//Only add if not found before
									if(!in_array($matches[1], $foundElements)){
										$foundElements[$element->id]	= $matches[1];
									}
								}
							}

							if(!empty($foundElements)){
								?>
								<h4>Select fields where you want to create seperate rows for</h4>
								<?php

								foreach($foundElements as $id=>$element){
									$name	= ucfirst(strtolower(str_replace('_', ' ', $element)));
									
									//Check which option is the selected one
									if(is_array($this->formData->split) && in_array($id, $this->formData->split)){
										$checked = 'checked';
									}else{
										$checked = '';
									}
									echo "<label>";
										echo "<input type='checkbox' name='split[]' value='$id' $checked>   ";
										echo $name;
									echo "</label><br>";
								}
							}
							?>

							<h4>Select roles with form edit rights</h4>
							<div class="role_info">
								<?php
								foreach($userRoles as $key=>$roleName){
									if(!empty($this->formData->full_right_roles[$key])){
										$checked = 'checked';
									}else{
										$checked = '';
									}
									echo "<label class='option-label'>";
										echo "<input type='checkbox' class='formbuilder formfieldsetting' name='full_right_roles[$key]' value='$roleName' $checked>";
										echo $roleName;
									echo"</label><br>";
								}
								?>
							</div>
							<br>
							
							<div class='submit_others_form_wrapper'>
								<h4>Select who can submit this form on behalf of someone else</h4>
								<?php
								foreach($userRoles as $key=>$roleName){
									if(!empty($this->formData->submit_others_form[$key])){
										$checked = 'checked';
									}else{
										$checked = '';
									}
									echo "<label class='option-label'>";
										echo "<input type='checkbox' class='formbuilder formfieldsetting' name='submit_others_form[$key]' value='$roleName' $checked>";
										echo $roleName;
									echo"</label><br>";
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
				echo SIM\addSaveButton('submit_form_setting',  'Save form settings');
				?>
			</form>
			<form method="POST" style='display: inline-block;'>
				<button type='submit' class='button' name="export-form" value='<?php echo $this->formData->id;?>'>Export this form</button>
			</form>
			<form method="POST" style='display: inline-block;'>
				<input type="hidden" name="page-id" value='<?php echo get_the_ID();?>'>
				<button type='submit' class='button' name="delete-form" value='<?php echo $this->formData->id;?>'>Delete this form</button>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Form to setup form e-mails
	 */
	public function formEmailsForm(){
		$emails = $this->formData->emails;
		?>
		<div class="emails_wrapper">
			<form action='' method='post' class='sim-form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="formfield formfieldlabel">
						Define any e-mails you want to send.<br>
						You can use placeholders in your inputs.<br>
						These default ones are available:<br><br>
					</label>
					<span class='placeholders' title="Click to copy">%id%</span>
					<?php
					if(!empty($this->formData->split)){
						?>
						<span class='placeholders' title="Click to copy">%subid%</span>
						<?php
					}
					?>
					<span class='placeholders' title="Click to copy">%formurl%</span>
					<span class='placeholders' title="Click to copy">%submissiondate%</span>
					<span class='placeholders' title="Click to copy">%editdate%</span>
					<span class='placeholders' title="Click to copy">%submissiontime%</span>
					<span class='placeholders' title="Click to copy">%edittime%</span>
					<br>
					All your fieldvalues are available as well:
					<select class='nonice placeholderselect'>
						<option value=''>Select to copy to clipboard</option><?php
						foreach($this->formElements as $element){
							$element->name	= str_replace('[]', '', $element->name);
							if(!in_array($element->type, ['label','info','button','datalist','formstep'])){
								echo "<option>%{$element->name}%</option>";
							}
						}
						do_action('sim-add-email-placeholder-option', $this);
						?>
					</select>
					
					<br>
					<div class='clone_divs_wrapper'>
						<?php
						foreach($emails as $key=>$email){
							$defaultFrom	=  get_option( 'admin_email' );
							
							?>
							<div class='clone_div' data-divid='<?php echo $key;?>'>
								<h4 class="formfield" style="margin-top:50px; display:inline-block;">E-mail <?php echo $key+1;?></h4>
								<button type='button' class='add button' style='flex: 1;'>+</button>
								<button type='button' class='remove button' style='flex: 1;'>-</button>
								<div style='width:100%;'>
									<div class="formfield formfieldlabel" style="margin-top:10px;">
										Send e-mail when:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='submitted' <?php if($email['emailtrigger'] == 'submitted'){echo 'checked';}?>>
											The form is submitted
										</label><br>

										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='shouldsubmit' <?php if($email['emailtrigger'] == 'shouldsubmit'){echo 'checked';}?>>
											The form is due for submission
										</label><br>

										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='submittedcond' <?php if($email['emailtrigger'] == 'submittedcond'){echo 'checked';}?>>
											The form is submitted and meets a condition
										</label><br>

										<div class='submitted-type <?php if($email['emailtrigger'] != 'submittedcond'){echo 'hidden';}?>'>
											<div class='submitted-trigger-type'>
												Element 
												<select class='' name='emails[<?php echo $key;?>][submittedtrigger][element]' required>
													<?php
													echo $this->inputDropdown($emails[$key]['submittedtrigger']['element'], "emails[$key][submittedtrigger']['element']");
													?>
												</select>

												<select class='' name='emails[<?php echo $key;?>][submittedtrigger][equation]' required>
													<?php
														$optionArray	= [
															''			=> '---',
															'=='		=> 'equals',
															'!='		=> 'is not',
															'>'			=> 'greather than',
															'<'			=> 'smaller than',
															'checked'	=> 'is checked',
															'!checked'	=> 'is not checked',
															'== value'	=> 'equals the value of',
															'!= value'	=> 'does not equal the value of',
															'> value'	=> 'greather than the value of',
															'< value'	=> 'smaller than the value of'
														];

														foreach($optionArray as $option=>$optionLabel){
															if($emails[$key]['submittedtrigger']['equation'] == $option){
																$selected	= 'selected="selected"';
															}else{
																$selected	= '';
															}
															echo "<option value='$option' $selected>$optionLabel</option>";
														}
													?>
												</select>

												<label class='staticvalue <?php if(empty($emails[$key]['submittedtrigger']['equation']) || !in_array($emails[$key]['submittedtrigger']['equation'], ['==', '!=', '>', '<'])){echo 'hidden';}?>'>
													<input type='text' name='emails[<?php echo $key;?>][submittedtrigger][value]' value="<?php echo $emails[$key]['submittedtrigger']['value'];?>" style='width: auto;'>
												</label>

												<select class='dynamicvalue <?php if(empty($emails[$key]['submittedtrigger']['equation']) || in_array($emails[$key]['submittedtrigger']['equation'], ['==', '!=', '>', '<', 'checked', '!checked'])){echo 'hidden';}?>' name='emails[<?php echo $key;?>][submittedtrigger][valueelement]'>
													<?php
														echo $this->inputDropdown($emails[$key]['submittedtrigger']['valueelement'], "emails[$key][submittedtrigger][valueelement]");
													?>
												</select>
											</div>
										</div>

										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='fieldchanged' <?php if($email['emailtrigger'] == 'fieldchanged'){echo 'checked';}?>>
											A field has changed to a value
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='fieldschanged' <?php if($email['emailtrigger'] == 'fieldschanged'){	echo 'checked';}?>>
											One or more fields have changed
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='removed' <?php if($email['emailtrigger'] == 'removed'){echo 'checked';}?>>
											The submission is archived or deleted
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='disabled' <?php if($email['emailtrigger'] == 'disabled'){echo 'checked';}?>>
											Do not send this e-mail
										</label><br>
									</div>
									
									<div class='conditionalfield-wrapper <?php if($email['emailtrigger'] != 'fieldchanged'){echo 'hidden';}?>'>
										<label class="formfield formfieldlabel">Field</label>
										<select name='emails[<?php echo $key;?>][conditionalfield]'>
											<?php
											echo $this->inputDropdown( $email['conditionalfield'] );
											?>
										</select>
										
										<label class="formfield formfieldlabel">
											Value
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalvalue]' value="<?php echo $email['conditionalvalue']; ?>" style='width:fit-content;'>
										</label>
									</div>

									<div class='conditionalfields-wrapper <?php if($email['emailtrigger'] != 'fieldschanged'){echo 'hidden';}?>'>
										<label class="formfield formfieldlabel">Field(s)</label>
										<select name='emails[<?php echo $key;?>][conditionalfields][]' multiple='multiple'>
											<?php
											echo $this->inputDropdown( $email['conditionalfields'] );
											?>
										</select>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Sender e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='fixed' <?php if(empty($email['fromemail']) || $email['fromemail'] == 'fixed'){echo 'checked';}?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='conditional' <?php if($email['fromemail'] == 'conditional'){echo 'checked';}?>>
											Conditional e-mail adress
										</label><br>
									</div>
									
									<div class='emailfromfixed <?php if(!empty($email['fromemail']) && $email['fromemail'] != 'fixed'){echo 'hidden';}?>'>
										<label class="formfield formfieldlabel">
											From e-mail
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][from]' value="<?php if(empty($email['from'])){echo $defaultFrom;} else{echo $email['from'];} ?>">
										</label>
									</div>
									
									<div class='emailfromconditional <?php if($email['fromemail'] != 'conditional'){echo 'hidden';}?>'>
										<div class='clone_divs_wrapper'>
											<?php
											if(!is_array($email['conditionalfromemail'])){
												$email['conditionalfromemail'] = [
													[
														'fieldid'	=> '',
														'value'		=> '',
														'email'		=> ''
													]
												];
											}
											foreach(array_values($email['conditionalfromemail']) as $fromKey=>$fromEmail){
												?>
												<div class='clone_div' data-divid='<?php echo $fromKey;?>'>
													<fieldset class='formemailfieldset'>
														<legend class="formfield buttonwrapper">
															<span class='text'>Condition <?php echo $fromKey+1;?></span>
															<button type='button' class='add button' style='flex: 1;'>+</button>
															<button type='button' class='remove button' style='flex: 1;'>-</button>
														</legend>
														If
														<select name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][fieldid]'>
															<?php
															echo $this->inputDropdown($fromEmail['fieldid']);
															?>
														</select>
														<label class="formfield formfieldlabel">
															equals
															<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][value]' value="<?php echo $fromEmail['value'];?>">
														</label>
														<label class="formfield formfieldlabel">
															then from e-mail address should be:<br>
															<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][email]' value="<?php echo $fromEmail['email'];?>">
														</label>
													</fieldset>
												</div>
												<?php
											}
											?>
											<br>
											<label class="formfield formfieldlabel">
												Else the e-mail will be
												<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][elsefrom]' value="<?php echo $email['elsefrom']; ?>">
											</label>
										</div>
									</div>
									
									<br>
									<div class="formfield tofieldlabel">
										Recipient e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='fixed' <?php if(empty($email['emailto']) || $email['emailto'] == 'fixed'){echo 'checked';}?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='conditional' <?php if($email['emailto'] == 'conditional'){echo 'checked';}?>>
											Conditional e-mail adress
										</label><br>
									</div>
									<br>
									<div class='emailtofixed <?php if(!empty($email['emailto']) && $email['emailto'] != 'fixed'){echo 'hidden';}?>'>
										<label class="formfield formfieldlabel">
											To e-mail
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][to]' value="<?php if(empty($email['to'])){echo '%email%';}else{echo $email['to'];} ?>">
										</label>
									</div>
									<div class='emailtoconditional <?php if($email['emailto'] != 'conditional'){echo 'hidden';}?>'>
										<div class='clone_divs_wrapper'>
											<?php
											if(!is_array($email['conditionalemailto'])){
												$email['conditionalemailto'] = [
													[
														'fieldid'	=> '',
														'value'		=> '',
														'email'		=> ''
													]
												];
											}
											foreach($email['conditionalemailto'] as $toKey=>$toEmail){
												?>
												<div class='clone_div' data-divid='<?php echo $toKey;?>'>
													<fieldset class='formemailfieldset buttonwrapper'>
														<legend class="formfield">
															<span class='text'>Condition <?php echo $toKey+1;?></span>
															<button type='button' class='add button' style='flex: 1;'>+</button>
															<button type='button' class='remove button' style='flex: 1;'>-</button>
														</legend>
														If
														<select name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][fieldid]'>
															<?php
															echo $this->inputDropdown($toEmail['fieldid']);
															?>
														</select>
														<label class="formfield formfieldlabel">
															equals
															<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][value]' value="<?php echo $toEmail['value'];?>">
														</label>
														<label class="formfield formfieldlabel">
															then from e-mail address should be:<br>
															<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][email]' value="<?php echo $toEmail['email'];?>">
														</label>
													</fieldset>
												</div>
												<?php
											}
											?>
											<br>
											<label class="formfield formfieldlabel">
												Else the e-mail will be
												<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][elseto]' value="<?php echo $email['elseto']; ?>">
											</label>
										</div>
									</div>
									<br>
									<div class="formfield formfieldlabel">
										Subject
										<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][subject]' value="<?php echo $email['subject']?>">
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										E-mail content
										<?php
										$settings = array(
											'wpautop' => false,
											'media_buttons' => false,
											'forced_root_block' => true,
											'convert_newlines_to_brs'=> true,
											'textarea_name' => "emails[$key][message]",
											'textarea_rows' => 10
										);
									
										echo wp_editor(
											$email['message'],
											"{$this->formName}_email_message_$key",
											$settings
										);
										?>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Additional headers like 'Reply-To'
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][headers]'><?php
											echo $email['headers']?>
										</textarea>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Form values that should be attached to the e-mail
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][files]'><?php
											echo $email['files']?>
										</textarea>
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				echo SIM\addSaveButton('submit_form_emails','Save form email configuration');
				?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Form to add or edit a new form element
	 */
	public function elementBuilderForm($element=null){
		ob_start();

		$heading	= "Please fill in the form to add a new form element";

		if(is_numeric($element)){
			$element = $this->getElementById($element);

			$heading	= "Change this element";
		}

		$numericElements	= [];
		$dateElements		= [];
		foreach($this->formElements as $el){
			if(in_array($el->type, ['date', 'number', 'range', 'week', 'month']) ){
				$numericElements[]	= $el->id;
			}
			if(in_array($el->type, ['date', 'week', 'month']) ){
				$dateElements[]	= $el->id;
			}
		}
		?>
		<script>
			const numericElements	= <?php echo json_encode($numericElements); ?>;
			const dateElements		= <?php echo json_encode($dateElements); ?>;
		</script>
		<form action="" method="post" name="add_form_element_form" class="form_element_form sim_form" data-addempty=true>
			<div style="display: none;" class="error"></div>
			<h4><?php echo $heading;?></h4><br>

			<input type="hidden" name="formid" value="<?php echo $this->formData->id;?>">

			<input type="hidden" name="formfield[form_id]" value="<?php echo $this->formData->id;?>">
			
			<input type="hidden" name="element_id" value="<?php if( $element != null){echo $element->id;}?>">
			
			<input type="hidden" name="insertafter">
			
			<input type="hidden" name="formfield[width]" value="100">
			
			<label>Element type</label><br>
			<select class="formbuilder elementtype" name="formfield[type]" required>
				<optgroup label="Normal elements">
					<?php
					$options=[
						"button"	=> "Button",
						"checkbox"	=> "Checkbox",
						"color"		=> "Color",
						"date"		=> "Date",
						"select"	=> "Dropdown",
						"email"		=> "E-mail",
						"file"		=> "File upload",
						"image"		=> "Image upload",
						"label"		=> "Label",
						"month"		=> "Month",
						"number"	=> "Number",
						"password"	=> "Password",
						"tel"		=> "Phonenumber",
						"radio"		=> "Radio",
						"range"		=> "Range",
						"text"		=> "Text",
						"textarea"	=> "Text (multiline)",
						"time"		=> "Time",
						"url"		=> "Url",
						"week"		=> "Week"
					];

					foreach($options as $key=>$option){
						if($element != null && $element->type == $key){
							$selected = 'selected="selected"';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
					
				</optgroup>
				<optgroup label="Special elements">
					<?php
					$options	= [
						"hcaptcha"		=> "hCaptcha",
						"recaptcha"		=> "reCaptcha",
						"turnstile"		=> "Cloudflare Turnstile",
						"datalist"		=> "Datalist",
						"div_start"		=> "Div Container - start",
						"div_end"		=> "Div Container - end",
						"formstep"		=> "Multistep",
						"info"			=> "Infobox",
						"multi_start"	=> "Multi-answer - start",
						"multi_end"		=> "Multi-answer - end",
						"p"				=> "Paragraph",
						"php"			=> "Custom code"
					];

					$options	= apply_filters('sim-special-form-elements', $options);

					foreach($options as $key=>$option){
						if($element != null && $element->type == $key){
							$selected = 'selected="selected"';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
				</optgroup>
			</select>
			<br>
			
			<div name='elementname' class='elementoption wide reverse notlabel notphp notformstep button shouldhide' style='background-color: unset;'>
				<label>
					<div style='text-align: left;'>Specify a name for the element</div>
					<input type="text" class="formbuilder wide" name="formfield[name]" value="<?php if($element != null){echo $element->name;}?>">
				</label>
				<br><br>
			</div>
			
			<div name='functionname' class='elementoption wide hidden php'>
				<label>
					Specify the functionname
					<input type="text" class="formbuilder wide" name="formfield[functionname]" value="<?php if($element != null){echo $element->functionname;}?>">
				</label>
				<br><br>
			</div>
			
			<div name='labeltext' class='elementoption label button formstep hidden wide' style='background-color: unset;'>
				<label>
					<div style='text-align: left;'>Specify the <span class='elementtype'>label</span> text</div>
					<input type="text" class="formbuilder wide" name="formfield[text]" value="<?php if($element != null){echo $element->text;}?>">
				</label>
				<br><br>
			</div>

			<div name='upload-options' class='elementoption hidden file image'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[library]" value="true" <?php if($element != null && $element->library){echo 'checked';}?>>
					Add the <span class='filetype'>file</span> to the library
				</label>
				<br><br>

				<label>
					<input type="checkbox" class="formbuilder" name="formfield[editimage]" value="1" <?php if($element != null && $element->editimage){echo 'checked';}?>>
					Allow people to edit an image before uploading it
				</label>
				<br>
				<br>

				<label>
					Name of the folder the <span class='filetype'>file</span> should be uploaded to.<br>
					<input type="text" class="formbuilder" name="formfield[foldername]" value="<?php if($element != null){echo $element->foldername;}?>">
				</label>
			</div>
			
			<div name='wrap' class='elementoption reverse notp notphp notfile notimage shouldhide'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[wrap]" value="true" <?php if($element != null && $element->wrap){echo 'checked';}?>>
					Group together with next element
				</label>
				<br><br>
			</div>
			
			<div name='infotext' class='elementoption info p hidden'>
				<label>
					Specify the text for the <span class='type'>infobox</span>
					<?php
					$settings = array(
						'wpautop'					=> false,
						'media_buttons'				=> false,
						'forced_root_block'			=> true,
						'convert_newlines_to_brs'	=> true,
						'textarea_name'				=> "formfield[infotext]",
						'textarea_rows'				=> 10,
						'editor_class'				=> 'formbuilder'
					);
				
					if(empty($element->text)){
						$content	= '';
					}else{
						$content	= $element->text;
					}
					echo wp_editor(
						$content,
						$this->formData->name."_infotext",	//editor should always have an unique id
						$settings
					);
					?>
					
				</label>
				<br>
			</div>
			
			<div name='multiple' class='elementoption reverse notlabel notbutton notformstep notp notphp shouldhide'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[multiple]" value="true" <?php if($element != null && $element->multiple){echo 'checked';}?>>
					Allow multiple answers
				</label>
				<br>
				<br>
			</div>
			
			<div name='valuelist' class='elementoption datalist radio select checkbox hidden'>
				<label>
					Specify the values, one per line
					<textarea class="formbuilder" name="formfield[valuelist]"><?php if($element != null){echo trim($element->valuelist);}?></textarea>
				</label>
				<br>
			</div>

			<div name='select_options' class='elementoption datalist radio select checkbox hidden'>
				<label class='block'>Specify an options group if desired</label>
				<select class="formbuilder" name="formfield[default_array_value]">
					/*<option value="">---</option>*/
					<?php
					$this->buildDefaultsArray();
					foreach($this->defaultArrayValues as $key=>$field){
						if($element != null && $element->default_array_value == $key){
							$selected = 'selected="selected"';
						}else{
							$selected = '';
						}
						$optionName	= ucfirst(str_replace('_', ' ', $key));
						echo "<option value='$key' $selected>$optionName</option>";
					}
					?>
				</select>
			</div>

			<div name='defaults' class='elementoption reverse notlabel notbutton notformstep notp notphp notfile notimage shouldhide'>
				<label class='block'>Specify a default value if desired</label>
				<input type='text' class="formbuilder" name="formfield[default_value]" list='defaults' value='<?php if($element != null){echo trim($element->default_value);}?>'>

				<datalist id='defaults'>
					<?php
					foreach($this->defaultValues as $key=>$field){
						$optionName	= ucfirst(str_replace('_',' ',$key));
						echo "<option value='$key'>$optionName</option>";
					}
					?>
				</datalist>
			</div>

			<?php
			do_action('sim-after-formbuilder-element-options', $element);
			?>
			<br>
			<div name='elementoptions' class='elementoption reverse notlabel notbutton notformstep notp notphp shouldhide'>
				<label>
					Specify any options like styling
					<textarea class="formbuilder" name="formfield[options]"><?php if($element != null){echo trim($element->options);}?></textarea>
				</label><br>
				<br>
				
				<?php
				$meta	= false;
				if(!empty($this->formData->save_in_meta)){
					$meta	= true;
				}

				if($meta){
					?>
					<h3>Warning conditions</h3>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[mandatory]" value="true" <?php if($element != null && $element->mandatory){echo 'checked';}?>>
						Check if people should be warned by e-mail/signal if they have not filled in this field.
					</label><br>
					<br>

					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[recommended]" value="true" <?php if($element != null && $element->recommended){echo 'checked';}?>>
						Check if people should be notified on their homepage if they have not filled in this field.
					</label><br>
					<br>

					<div <?php if($element == null || (!$element->mandatory && !$element->recommended)){echo "class='hidden'";}?>>
						<?php
						if($element == null){
							$conditions	= '';
						}else{
							$conditions = maybe_unserialize($element->warning_conditions);
						}
						echo $this->warningConditionsForm('formfield[warning_conditions]', $conditions);
						?>
					</div>
					<?php
				}else{
					?>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[required]" value="true" <?php if($element != null && $element->required){echo 'checked';}?>>
						Check if this should be a required field
					</label><br>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[mandatory]" value="true" <?php if($element != null && $element->mandatory){echo 'checked';}?>>
						Check if this should be a conditional required field: its only required when visible
					</label><br>
					<br>
					<?php
				}
				?>
			</div>
			<br>
			<label class="option-label">
				<input type="checkbox" class="formbuilder" name="formfield[hidden]" value="true" <?php if($element != null && $element->hidden){echo 'checked';}?>>
				Check if this should be a hidden field
			</label><br>

			<?php
			if($element == null){
				$text	= "Add";
			}else{
				$text	= "Change";
			}
			echo SIM\addSaveButton('submit_form_element',"$text form element"); ?>
		</form>
		<?php

		return ob_get_clean();
	}

	/**
	 * Form to add conditions to an element
	 *
	 * A field can have one or more conditions applied to it like:
	*		1) hide when field X is Y
	*		2) Show when field X is Z
	*	Each condition can have multiple rules like:
	*		Hide when field X is Y and field A is B
	*
	*	The array structure is therefore:
	*		[
	*			[0][
	*				[rules]
	*						[0]
	*						[1]
	*				[action]
	*			[1]
	*				[rules]
	*						[0]
	*				[action]
	*		]
	*
	*	It is also stored at the conditional fields to be able to create efficient JavaScript
	 *
	 * @param int	$elementId	The id of the element. Default -1 for empty
	 */
	public function elementConditionsForm($elementId = -1){
		if($elementId != -1){
			$element	= $this->getElementById($elementId);
		}

		if($elementId == -1 || empty($element->conditions)){
			if(gettype($element) != 'object'){
				$element	= new stdClass();
			}

			$dummyFieldCondition['rules'][0]["conditional_field"]	= "";
			$dummyFieldCondition['rules'][0]["equation"]				= "";
			$dummyFieldCondition['rules'][0]["conditional_field_2"]	= "";
			$dummyFieldCondition['rules'][0]["equation_2"]			= "";
			$dummyFieldCondition['rules'][0]["conditional_value"]	= "";
			$dummyFieldCondition["action"]					= "";
			$dummyFieldCondition["target_field"]			= "";
			
			if($elementId == -1){
				$elementId			= 0;
			}
			$element->conditions = [$dummyFieldCondition];
		}
		
		$conditions = maybe_unserialize($element->conditions);
		
		ob_start();
		$counter = 0;
		foreach($this->formElements as $el){
			$copyTo	= (array)maybe_unserialize($el->conditions);
			if(!empty($copyTo['copyto']) && in_array($elementId, $copyTo['copyto'])){
				$counter++;
				?>
				<div class="form_element_wrapper" data-id="<?php echo $el->id;?>" data-formid="<?php echo $this->formData->id;?>">
					<button type="button" class="edit_form_element button" title="Jump to conditions element">View conditions of '<?php echo $el->name;?>'</button>
				</div>
				<?php
			}
		}
		if($counter>0){
			$jumbButtonHtml =  ob_get_clean();
			if($counter==1){
				$counter	= 'another element';
				$any 		= 'the';
			}else{
				$counter	= "$counter other elements";
				$any 		= 'any';
			}
			
			ob_start();
			?>
			<div>
				This element has some conditions defined by <?php echo $counter;?>.<br>
				Click on <?php echo $any;?> button below to view.
				<?php echo $jumbButtonHtml;?>
			</div><br><br>
			<?php
		}
		?>
		
		<form action='' method='post' name='add_form_element_conditions_form'>
			<h3>Form element conditions</h3>
			<input type='hidden' class='element_condition' name='formid' value='<?php echo $this->formData->id;?>'>
			
			<input type='hidden' class='element_condition' name='elementid' value='<?php echo $elementId;?>'>

			<?php
			// get the last numeric array key
			$lastCondtionKey = end(array_filter(array_keys($conditions), 'is_int'));
			foreach($conditions as $conditionIndex=>$condition){
				if(!is_numeric($conditionIndex)){
					continue;
				}
				?>
				<div class='condition_row' data-condition_index='<?php echo $conditionIndex;?>'>
					<span style='font-weight: 600;'>If</span>
					<br>
					<?php
					$lastRuleKey = array_key_last($condition['rules']);
					foreach($condition['rules'] as $ruleIndex=>$rule){
						?>
						<div class='rule_row' data-rule_index='<?php echo $ruleIndex;?>'>
							<input type='hidden' class='element_condition combinator' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][combinator]' value='<?php echo $rule['combinator']; ?>'>
						
							<select class='element_condition condition_select conditional_field' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_field]' required>
								<?php
									echo $this->inputDropdown($rule['conditional_field'], $elementId);
								?>
							</select>

							<select class='element_condition condition_select equation' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][equation]' required>
								<?php
									$optionArray	= [
										''			=> '---',
										'changed'	=> 'has changed',
										'clicked'	=> 'is clicked',
										'=='		=> 'equals',
										'!='		=> 'is not',
										'>'			=> 'greather than',
										'<'			=> 'smaller than',
										'checked'	=> 'is checked',
										'!checked'	=> 'is not checked',
										'== value'	=> 'equals the value of',
										'!= value'	=> 'does not equal the value of',
										'> value'	=> 'greather than the value of',
										'< value'	=> 'smaller than the value of',
										'-'			=> 'minus the value of',
										'+'			=> 'plus the value of',
										'visible'	=> 'is visible',
										'invisible'	=> 'is not visible',
									];

									foreach($optionArray as $option=>$optionLabel){
										if($rule['equation'] == $option){
											$selected	= 'selected="selected"';
										}else{
											$selected	= '';
										}
										echo "<option value='$option' $selected>$optionLabel</option>";
									}
								?>
							</select>

							<?php
							//show if -, + or value field is target value
							if($rule['equation'] == '-' || $rule['equation'] == '+' || str_contains($rule['equation'], 'value')){
								$hidden = '';
							}else{
								$hidden = 'hidden';
							}
							?>
							
							<span class='<?php echo $hidden;?> condition_form conditional_field_2'>
								<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_field_2]'>
								<?php
									echo $this->inputDropdown($rule['conditional_field_2'], $elementId);
								?>
								</select>
							</span>
							
							<?php
							if($rule['equation'] == '-' || $rule['equation'] == '+'){
								$hidden = '';
							}else{
								$hidden = 'hidden';
							}
							?>

							<span class='<?php echo $hidden;?> condition_form equation_2'>
								<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][equation_2]'>
									<?php
										$optionArray	= [
											''			=> '---',
											'=='		=> 'equals',
											'!='		=> 'is not',
											'>'			=> 'greather than',
											'<'			=> 'smaller than',
										];
										foreach($optionArray as $option=>$optionLabel){
											if($rule['equation_2'] == $option){
												$selected	= 'selected="selected"';
											}else{
												$selected	= '';
											}
											echo "<option value='$option' $selected>$optionLabel</option>";
										}
									?>
								</select>
							</span>
							<?php
							if(str_contains($rule['equation'], 'value') || in_array($rule['equation'], ['changed','checked','!checked', 'visible', 'invisible'])){
								$hidden = 'hidden';
							}else{
								$hidden = '';
							}
							?>
							<input  type='text'   class='<?php echo $hidden;?> element_condition condition_form' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_value]' value="<?php echo $rule['conditional_value'];?>">
							
							<button type='button' class='element_condition and_rule condition_form button <?php if(!empty($rule['combinator']) && $rule['combinator'] == 'AND'){echo 'active';}?>'	title='Add a new "AND" rule to this condition'>AND</button>
							<button type='button' class='element_condition or_rule condition_form button  <?php if(!empty($rule['combinator']) && $rule['combinator'] == 'OR'){echo 'active';}?>'	title='Add a new "OR"  rule to this condition'>OR</button>
							<button type='button' class='remove_condition condition_form button' title='Remove rule or condition'>-</button>
							<?php
							if($conditionIndex == $lastCondtionKey && $ruleIndex == $lastRuleKey){
								?>
								<button type='button' class='add_condition condition_form button' title='Add a new condition'>+</button>
								<button type='button' class='add_condition opposite condition_form button' title='Add a new condition, opposite to to the previous one'>Add opposite</button>
							<?php
							}
							?>
						</div>
						<?php
					}
					?>
					<br>
					<span style='font-weight: 600;'>then</span><br>
					
					<div class='action_row'>
						<div class='radio_wrapper condition_form'>
							<label>
								<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='show' <?php if($condition['action'] == 'show'){echo 'checked';}?> required>
								Show this element
							</label><br>
							
							<label>
								<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='hide' <?php if($condition['action'] == 'hide'){echo 'checked';}?> required>
								Hide this element
							</label><br>
							
							<label>
								<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='toggle' <?php if($condition['action'] == 'toggle'){echo 'checked';}?> required>
								Toggle this element
							</label><br>
							
							<label>
								<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='value' <?php if($condition['action'] == 'value'){echo 'checked';}?> required>
								Set property
							</label>
							<input type="text" list="propertylist" name="element_conditions[<?php echo $conditionIndex;?>][propertyname1]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname1'];?>">
							<label> to:</label>
							<input type="text" name="element_conditions[<?php echo $conditionIndex;?>][action_value]" class='element_condition' value="<?php echo $condition['action_value'];?>">
							<br>
							<label>
								<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='property' <?php if($condition['action'] == 'property'){echo 'checked';}?> required>
								Set the
							</label>
						
							<datalist id="propertylist">
								<option value="value">
								<option value="min">
								<option value="max">
							</datalist>
							<label>
								<input type="text" list="propertylist" name="element_conditions[<?php echo $conditionIndex;?>][propertyname]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname'];?>">
								property to the value of
							</label>
							
							<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][property_value]'>
								<?php echo $this->inputDropdown($condition['property_value'], $elementId);?>
							</select>

							<?php
							$type	= $this->getElementById($condition['property_value'], 'type');
							$hidden	= 'hidden';
							$hidden2= 'hidden';
							if(in_array($type, ['date', 'number', 'range', 'week', 'month']) ){
								$hidden	= '';

								if(in_array($type, ['date', 'week', 'month']) ){
									$hidden2	= '';
								}
							}
							?>
							<label class='addition <?php echo $hidden;?>'>
								+ <input type='number' name="element_conditions[<?php echo $conditionIndex;?>][addition]" class='element_condition' value="<?php echo $condition['addition'];?>" style='width: 60px;'>
								<span class='days <?php echo $hidden2;?>'> days</span>
							</label>
							<br>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			<br>
			<div class="copyfieldswrapper">
				<label>
					<input type='checkbox' class="showcopyfields" <?php if(!empty($conditions['copyto'])){echo 'checked';}?>>
					Apply visibility conditions to other fields
				</label><br><br>
				
				<div class='copyfields <?php if(empty($conditions['copyto'])){echo 'hidden';}?>'>
					Check the fields these conditions should apply to as well,<br>
					This holds only for visibility conditions (show, hide or toggle).<br><br>
					<?php
					foreach($this->formElements as $element){
						//do not show the current element itself or wrapped labels
						if($element->id != $elementId && empty($element->wrap)){
							if(!empty($conditions['copyto'][$element->id])){
								$checked = 'checked';
							}else{
								$checked = '';
							}

							$name	= ucfirst(str_replace('_', ' ', $element->name));
							if(str_contains($name, '[]')){
								$name	.= " ($element->id)";
							}
							
							echo "<label>";
								echo "<input type='checkbox' name='element_conditions[copyto][{$element->id}]' value='{$element->id}' $checked>";
								echo $name;
							echo "</label><br>";
						}
					}
					?>
				</div>
			</div>
			<?php
			echo SIM\addSaveButton('submit_form_condition','Save conditions'); ?>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Form to add warning conditions to an element
	 *
	 * @param	string	$name			The basename for the form conditions inputs.
	 * @param	int		$conditions		The existing conditions
	 */
	public function warningConditionsForm($name, $conditions = ''){
		if(empty($conditions) || !is_array($conditions)){
			$conditions	=[
				[
					"user_meta_key"	=> '',
					"equation"		=> ''
				]
			];
		}		
		
		$userMetaKeys	= get_user_meta($this->user->ID);
		ksort($userMetaKeys);

		ob_start();
		?>
			
		<label>Do not warn if usermeta with the key</label>
		<br>

		<div class="conditions_wrapper" style='width: 90vw;z-index: 9999;position: relative;'>
			<?php
			foreach($conditions as $conditionIndex=>$condition){
				if(!is_numeric($conditionIndex)){
					continue;
				}
				?>
				<div class='warning_conditions element_conditions' data-index='<?php echo $conditionIndex;?>'>
					<input type="hidden" class='warning_condition combinator' name="<?php echo $name;?>[<?php echo $conditionIndex;?>][combinator]" value="<?php echo $condition['combinator'];?>">

					<input type="text" class="warning_condition meta_key" name="<?php echo $name;?>[<?php echo $conditionIndex;?>][meta_key]" value="<?php echo $condition['meta_key'];?>" list="meta_key" style="width: fit-content;">
					<datalist id="meta_key">
						<?php
						foreach($userMetaKeys as $key=>$value){
							// Check if array, store array keys
							$value 	= maybe_unserialize($value[0]);
							$data	= '';
							if(is_array($value)){
								$keys	= implode(',', array_keys($value));
								$data	= "data-keys=$keys";
							}
							echo "<option value='$key' $data>";
						}

						?>
					</datalist>

					<?php
						$arrayKeys	= maybe_unserialize($userMetaKeys[$condition['meta_key']][0]);
					?>
					<span class="index_wrapper <?php if(!is_array($arrayKeys)){echo 'hidden';}?>">
						<span>and index</span>
						<input type="text" class="warning_condition meta_key_index" name='<?php echo $name;?>[<?php echo $conditionIndex;?>][meta_key_index]' value="<?php echo $condition['meta_key_index'];?>" list="meta_key_index[<?php echo $conditionIndex;?>]" style="width: fit-content;">
						<datalist class="meta_key_index_list warning_condition" id="meta_key_index[<?php echo $conditionIndex;?>]">
							<?php
							if(is_array($arrayKeys)){
								foreach(array_keys($arrayKeys) as $key){
									echo esc_html("<option value='$key'>");
								}
							}
							?>
						</datalist>
					</span>
					
					<select class="warning_condition inline" name='<?php echo $name;?>[<?php echo $conditionIndex;?>][equation]'>
						<?php
						$optionArray	= [
							''			=> '---',
							'=='		=> 'equals',
							'!='		=> 'is not',
							'>'			=> 'greather than',
							'<'			=> 'smaller than',
							'submitted'	=> 'has submitted',
						];
						foreach($optionArray as $option=>$optionLabel){
							if($condition['equation'] == $option){
								$selected	= 'selected=selected';
							}else{
								$selected	= '';
							}
							echo "<option value='$option' $selected>$optionLabel</option>";
						}
						?>
					</select>
					<input  type='text'   class='warning_condition' name='<?php echo $name;?>[<?php echo $conditionIndex;?>][conditional_value]' value="<?php echo $condition['conditional_value'];?>" style="width: fit-content;">
					
					<button type='button' class='warn_cond button <?php if(!empty($condition['combinator']) && $condition['combinator'] == 'and'){echo 'active';}?>'	title='Add a new "AND" rule' value="and">AND</button>
					<button type='button' class='warn_cond button  <?php if(!empty($condition['combinator']) && $condition['combinator'] == 'or'){echo 'active';}?>'	title='Add a new "OR"  rule' value="or">OR</button>
					<button type='button' class='remove_warn_cond  button' title='Remove rule'>-</button>

					<br>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function replaceLineEnds(&$value, $key){
		$value	= str_replace(["\n", "\r"], ['\n', '\r'], trim($value));
	}

	public function exportForm($formId){
		global $wpdb;

		$this->getForm($formId);

		$tableName		= str_replace($wpdb->prefix, '%PREFIX%', $this->tableName);
		$elTableName	= str_replace($wpdb->prefix, '%PREFIX%', $this->elTableName);

		$name			= esc_sql($this->formData->name);

		$query				= "SELECT * FROM {$this->tableName} WHERE id= '$formId'";
		$result				= $wpdb->get_results($query)[0];

		// Fix the settings
		$result->form_url		= "%FORMURL%";

		$result->emails	= maybe_unserialize($result->emails);
		if(!empty($result->emails)){
			array_walk_recursive($result->emails, [$this, "replaceLineEnds"]);
		}
		$result->emails	= maybe_serialize($result->emails);

		unset($result->id);
		
		$formKeys	= '(`'.implode('`, `', array_keys((array) $result)).'`)';
		$formValues	= "('".implode("', '", array_values((array) $result))."')";

		$content	= "INSERT INTO `$tableName` $formKeys VALUES $formValues\n";

		$elementKeys	= '(`'.implode('`, `', array_keys((array) $this->formElements[0])).'`)';
		foreach($this->formElements as $element){
			$query	= "INSERT INTO `$elTableName` $elementKeys VALUES (";
			
			$lastKey	= array_key_last((array) $element);
			foreach($element as $name=>$property){
				if($name == 'form_id'){
					$query	.= "%FORMID%";
				}elseif($property === null){
					$query	.= 'NULL';
				}elseif(is_numeric($property)){
					$query	.= $property;
				}else{
					$query	.= "'".esc_sql($property)."'";
				}

				if($name != $lastKey){
					$query	.= ',';
				}
			}
			$query	.= ");";

			$content	.= "$query\n";
		}

		$backupName = $this->formData->name.".sform";
		SIM\clearOutput();

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=$backupName");

        echo $content;
		exit;
	}

	public function importForm($path){
		if(!file_exists($path)){
			return new \WP_Error('forms', "$path does not exist");
		}
		global $wpdb;

		$formId				= -1;
		$elementIdMapping	= [];
		$pattern			= '/VALUES \((\d*),/i';
		$wpdb->show_errors (true);

		// Import the form and elements
		foreach(file($path) as $line) {
			$oldId	= -1;
			if(!empty($line)){
				$query	= str_replace(['%PREFIX%', '%SITEURL%', '%FORMID%'], [$wpdb->prefix, SITEURL, $formId], $line);
				
				if(!empty(trim($query))){
					// Find the old id
					if(preg_match($pattern, $query, $matches)){
						$oldId	= $matches[1];
						$query	= preg_replace($pattern, "VALUES ('',", $query);
					}

					$result	= $wpdb->query($query);

					if(!$result){
						SIM\printArray("query failed: ".$query."\n{$wpdb->last_error}");

						echo "<div class='error'>Import failed.<br>{$wpdb->last_error}</div>";
					}else{
						// First line of the form
						if($formId == -1){
							$formId						= $wpdb->insert_id;
						// Get the new element id
						}elseif($oldId	!= -1){
							$elementIdMapping[$oldId]	= $wpdb->insert_id;
						}
					}
				}
			}
		}

		// Load the new form
		$this->getForm($formId);

		if(!empty($this->formData->autoarchive_el)){
			$this->formData->autoarchive_el	= $elementIdMapping[$this->formData->autoarchive_el];

			$wpdb->update(
				$this->tableName,
				array(
					'autoarchive_el' 	=> $this->formData->autoarchive_el
				),
				array(
					'id'		=> $this->formData->id,
				),
			);
		}

		// Update old element ids with new ones
		foreach($this->formElements as $element){
			$update	= false;

			if(!empty($element->conditions)){
				$element->conditions	= unserialize($element->conditions);
				foreach($element->conditions as $key=>&$condition){
					if($key	=== 'copyto'){
						foreach($condition as $k=>$copyId){
							// add with new id
							$condition[$elementIdMapping[$k]]	= $elementIdMapping[$copyId];

							// remove the old id
							unset($condition[$k]);
						}
					}else{
						if(is_numeric($condition['property_value'])){
							$condition['property_value']	= $elementIdMapping[$condition['property_value']];
							$update							= true;
						}

						foreach($condition['rules'] as &$rule){
							if(is_numeric($rule['conditional_field'])){
								$rule['conditional_field']	= $elementIdMapping[$rule['conditional_field']];
								$update						= true;
							}

							if(is_numeric($rule['conditional_field_2'])){
								$rule['conditional_field_2']	= $elementIdMapping[$rule['conditional_field_2']];
								$update							= true;
							}

							if(is_numeric($rule['conditional_value'])){
								$rule['conditional_value']	= $elementIdMapping[$rule['conditional_value']];
								$update						= true;
							}
						}
					}
				}
				$element->conditions	= serialize($element->conditions);
			}

			if($update){
				$result = $wpdb->update(
					$this->elTableName,
					(array)$element,
					array(
						'id'		=> $element->id,
					),
				);

				if(!$result){
					SIM\printArray("query failed: ".$query."\n{$wpdb->last_error}");
				}
			}
		}

		// add a new page
		$formName	= ucfirst(str_replace('_', ' ', $this->formData->name));
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => "$formName form",
			'post_content'  => "[formbuilder formname={$this->formData->name}]",
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$url	= get_permalink(wp_insert_post( $post, true, false));

		// Update the form url
		$wpdb->update($this->tableName,
			array(
				'form_url' 	=> $this->formData->form_url
			),
			array(
				'id'		=> $this->formData->id,
			),
		);

		echo "<div class='success'>Import of the form '$formName' finished successfully.<br>Visit the created form <a href='$url' target='_blank'>here</a></div>";
	}
}
