import {changeUrl, displayTab} from './../../../../includes/js/partials/tabs.js'
import {showModal} from './../../../../includes/js/partials/modals.js'
import {fetchRestApi} from  './../../../../includes/js/partials/form_submit_functions.js'
import { showLoader } from  './../../../../includes/js/partials/show_loader.js';
import {copyFormInput, fixNumbering, removeNode} from './../../../../../../sim-modules/forms/js/form_exports.js'
import { bind as NiceSelect } from '../../../js/node_modules/nice-select2';

console.log('admin.js loaded');

function switchSlider(event){
    event.stopImmediatePropagation();

    if(event.target.checked){
        document.querySelectorAll('.options, .tablink-wrapper').forEach(el=>el.style.display    = 'block');
    }else{
        document.querySelectorAll('.options, .tablink-wrapper').forEach(el=>el.style.display    = 'none');
    }
}

//Load after page load
document.addEventListener("DOMContentLoaded", function() {
	document.querySelectorAll('[name="enable"]').forEach(el=>el.addEventListener('change', switchSlider));

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
        if(select._niceSelect  == undefined){
		    NiceSelect(select, {searchable: true});
        }
	});

    // Display loaders
    document.querySelectorAll(`.loader-image-trigger`).forEach( el =>{
        let size	= el.dataset.size ? el.dataset.size : 50 ;
        let text	= el.dataset.text ? el.dataset.text : '';

        showLoader(el, true, size, text);
    });
});

window.addEventListener("click", async event => {
	let target  = event.target;    
        
    //add element
    if(target.matches('.add')){
        let newNode = copyFormInput(target.closest(".clone-div"));

        fixNumbering(target.closest('.clone-divs-wrapper'));

        //add tinymce's can only be done when node is inserted and id is unique
        newNode.querySelectorAll('.wp-editor-area').forEach(el =>{
            window.tinyMCE.execCommand('mceAddEditor',false, el.id);
        });

        target.remove();
    }
    
    //remove element
    else if(target.matches('.remove')){
        //Remove node clicked
        removeNode(target);
    }

    else if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
        event.preventDefault();

        let value = '';
        if(target.classList.contains('placeholders')){
            value = target.textContent;
        }else if(target.value != ''){
            value = target.value;
            target.selectedIndex = '0';
        }
        
        if(value != ''){
            let options = {
                icon: 'success',
                title: 'Copied '+value,
                showConfirmButton: false,
                timer: 1500
            };

            if(document.fullscreenElement != null){
                options['target']	= document.fullscreenElement;
            }

            Swal.fire(options);
            navigator.clipboard.writeText(value);
        }
    }

    else if(target.matches(".tablink")){
		//change the url in browser
		changeUrl(target);

		//show the tab
		displayTab(target);
	}else if(target.matches(`.sim.release`)){
        showModal('release');

        let formData    = new FormData();
        formData.append('module-name', target.dataset.name);
        
        let response    = await fetchRestApi('get-changelog', formData);

        document.querySelector('#release-modal .content').innerHTML   = response;
        document.querySelector('#release-modal .content').classList.remove('hidden');
    }else{
        return;
    }

    event.stopImmediatePropagation();
});

document.querySelectorAll('#release-modal').forEach(el => el.addEventListener('modalclosed', ev => {
    ev.target.querySelector('.loader-wrapper').classList.remove('hidden');
    ev.target.querySelector('.content').classList.add('hidden');
}));