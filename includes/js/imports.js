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

    //add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
        if(select._niceselect == undefined){
		    select._niceselect = NiceSelect.bind(select,{searchable: true});
        }
	});

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


export function displayMessage(message, icon, autoclose=false, no_ok=false, timer=1500){
	if(message == undefined){
		return;
	}
	
	if(typeof(Swal) != 'undefined'){
		var options = {
			icon: icon,
			title: message.trim(),
			confirmButtonColor: "#bd2919",
			cancelButtonColor: 'Crimson'
		};

		if(no_ok){
			options['showConfirmButton'] = false;
		}
		
		if(typeof(callback) == 'function'){
			options['didClose'] = () => callback();
		}
		
		if(autoclose){
			options['timer'] = timer;
		}
		
		Swal.fire(options);
	}else{
		alert(message.trim());
	}
}

export function showLoader(element, replace=true, message=''){
	if(element == null){
		return;
	}
	
	let wrapper	= document.createElement("DIV");
	wrapper.setAttribute('class','loaderwrapper');
	if(message != ''){
		wrapper.innerHTML	= message;
	}

	let loader	= document.createElement("IMG");
	loader.setAttribute('class','loadergif');
	loader.setAttribute("src", sim.loadingGif);
	loader.style["height"]= "30px";

	wrapper.insertAdjacentElement('beforeEnd', loader);
	if(replace){
		element.parentNode.replaceChild(wrapper, element);
	}else{
		element.parentNode.insertBefore(wrapper, element.nextSibling);
	}

	return wrapper;
}

//Check if on mobile
export function isMobileDevice() {
	return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
}
