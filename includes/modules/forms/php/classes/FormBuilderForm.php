<?php
namespace SIM\FORMS;
use SIM;

class FormBuilderForm extends SimForms{
	use ElementHtml;

	function __construct($atts=[]){
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
	 * @return	string				The dropdown html
	 */
	function inputDropdown($selectedId, $elementId=''){
		if($selectedId == ''){
			$html = "<option value='' selected>---</option>";
		}else{
			$html = "<option value=''>---</option>";
		}
		
		foreach($this->formElements as $element){
			//do not include the element itself do not include non-input types
			if($element->id != $elementId && !in_array($element->type, ['label','info','datalist','formstep'])){
				$name = ucfirst(str_replace('_',' ',$element->name));
				
				//Check which option is the selected one
				if(!empty($selectedId) && $selectedId == $element->id){
					$selected = 'selected';
				}else{
					$selected = '';
				}
				$html .= "<option value='{$element->id}' $selected>$name</option>";
			}
		}
		
		return $html;
	}

	/**
	 * Build all html for a particular elemnt including edit controls.
	 * 
	 * @param	object	$element		The element
	 * @param	int		$key			The key in case of a multi element. Default 0
	 * 
	 * @return	string					The html
	 */
	function buildHtml($element, $key=0){
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
		$elementHtml = $this->getElementHtml($element, $width);
		
		//Check if element needs to be hidden
		if(!empty($element->hidden)){
			$hidden = ' hidden';
		}else{
			$hidden = '';
		}
		
		//if the current element is required or this is a label and the next element is required
		if(
			!empty($element->required)		||
			$element->type == 'label'		&&
			$this->nextElement->required
		){
			$hidden .= ' required';
		}
		
		//Add form edit controls if needed
		$html = " <div class='form_element_wrapper' data-id='{$element->id}' data-formid='{$this->formData->id}' data-priority='{$element->priority}' style='display: flex;'>";
			$html .= "<span class='movecontrol formfieldbutton' aria-hidden='true'>:::</span>";
			$html .= "<div class='resizer_wrapper'>";
				if($element->type == 'info'){
					$html .= "<div class='show inputwrapper$hidden'>";
				}else{
					$html .= "<div class='resizer show inputwrapper$hidden' data-widthpercentage='$width' style='width:$width%;'>";
				}
				
				if($element->type == 'formstep'){
					$html .= ' ***formstep element***</div>';
				}elseif($element->type == 'datalist'){
					$html .= ' ***datalist element***';
				}elseif($element->type == 'multi_start'){
					$html .= ' ***multi answer start***';
					$elementHtml	= '';
				}elseif($element->type == 'multi_end'){
					$html .= ' ***multi answer end***';
					$elementHtml	= '';
				}
				
				$html .= $elementHtml;
					//Add a star if this field has conditions
					if(!empty($element->conditions)){
						$html .= "<div class='infobox'>";
							$html .= "<span class='conditions_info formfieldbutton'>*</span>";
							$html .= "<span class='info_text conditions' style='margin:-20px 10px;'>This element has conditions</span>";
						$html .= "</div>";
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
	function showForm(){		
		// We cannot use the minified version as the dynamic js files depend on the function names
		wp_enqueue_script('sim_forms_script');

		ob_start();

		if(isset($_POST['export-form']) && is_numeric($_POST['export-form'])){
			$this->exportForm($_POST['export-form']);
		}

		if(isset($_POST['delete-form']) && is_numeric($_POST['delete-form'])){
			$saveFormSettings	= new SaveFormSettings();
			$saveFormSettings->deleteForm($_POST['delete-form'], $_POST['page-id']);

			return "<div class='success'>Form successfully deleted.</div>";
		}

		//Formbuilder js
		wp_enqueue_script( 'sim_formbuilderjs');
		
		?>
		<div class="sim_form wrapper">
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
						<button name='editform' class='button' data-action='hide' style='padding-top:0px;padding-bottom:0px;'>Hide form edit controls</button>
						<a href="." class="button sim">Show enduser form</a>
					</div>
					<?php
				}
			
				?>
				<form action='' method='post' class='sim_form builder'>
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
	function addElementModal(){
		?>
		<div class="modal add_form_element_modal hidden">
			<!-- Modal content -->
			<div class="modal-content" style='max-width:90%;'>
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
	function formSettingsForm(){
		global $wp_roles;
		
		$settings = $this->formData->settings;
		
		//Get all available roles
		$userRoles = $wp_roles->role_names;
		
		//Sort the roles
		asort($userRoles);
		
		?>
		<div class="element_settings_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="block">
						<h4>Submit button text</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[buttontext]' value="<?php echo $settings['buttontext']?>">
					</label>
					
					<label class="block">
						<h4>Succes message</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[succesmessage]' value="<?php echo $settings['succesmessage']?>">
					</label>
					
					<label class="block">
						<h4>Form name</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[formname]' value="<?php echo $settings['formname']?>">
					</label>
					<br>
					
					<label class='block'>
						<?php
						if(!empty($settings['save_in_meta'])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='settings[save_in_meta]' value='save_in_meta' <?php echo $checked;?>>
						Save submissiondata in usermeta table
					</label>
					<br>

					<label class="block">
						<h4>Form url</h4>
						<?php
						if(!empty($settings['formurl'])){
							$url	= $settings['formurl'];
						}else{
							$url	= str_replace(['?formbuilder=yes', '&formbuilder=yes'], '', SIM\currentUrl());
						}

						?>
						<input type='url' class='formbuilder formfieldsetting' name='settings[formurl]' value="<?php echo $url?>">
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
						<input type='text' class='formbuilder formfieldsetting' name='settings[upload_path]' value='<?php echo $settings['upload_path'];?>'>
					</label>
					<br>
					
					<label>
						<?php
						if(!empty($settings['formreset'])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='settings[formreset]' value='formreset' <?php echo $checked;?>>
						Reset form after succesfull submission
					</label>
					<br>
						
					<h4>Select available actions for formdata</h4>
					<?php
					
					$actions = ['archive','delete'];
					foreach($actions as $action){
						if(!empty($settings['actions'][$action])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<label class='option-label'>
							<input type='checkbox' class='formbuilder formfieldsetting' name='settings[actions][<?php echo $action;?>]' value='<?php echo $action;?>' <?php echo $checked;?>>
							<?php echo ucfirst($action);?>
						</label><br>
						<?php
					}
					?>
					
					<h4>Select a field with multiple answers where you want to create seperate rows for</h4>
					<select name="settings[split]">
					<?php
					if($settings['split'] == ''){
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
								if($settings['split'] == $value){
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
					<div class="formsettings_wrapper">
						<label class="block">
							<h4>Select if you want to auto archive results</h4>
							<br>
							<?php
							if($settings['autoarchive'] == 'true'){
								$checked1	= 'checked';
								$checked2	= '';
							}else{
								$checked1	= '';
								$checked2	= 'checked';
							}
							?>
							<label>
								<input type="radio" name="settings[autoarchive]" value="true" <?php echo $checked1;?>>
								Yes
							</label>
							<label>
								<input type="radio" name="settings[autoarchive]" value="false" <?php echo $checked2;?>>
								No
							</label>
						</label>
						<br>
						<div class='autoarchivelogic <?php if(empty($checked1)){echo 'hidden';}?>' style="display: flex;width: 100%;">
							Auto archive a (sub) entry when field
							<select name="settings[autoarchivefield]" style="margin-right:10px;">
							<?php
							if($settings['autoarchivefield'] == ''){
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
								if(!empty($settings['autoarchivefield']) && $settings['autoarchivefield'] == $element->id){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='{$element->id}' $selected>$name</option>";
							}
							
							?>
							</select>
							<label style="margin:0 10px;">equals</label>
							<input type='text' name="settings[autoarchivevalue]" value="<?php echo $settings['autoarchivevalue'];?>">
							<div class="infobox" name="info" style="min-width: fit-content;">
								<div style="float:right">
									<p class="info_icon">
										<img draggable="false" role="img" class="emoji" alt="???" src="<?php echo PICTURESURL;?>/info.png">
									</p>
								</div>
								<span class="info_text">
									You can use placeholders like '%today%+3days' for a value
								</span>
							</div>
						</div>
					</div>
					
					<h4>Select roles with form edit rights</h4>
					<div class="role_info">
					<?php
					foreach($userRoles as $key=>$roleName){
						if(!empty($settings['full_right_roles'][$key])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						echo "<label class='option-label'>";
							echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[full_right_roles][$key]' value='$roleName' $checked>";
							echo $roleName;
						echo"</label><br>";
					}
					?>
					</div>
					<br>

					<div class='submit_others_form_wrapper<?php if(empty($settings['save_in_meta'])){echo 'hidden';}?>'>
						<h4>Select who can submit this form on behalf of someone else</h4>
						<?php
						foreach($userRoles as $key=>$roleName){
							if(!empty($settings['submit_others_form'][$key])){
								$checked = 'checked';
							}else{
								$checked = '';
							}
							echo "<label class='option-label'>";
								echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[submit_others_form][$key]' value='$roleName' $checked>";
								echo $roleName;
							echo"</label><br>";
						}
						?>
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
	function formEmailsForm(){
		$emails = $this->formData->emails;
		?>
		<div class="emails_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="formfield formfieldlabel">
						Define any e-mails you want to send.<br>
						You can use placeholders in your inputs.<br>
						These default ones are available:<br><br>
					</label>
					<span class='placeholders' title="Click to copy">%id%</span>
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
						if(!in_array($element->type, ['label','info','button','datalist','formstep'])){
							echo "<option>%{$element->name}%</option>";
						}
					}
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
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='submitted' <?php if(empty($email['emailtrigger']) || $email['emailtrigger'] == 'submitted'){echo 'checked';}?>>
											The form is submitted
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='fieldchanged' <?php if($email['emailtrigger'] == 'fieldchanged'){echo 'checked';}?>>
											A field has changed to a value
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='disabled' <?php if($email['emailtrigger'] == 'disabled'){echo 'checked';}?>>
											Do not send this e-mail
										</label><br>
									</div>
									
									<div class='emailfieldcondition <?php if($email['emailtrigger'] != 'fieldchanged'){echo 'hidden';}?>'>
										<label class="formfield formfieldlabel">Field</label>
										<select name='emails[<?php echo $key;?>][conditionalfield]'>
											<?php
											echo $this->inputDropdown($email['conditionalfield']);
											?>
										</select>
										
										<label class="formfield formfieldlabel">
											Value
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalvalue]' value="<?php echo $email['conditionalvalue']; ?>">
										</label>
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
														'email'		=> '']
												];
											}
											foreach(array_values($email['conditionalfromemail']) as $fromKey=>$fromEmail){
											?>
											<div class='clone_div' data-divid='<?php echo $fromKey;?>'>
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $fromKey+1;?>
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
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $toKey+1;?>
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
	function elementBuilderForm($element=null){
		ob_start();

		if(is_numeric($element)){
			$element = $this->getElementById($element);
		}
		?>
		<form action="" method="post" name="add_form_element_form" class="form_element_form sim_form" data-addempty=true>
			<div style="display: none;" class="error"></div>
			<h4>Please fill in the form to add a new form element</h4><br>

			<input type="hidden" name="formid" value="<?php echo $this->formData->id;?>">

			<input type="hidden" name="formfield[form_id]" value="<?php echo $this->formData->id;?>">
			
			<input type="hidden" name="element_id" value="<?php if( $element != null){echo $element->id;}?>">
			
			<input type="hidden" name="insertafter">
			
			<input type="hidden" name="formfield[width]" value="100">
			
			<label>Select which kind of form element you want to add</label>
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
							$selected = 'selected';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
					
				</optgroup>
				<optgroup label="Special elements">
					<?php
					$options=[
						"captcha"		=> "Captcha",
						"php"			=> "Custom code",
						"datalist"		=> "Datalist",
						"info"			=> "Infobox",
						"multi_start"	=> "Multi-answer - start",
						"multi_end"		=> "Multi-answer - end",
						"formstep"		=> "Multistep",
						"p"				=> "Paragraph"
					];

					foreach($options as $key=>$option){
						if($element != null && $element->type == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
				</optgroup>
			</select>
			<br>
			
			<div name='elementname' class='labelhide hide'>
				<label>
					<div>Specify a name for the element</div>
					<input type="text" class="formbuilder" name="formfield[name]" value="<?php if($element != null){echo $element->name;}?>">
				</label>
				<br><br>
			</div>
			
			<div name='functionname' class='hidden'>
				<label>
					Specify the functionname
					<input type="text" class="formbuilder" name="formfield[functionname]" value="<?php if($element != null){echo $element->functionname;}?>">
				</label>
				<br><br>
			</div>
			
			<div name='labeltext' class='hidden'>
				<label>
					<div>Specify the <span class='elementtype'>label</span> text</div>
					<input type="text" class="formbuilder" name="formfield[text]" value="<?php if($element != null){echo $element->text;}?>">
				</label>
				<br><br>
			</div>

			<div name='upload-options' class='hidden'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[library]" value="true" <?php if($element != null && $element->library){echo 'checked';}?>>
					Add the <span class='filetype'>file</span> to the library
				</label>
				<br><br>

				<label>
					Name of the folder the <span class='filetype'>file</span> should be uploaded to.<br>
					<input type="text" class="formbuilder" name="formfield[foldername]" value="<?php if($element != null){echo $element->foldername;}?>">
				</label>
			</div>
			
			<div name='wrap'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[wrap]" value="true" <?php if($element != null && $element->wrap){echo 'checked';}?>>
					Group together with next element
				</label>
				<br><br>
			</div>
			
			<div name='infotext' class='hidden'>
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
			
			<div name='multiple' class='labelhide hide'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[multiple]" value="true" <?php if($element != null && $element->multiple){echo 'checked';}?>>
					Allow multiple answers
				</label>
				<br>
				<br>
			</div>
			
			<div name='valuelist' class='hidden'>
				<label>
					Specify the values, one per line
					<textarea class="formbuilder" name="formfield[valuelist]"><?php if($element != null){echo trim($element->valuelist);}?></textarea>
				</label>
				<br>
			</div>

			<div name='select_options' class='hidden'>
				<label class='block'>Specify an options group if desired</label>
				<select class="formbuilder" name="formfield[default_array_value]">
					<option value="">---</option>
					<?php
					$this->buildDefaultsArray();
					foreach($this->defaultArrayValues as $key=>$field){
						if($element != null && $element->default_array_value == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						$optionName	= ucfirst(str_replace('_', ' ', $key));
						echo "<option value='$key' $selected>$optionName</option>";
					}
					?>
				</select>
			</div>

			<div name='defaults' class='labelhide hide'>
				<label class='block'>Specify a default value if desired</label>
				<select class="formbuilder" name="formfield[default_value]">
					<option value="">---</option>
					<?php
					foreach($this->defaultValues as $key=>$field){
						if($element != null && $element->default_value == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						$optionName	= ucfirst(str_replace('_',' ',$key));
						echo "<option value='$key' $selected>$optionName</option>";
					}
					?>
				</select>
			</div>
			<br>
			<div name='elementoptions' class='labelhide hide'>
				<label>
					Specify any options like styling
					<textarea class="formbuilder" name="formfield[options]"><?php if($element != null){echo trim($element->options);}?></textarea>
				</label><br>
				<br>
				
				<?php
				$meta	= false;
				if(!empty($this->formData->settings['save_in_meta'])){
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
							$elementId	= -1;
						}else{
							$elementId	= $element->id;
						}
						echo $this->warningConditionsForm($elementId);
						?>
					</div>
					<?php
				}else{
					?>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[required]" value="true" <?php if($element != null && $element->required){echo 'checked';}?>>
						Check if this should be a required field
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
	function elementConditionsForm($elementId = -1){
		$element	= $this->getElementById($elementId);

		if($elementId == -1 || empty($element->conditions)){
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
			if(in_array($elementId, (array)unserialize($el->conditions)['copyto'])){
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
			$lastCondtionKey = array_key_last($conditions);
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
									'!= value'	=> 'is not the value of',
									'> value'	=> 'greather than the value of',
									'< value'	=> 'smaller than the value of',
									'-'			=> 'minus the value of',
									'+'			=> 'plus the value of',
								];

								foreach($optionArray as $option=>$optionLabel){
									if($rule['equation'] == $option){
										$selected	= 'selected';
									}else{
										$selected	= '';
									}
									echo "<option value='$option' $selected>$optionLabel</option>";
								}
							?>							
						</select>

						<?php
						//show if -, + or value field istarget value
						if($rule['equation'] == '-' || $rule['equation'] == '+' || strpos($rule['equation'], 'value') !== false){
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
											$selected	= 'selected';
										}else{
											$selected	= '';
										}
										echo "<option value='$option' $selected>$optionLabel</option>";
									}
								?>
							</select>
						</span>
						<?php 
						if(strpos($rule['equation'], 'value') !== false || in_array($rule['equation'], ['changed','checked','!checked'])){
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
						<input type="text" name="element_conditions[<?php echo $conditionIndex;?>][propertyname1]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname1'];?>">
						<label> to:</label>
						<input type="text" name="element_conditions[<?php echo $conditionIndex;?>][action_value]" class='element_condition' value="<?php echo $condition['action_value'];?>">
						<br>
						
						<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='property' <?php if($condition['action'] == 'property'){echo 'checked';}?> required>
						<label for='property'>Set the</label>
						
						<input type="text" list="propertylist" name="element_conditions[<?php echo $conditionIndex;?>][propertyname]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname'];?>">
						<datalist id="propertylist">
							<option value="value">
							<option value="min">
							<option value="max">
						</datalist>
						<label for='property'> property to the value of </label>
						
						<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][property_value]'>
						<?php echo $this->inputDropdown($condition['property_value'], $elementId);?>
						</select><br>
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
							
							echo "<label>";
								echo "<input type='checkbox' name='element_conditions[copyto][{$element->id}]' value='{$element->id}' $checked>";
								echo ucfirst(str_replace('_',' ',$element->name));
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
	 * @param	int		$elementId		The id to add warning conditions for. Default -1 for empty
	 */
	function warningConditionsForm($elementId = -1){
		$element	= $this->getElementById($elementId);

		if($elementId == -1 || empty($element->warning_conditions)){
			$dummy[0]["user_meta_key"]	= "";
			$dummy[0]["equation"]		= "";
			
			if($elementId == -1){
				$elementId			= 0;
			}
			$element->warning_conditions = $dummy;
		}
		
		$conditions 	= maybe_unserialize($element->warning_conditions);
		
		$userMetaKeys	= get_user_meta($this->user->ID);
		ksort($userMetaKeys);

		ob_start();
		?>
			
		<label>Do not warn if usermeta with the key</label>
		<br>

		<div class="conditions_wrapper">
			<?php
			foreach($conditions as $conditionIndex=>$condition){
				if(!is_numeric($conditionIndex)){
					continue;
				}
				?>
				<div class='warning_conditions element_conditions' data-index='<?php echo $conditionIndex;?>'>
					<input type="hidden" class='warning_condition combinator' name="formfield[warning_conditions][<?php echo $conditionIndex;?>][combinator]" value="<?php echo $condition['combinator'];?>">

					<input type="text" class="warning_condition meta_key" name="formfield[warning_conditions][<?php echo $conditionIndex;?>][meta_key]" value="<?php echo $condition['meta_key'];?>" list="meta_key" style="width: fit-content;">
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
							echo esc_html("<option value='$key' $data>");
						}

						?>
					</datalist>

					<?php
						$arrayKeys	= maybe_unserialize($userMetaKeys[$condition['meta_key']][0]);
					?>
					<span class="index_wrapper <?php if(!is_array($arrayKeys)){echo 'hidden';}?>">
						<span>and index</span>
						<input type="text" class="warning_condition meta_key_index" name='formfield[warning_conditions][<?php echo $conditionIndex;?>][meta_key_index]' value="<?php echo $condition['meta_key_index'];?>" list="meta_key_index[<?php echo $conditionIndex;?>]" style="width: fit-content;">
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
					
					<select class="warning_condition" name='formfield[warning_conditions][<?php echo $conditionIndex;?>][equation]'>
						<?php
						$optionArray	= [
							''			=> '---',
							'=='		=> 'equals',
							'!='		=> 'is not',
							'>'			=> 'greather than',
							'<'			=> 'smaller than',
						];
						foreach($optionArray as $option=>$optionLabel){
							if($condition['equation'] == $option){
								$selected	= 'selected';
							}else{
								$selected	= '';
							}
							echo "<option value='$option' $selected>$optionLabel</option>";
						}
						?>
					</select>
					<input  type='text'   class='warning_condition' name='formfield[warning_conditions][<?php echo $conditionIndex;?>][conditional_value]' value="<?php echo $condition['conditional_value'];?>" style="width: fit-content;">
					
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

	function exportForm($formId){
		global $wpdb;

		$this->getForm($formId);

		$tableName		= str_replace($wpdb->prefix, '%PREFIX%', $this->tableName);
		$elTableName	= str_replace($wpdb->prefix, '%PREFIX%', $this->elTableName);

		$name			= esc_sql($this->formData->name);

		$query				= "SELECT * FROM {$this->tableName} WHERE id= '$formId'";
		$result				= $wpdb->get_results($query)[0];

		// Fix the settings
		$settings						= $this->formData->settings;
		SIM\cleanUpNestedArray($settings, true);

		if(is_array($settings)){
			if(!empty($settings['formurl'])){
				$result->settings			= str_replace($settings['formurl'], "%FORMURL%", $result->settings );
			}

			$settings						= $result->settings;
		}else{
			$settings	= '';
		}

		$emails	= $result->emails;
		$emails	= str_replace(["\n", "\r"], ['\n', '\r'], $emails); 

		$content	= "INSERT INTO `$tableName` VALUES ('','$name',{$this->formData->version},'$settings','$emails')\n";

		foreach($this->formElements as $element){
			$query	= "INSERT INTO `$elTableName` VALUES (";
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

				if($name != 'warning_conditions'){
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

	function importForm($path){
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

		if(!empty($this->formData->settings['autoarchivefield'])){
			$this->formData->settings['autoarchivefield']	= $elementIdMapping[$this->formData->settings['autoarchivefield']];

			$wpdb->update(
				$this->tableName, 
				array(
					'settings' 	=> maybe_serialize($this->formData->settings)
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
		$this->formData->settings['formurl']	= $url;

		$wpdb->update($this->tableName, 
			array(
				'settings' 	=> maybe_serialize($this->formData->settings)
			), 
			array(
				'id'		=> $this->formData->id,
			),
		);

		echo "<div class='success'>Import of the form '$formName' finished successfully.<br>Visit the created form <a href='$url' target='_blank'>here</a></div>";
	}
}