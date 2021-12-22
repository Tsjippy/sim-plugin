function add_upload_button(){
	upload_file_html = '<div  class="wp-editor-help">'+
		'<label for="file_upload">Select a file:</label><br>' +
		'<input type="file" id="file_upload" name="file_upload"><br>'+
		'<div id="uploadbutton_div" style="display:none; margin-top: 10px;">' +
			'<div id="insertcontents_div" style="display:none;">' +
				'<input type="checkbox" id="insert_contents" checked>'+
				'<label for="insert_contents"> Insert contents into post</label><br>'+
			'</div>' +
			'<div style="width:100%; display: flex; margin-top:20px">'+
				'<button type="button" class="button upload-files" id="upload_file" style="color: white;">Upload</button>'+
				'<img id="loadinggif" class="loadergif" src="'+simnigeria.loading_gif+'" style="margin-top:-10px;display:none;">'+
			'</div>'+		
			'<br>'+
		'</div>'+
	'</div>';

	var dialogConfig = {
		width: 350,
		height: 140,
		title: 'Upload a file',
		items: {
			type: 'container',
			classes: 'wp-help',
			html: upload_file_html
		},
		buttons: {
			text: 'Close',
			onclick: 'close'
		}
	} ;

	//Create the plugin
	tinymce.create(
		//fileupload
		'tinymce.plugins.file_upload',
		{
			init:function(editor, url){	
				editor.addCommand('mceWpUploadFile',
					function(){
						dialog = editor.windowManager.open(dialogConfig);

						upload_button = dialog.getEl(0).querySelector('#upload_file');
						upload_button.addEventListener('click',upload);
						file_element	= dialog.getEl(0).querySelector('#file_upload');
						file_element.addEventListener('change',function(event){
							if(
								this.files[0].type.includes('officedocument.wordprocess') || 
								this.files[0].type == 'text/plain' ||
								this.files[0].type == 'application/msword'
							){
								document.getElementById('insertcontents_div').style.display = 'block';
							}
							document.getElementById('uploadbutton_div').style.display = 'block';
						});
					}
				);
				
				editor.addButton('file_upload',
					{
						tooltip: 'Insert a file and/or its contents',
						title:'Upload a file',
						cmd:'mceWpUploadFile',
						image:url+'/../pictures/file-upload.png'
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
		'file_upload',
		tinymce.plugins.file_upload
	)
}

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
		'<input type="text" id="form_name" name="form_name"><br>'+
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
					tinymce.activeEditor.insertContent('[formbuilder datatype='+form_name+']');
					
					alert("Form succesfully inserted.\n\n Please publish the page, then visit the new page to start building your form");
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

function upload(event){
	document.getElementById('loadinggif').style.display = 'block';
	
	//Create a formdata element
	var form_data = new FormData();
	
	//Add the ajax action name
	form_data.append('action', 'upload_files');

	// Read selected files
	var totalfiles = file_element.files.length;
	if (totalfiles > 0){
		//Add all the files to the formdata
		for (var index = 0; index < totalfiles; index++) {
		   form_data.append('files[]', file_element.files[index]);
		}
	}else{
		alert("Please select some files before hitting the uplad button!");
		return;
	}
	
	//AJAX request
	var request = new XMLHttpRequest();
	
	//Listen to the state changes
	request.onreadystatechange = function(){
		//If finished
		if(request.readyState == 4){
			//Success
			if (request.status >= 200 && request.status < 400) {
				upload_succes(request.responseText);
			//Error
			}else{
				console.log(request.responseText);
				document.getElementById("progress_percentage").textContent = "Upload failed!";
			}	
		}
	};
	
	//Listen to the upload status
	request.upload.addEventListener('progress', progress, false);
	
	request.open('POST', simnigeria.ajax_url, true);

	//Create a progressbar
	var progress_percentage = document.createElement('span');
	progress_percentage.id = "progress_percentage";
	progress_percentage.textContent="   0%";
	document.getElementById('loadinggif').parentNode.appendChild(progress_percentage);
	
	//Send AJAX request
	request.send(form_data);
}

function progress(e){
	if(e.lengthComputable){
		var max = e.total;
		var current = e.loaded;

		var Percentage = (current * 100)/max;
		Percentage = Math.round(Percentage*10)/10
		
		document.getElementById("progress_percentage").textContent = "   "+Percentage+"%";

		if(Percentage >= 100){
		   // process completed  
		}
	}  
}

function upload_succes(result){
	var imgurls = JSON.parse(result);
	
	//Add contents to editor
	if(document.getElementById("insert_contents").checked){
		read_file_contents(imgurls[0]['url']);
	}
	
	//Get all the file urls
	for(var index = 0; index < imgurls.length; index++) {
		var src = imgurls[index]['url'];
		var filename = src.split("/")[src.split("/").length-1];
		
		//Add link to editor
		tinymce.activeEditor.insertContent('<p><a href="'+simnigeria.base_url+'/'+src+'">'+filename+'</a><a class="button sim" href="'+simnigeria.base_url+'/'+src+'">Download</a></p>');
	}
	
	//Close the popup
	dialog.close();
}

//Retrieve file contents over AJAX
function read_file_contents(url){
	var request = new XMLHttpRequest();

	//Create a formdata element
	var action = new FormData();

	//Add the ajax action name
	action.append('action', 'get_docx_contents');

	action.append('url', simnigeria.base_url+'/'+url);

	//Listen to the state changes
	request.onreadystatechange = function(){
		//If finished
		if(request.readyState == 4){
			//Success
			if (request.status >= 200 && request.status < 400) {
				//console.log(request.responseText);
				
				//Add contents to editor
				tinymce.activeEditor.insertContent(request.responseText);
				
				//Put cursor at the end
				tinyMCE.activeEditor.selection.select(tinyMCE.activeEditor.getBody(), true);
				tinyMCE.activeEditor.selection.collapse(false);
			//Error
			}else{
				console.log(request.responseText);
			}	
		}
	};

	request.open('POST', simnigeria.ajax_url, false);

	request.send(action);
}

console.log('tiny_mce_action.js loaded');
add_upload_button();
add_select_user_button();
add_form_shortcode();