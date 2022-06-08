/*
    ADD FORM
*/
addFormShortcodeHtml = 
`<div  class="wp-editor-help">
    <label>
        Give the formname in one word:<br>
        <input type="text" id="form_name" name="form_name" style="width:300px;">
    </label>
    <br>
    <br>
    <label>
        Or add a table to display<br>
        the data of an existing form:<br>
        ${form_select}
    </label>
    <br>
</div>`;

var insertFormShortcodeDialog = {
    width: 350,
    height: 160,
    title: 'Insert form',
    items: {
        type: 'container',
        classes: 'wp-help',
        html: addFormShortcodeHtml
    },
    buttons: [{
        text: 'Insert shortcode',
        onclick: function(){
            formName		= document.querySelector("#form_name").value;
            formSelector	= document.querySelector("[name='form_selector']").value;
            if(formName != ''){
                tinymce.activeEditor.insertContent(`[formbuilder formname=${formName}]`);
                Main.displayMessage("Form succesfully inserted.\n\n Please publish the page, then visit the new page to start building your form");
            }else if(form_selector != ''){
                tinymce.activeEditor.insertContent(`[formresults formname=${formSelector}`);
                
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
                    dialog = editor.windowManager.open(insertFormShortcodeDialog);
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

console.log('Form shortcode tiny_mce.js loaded');