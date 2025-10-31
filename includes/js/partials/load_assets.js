export let scripts;

export let afterScriptsLoaded	= function (attachTo){
    // load 
    if(typeof(tinymce) != 'undefined'){
        tinymce.remove();

        // Activate tinyMce's again
        document.querySelectorAll('.entry-content .wp-editor-area').forEach(el =>{
            window.tinyMCE.execCommand('mceAddEditor', false, el.id);
        });
    }    

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

    //add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
        Main.attachNiceSelect(select);
	});

    const ev = new Event('scriptsloaded');
	attachTo.dispatchEvent(ev);
}

export let addStyles    = function (response, attachTo){
    scripts     = [];
    let temp    = document.createElement('div');

    // parse inline scripts
    temp.innerHTML =  response.html;
    scripts = Array.prototype.slice.call(temp.getElementsByTagName('script'));

    if(response.js){
        temp.innerHTML =  response.js;
        scripts	= [...temp.children].concat(scripts);
        addScripts(attachTo);
    }

    if(response.css){
        temp.innerHTML = response.css;
        [...temp.children].forEach(el => {
            document.head.appendChild(el);
        });
    }
}

export let addScripts	= function (attachTo) {
    if(attachTo == undefined){
        return;
    }

    if(scripts.length == 0){
        afterScriptsLoaded(attachTo);
    }
    let el	= scripts.shift();

    if(el == undefined){
        afterScriptsLoaded(attachTo);
        return;
    }

    // only add if needed
    if(el.tagName == 'SCRIPT' && document.getElementById(el.id) == null){
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
        
        try{
            document.head.appendChild(s);
        }catch (error) {
            console.error(error);
        }

        if(scripts.length > 0){
            if(el.src != ''){
                s.addEventListener('load', addScripts.bind( null, attachTo ) );
            }else{
                addScripts(attachTo);
            }
        }else{
            if(el.src != ''){
                s.addEventListener('load', afterScriptsLoaded.bind( null, attachTo));
            }else{
                afterScriptsLoaded(attachTo);
            }			
        }
    }else if(scripts.length > 0){
        // get next script
        addScripts(attachTo);
    }else{
        afterScriptsLoaded(attachTo);
    }
}