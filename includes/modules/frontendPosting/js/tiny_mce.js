var dialog;
/*
	SELECT USER DATA
*/

let selectUserHtml = 
`<div class="wp-editor-help">
	${userSelect.html}
	
	<label>
		<input type="checkbox" id="insert_picture">
		Show user picture
	</label>
	<br>
	<label>
		<input type="checkbox" id="insert_phonenumbers">
		Show user phonenumbers
	</label>
	<br>
	<label>
		<input type="checkbox" id="insert_email">
		Show user e-mail
	</label>
</div>`;

let selectUserDialog = {
	width: 350,
	height: 160,
	title: 'Insert user data',
	items: {
		type: 'container',
		classes: 'wp-help',
		html: selectUserHtml
	},
	buttons: [{
		text: 'Insert link',
		onclick: function(){
			var userid = document.querySelector('.wp-editor-help [name="user_selection"]').value;
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
				tinymce.activeEditor.insertContent('[user_link id="'+userid+'"'+options+']');
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
					dialog 							= editor.windowManager.open(selectUserDialog);
					var select						= document.querySelector('.wp-editor-help [name="user_selection"]');
					select._niceselect 				= NiceSelect.bind(select, {searchable: true});
					var niceselect 					= select._niceselect.dropdown
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
		
		createControl:function(){
			return null
		},
	}
);

//Register the plugin
tinymce.PluginManager.add(
	'select_user',
	tinymce.plugins.select_user
)

console.log('Frontend posting tiny_mce.js loaded');