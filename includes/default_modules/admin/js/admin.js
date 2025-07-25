import {changeUrl, displayTab, showModal} from './../../../../includes/js/main.js'
import {fetchRestApi} from  './../../../../includes/js/form_submit_functions.js'
import {copyFormInput, fixNumbering, removeNode} from './../../../../../../sim-modules/forms/js/forms.js'
import { bind as NiceSelect } from '../../../js/node_modules/nice-select2';

console.log('admin.js loaded');

function switchSlider(event){
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
        if(select._niceselect  == undefined){
		    NiceSelect(select, {searchable: true});
        }
	});
});

window.addEventListener("click", async event => {
	let target = event.target;
        
    //add element
    if(target.matches('.add')){
        let newNode = copyFormInput(target.closest(".clone_div"));

        fixNumbering(target.closest('.clone_divs_wrapper'));

        //add tinymce's can only be done when node is inserted and id is unique
        newNode.querySelectorAll('.wp-editor-area').forEach(el =>{
            window.tinyMCE.execCommand('mceAddEditor',false, el.id);
        });

        target.remove();
    }
    
    //remove element
    if(target.matches('.remove')){
        //Remove node clicked
        removeNode(target);
    }

    if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
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

    if(target.matches(".tablink")){
		event.preventDefault();
		//change the url in browser
		changeUrl(target);

		//show the tab
		displayTab(target);
	}else if(target.matches(`.sim.release`)){
        showModal('release');

        let formData    = new FormData();
        formData.append('module_name', target.dataset.name);
        
        let response    = await fetchRestApi('get-changelog', formData);

        document.querySelector('#release_modal .loadergif_wrapper').classList.add('hidden');

        document.querySelector('#release_modal .content').innerHTML   = response;
        document.querySelector('#release_modal .content').classList.remove('hidden');
    }
});

document.querySelectorAll('#release_modal').forEach(el=>el.addEventListener('modalclosed', ev=>{
    ev.target.querySelector('.loadergif_wrapper').classList.remove('hidden');
    ev.target.querySelector('.content').classList.add('hidden');
}));