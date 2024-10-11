import {changeUrl, displayTab} from './../../../../js/main.js'

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
	document.querySelector('[name="enable"]').addEventListener('change', switchSlider);

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
        if(select._niceselect  == undefined){
		    select._niceselect = NiceSelect.bind(select,{searchable: true});
        }
	});
});

window.addEventListener("click", event => {
	let target = event.target;
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
	}
});