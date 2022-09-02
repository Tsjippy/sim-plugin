export let scripts;
export let callback;

export let afterScriptsLoaded	= function (){
    tinymce.remove();

    // Activate tinyMce's again
    document.querySelectorAll('.entry-content .wp-editor-area').forEach(el =>{
        window.tinyMCE.execCommand('mceAddEditor', false, el.id);
    });

    // invoke dom content loaded events
    window.document.dispatchEvent(new Event("DOMContentLoaded", {
        bubbles: true,
        cancelable: true
    }));

    // Somehow the visual/text switch does not work, manually fix it
    document.querySelectorAll('.wp-switch-editor').forEach(el=>el.addEventListener('click', ev=>{
        let area    = el.closest('.wp-editor-wrap').querySelector('.wp-editor-area');
        let panel   =  el.closest('.wp-editor-wrap').querySelector('.mce-panel');
        area.style.visibility='unset';
        if(ev.target.matches('.switch-tmce')){
           panel.style.display='block';
            area.style.display='none';
        }else{
            panel.style.display='none';
            area.style.display='block';
        }
    }));

    if(typeof callback == 'function'){
        callback();
    }
}

export let addStyles    = function (response){
    let temp = document.createElement('div');

    if(response.js){
        temp.innerHTML =  response.js;
        scripts	= [...temp.children];
        addScripts();
    }

    if(response.css){
        temp.innerHTML = response.css;
        [...temp.children].forEach(el => {
            document.head.appendChild(el);
        });
    }
}

export let addScripts	= function () {
    let el	= scripts.shift();

    // only add if needed
    if(document.getElementById(el.id) == null && el.tagName == 'SCRIPT'){
        var s = document.createElement('script');

        if(el.type == ''){
            s.type	= 'text/javascript'; 
        }else{
            s.type	= el.type;
        }

        if(el.src != ''){
            s.src 	= el.src;
        }else{
            s.innerHTML	= el.innerHTML;
        }
        
        s.id	= el.id;
        
        document.head.appendChild(s);

        if(scripts.length > 0){
            if(el.src != ''){
                s.addEventListener('load', addScripts);
            }else{
                addScripts();
            }
        }else{
            if(el.src != ''){
                s.addEventListener('load', afterScriptsLoaded);
            }else{
                afterScriptsLoaded();
            }			
        }
    }else if(scripts.length > 0){
        //run again when this script is loaded
        addScripts();
    }else{
        afterScriptsLoaded();
    }
}