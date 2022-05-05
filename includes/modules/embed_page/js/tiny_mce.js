/*
    ADD FORM
*/
var pageEmbedHtml = 
`<div  class="wp-editor-help">
    <label>
        Select a page to embed
    </label>
    <br>
    ${page_select}
    <br>
</div>`;

var pageEmbedDialog = {
    width: 350,
    height: 160,
    title: 'Embed another page',
    items: {
        type: 'container',
        classes: 'wp-help',
        html: pageEmbedHtml
    },
    buttons: [{
        text: 'Embed another page',
        onclick: function(){
            // Get page id
            pageId	= document.querySelector("[name='page-selector']").value;

            // Insert the shortcode at the end
            tinymce.activeEditor.setContent(tinymce.activeEditor.getContent()+`<br>[embed_page id=${pageId}]`);
            
            dialog.close();
        }
    },{
        text: 'Close',
        onclick: 'close'
    }]
} ;

//select page
tinymce.create(
    'tinymce.plugins.insert_embed_shortcode',
    {
        init:function(editor, url){
            console.log(url+'/../pictures/embed.png');
            editor.addCommand('mceInsert_embed_shortcode',
                function(){
                    dialog = editor.windowManager.open(pageEmbedDialog);
                    select							= document.querySelector('.wp-editor-help [name="page-selector"]');
					select._niceselect 				= NiceSelect.bind(select, {searchable: true});
					var niceselect 					= select._niceselect.dropdown
					niceselect.style.position		= 'relative';
					niceselect.style.width			= "200px";
					niceselect.style.border			= '2px solid #303030';
					niceselect.style.marginBottom	= '10px';
                }
            );
            
            editor.addButton('insert_embed_shortcode',
                {
                    tooltip: 'Embed another page in this page',
                    title:'Embed another page',
                    cmd:'mceInsert_embed_shortcode',
                    image:url+'/../pictures/embed.png'
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
    'insert_embed_shortcode',
    tinymce.plugins.insert_embed_shortcode
)

console.log('Page embed tiny_mce.js loaded');