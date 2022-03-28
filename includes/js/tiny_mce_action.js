/*
	SELECT USER DATA
*/
function add_select_user_button(){
	select_user_html = '<div class="wp-editor-help">'+
		tinymce_data.user_select +
		
		'<input type="checkbox" id="insert_picture">'+
		'<label for="insert_picture"> Show user picture</label><br>'+
		
		'<input type="checkbox" id="insert_phonenumbers">'+
		'<label for="insert_phonenumbers"> Show user phonenumbers</label><br>'+
		
		'<input type="checkbox" id="insert_email">'+
		'<label for="insert_email"> Show user e-mail</label><br>'+
	'</div>';

	var select_user_dialog = {
		width: 350,
		height: 160,
		title: 'Insert user data',
		items: {
			type: 'container',
			classes: 'wp-help',
			html: select_user_html
		},
		buttons: [{
			text: 'Insert link',
			onclick: function(){
				userid = document.querySelector("#user_selection").value;
				if(userid != ''){
					var options = '';
					if(document.getElementById('insert_picture').checked){
						options += ' picture=true';
					}
					if(document.getElementById('insert_phonenumbers').checked){
						options += ' phone=true';
					}
					if(document.getElementById('insert_email').checked){
						options += ' email=true';
					}
					tinymce.activeEditor.insertContent('[missionary_link id="'+userid+'"'+options+']');
				}
				dialog.close();
			}
		},{
			text: 'Close',
			onclick: 'close'
		}]
	} ;

	//select user
	tinymce.create(
		//fileupload
		'tinymce.plugins.select_user',
		{
			init:function(editor, url){	
				editor.addCommand('mceSelect_user',
					function(){
						dialog = editor.windowManager.open(select_user_dialog);
						
						var options = {searchable: true};
						select=document.getElementById('user_selection');
						console.log(select);
						select._niceselect = NiceSelect.bind(select,options);
						
						var niceselect = document.querySelector('.mce-container-body .nice-select');
						niceselect.style.position		= 'relative';
						niceselect.style.width			= "200px";
						niceselect.style.border			= '2px solid #303030';
						niceselect.style.marginBottom	= '10px';
					}
				);
				
				editor.addButton('select_user',
					{
						tooltip: 'Insert userinfo like name or phone',
						title:'Insert userinfo',
						cmd:'mceSelect_user',
						image:url+'/../pictures/usericon.png'
					}
				);
			
			},
			
			createControl:function(n,a){
				return null
			},
		}
	);
	//Register the plugin
	tinymce.PluginManager.add(
		'select_user',
		tinymce.plugins.select_user
	)
}

/*
	ADD FORM
*/
function add_form_shortcode(){
	add_form_shortcode_html = '<div  class="wp-editor-help">'+
		'<label for="form_name">Give the formname in one word:</label><br>' +
		'<input type="text" id="form_name" name="form_name" style="width:300px;"><br>'+
		'<label for="form_name">Or add a table to display the data of an existing form:</label><br>' +
		tinymce_data.form_select + '<br>'+
	'</div>';

	var insert_form_shortcode_dialog = {
		width: 350,
		height: 160,
		title: 'Insert form',
		items: {
			type: 'container',
			classes: 'wp-help',
			html: add_form_shortcode_html
		},
		buttons: [{
			text: 'Insert shortcode',
			onclick: function(){
				form_name		= document.querySelector("#form_name").value;
				form_selector	= document.querySelector("[name='form_selector']").value;
				if(form_name != ''){
					// Create the form via AJAX
					var form_data = new FormData();
					form_data.append('action', 'add_form');
					form_data.append('form_name', form_name);

					fetch(sim.ajax_url, {
						method: 'POST',
						credentials: 'same-origin',
						body: form_data
					}).then(response => response.text())
					.then(response => {
						tinymce.activeEditor.insertContent('[formbuilder datatype='+form_name+']');
						alert("Form succesfully inserted.\n\n Please publish the page, then visit the new page to start building your form");
						//console.log(response)
					})
					.catch(err => console.log(err));
				}else if(form_selector != ''){
					tinymce.activeEditor.insertContent('[formresults datatype='+form_selector+']');
					
					alert("Table succesfully inserted.\n\n Please publish the page, then visit the new page to start building your form");
				}
				
				dialog.close();
			}
		},{
			text: 'Close',
			onclick: 'close'
		}]
	} ;

	//select user
	tinymce.create(
		//form shortcode
		'tinymce.plugins.insert_form_shortcode',
		{
			init:function(editor, url){	
				editor.addCommand('mceInsert_form_shortcode',
					function(){
						dialog = editor.windowManager.open(insert_form_shortcode_dialog);
					}
				);
				
				editor.addButton('insert_form_shortcode',
					{
						tooltip: 'Create a new form',
						title:'Insert a new form',
						cmd:'mceInsert_form_shortcode',
						image:url+'/../pictures/form-insert.png'
					}
				);
			
			},
			
			createControl:function(n,a){
				return null
			},
		}
	);
	//Register the plugin
	tinymce.PluginManager.add(
		'insert_form_shortcode',
		tinymce.plugins.insert_form_shortcode
	)
}

console.log('tiny_mce_action.js loaded');
add_select_user_button();
add_form_shortcode();