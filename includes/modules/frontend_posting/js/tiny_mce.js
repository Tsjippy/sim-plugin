/*
	SELECT USER DATA
*/

select_user_html = 
`<div class="wp-editor-help">
	${user_select}
	
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
			userid = document.querySelector('.wp-editor-help [name="user_selection"]').value;
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
					dialog 							= editor.windowManager.open(select_user_dialog);
					select							= document.querySelector('.wp-editor-help [name="user_selection"]');
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

console.log('tiny_mce.js loaded');