import {close_mobile_menu} from './shared.js';

//Logout user
document.addEventListener("DOMContentLoaded",function() {
	console.log("logout.js loaded");

	document.querySelectorAll('.logout').forEach(el=>{
        el.addEventListener('click', logout);
    });
});

function logout(event){
	var target = event.target;
    
	if(target.id == "logout" || target.parentNode.id == "logout"){
        close_mobile_menu();

        if(typeof(Swal) != 'undefined'){
            var options = {
                iconHtml: '<img src="'+sim.loading_gif+'">',
                title: 'Logging out...',
                showConfirmButton: false,
                customClass: {
                    icon: 'no-border'
                }
            };
            
            Swal.fire(options);
        }

        var formData	= new FormData();
        formData.append('action','request_logout');

        fetch(sim.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(result=>result.text())
        .then(result=>{
            display_message(result,'success', false, true);
            //redirect to homepage
            location.href	= sim.base_url;
        });
    }
}
