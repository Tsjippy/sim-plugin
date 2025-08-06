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

export function displayMessage(message, icon, autoclose=false, no_ok=false, timer=1500){
	if(message == undefined){
		return;
	}
	
	if(typeof(Swal) != 'undefined'){
		var options = {
			icon: icon,
			title: message.toString().trim(),
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

        if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}
		
		return Swal.fire(options);
	}else{
		return alert(message.trim());
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
        let el  = element.nextElementSibling;
        if(el == null){
            element.parentNode.insertAdjacentElement('beforeEnd', wrapper);
        }else{
            element.parentNode.insertBefore(wrapper, el);
        }
	}

	return wrapper;
}

//Check if on mobile
export function isMobileDevice() {
    let check = false;
    (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
    return check;
}