import {closeMobileMenu} from './shared.js';

//Logout user
document.addEventListener("DOMContentLoaded",function() {
	console.log("logout.js loaded");

	document.querySelectorAll('.logout').forEach(el=>{
        el.addEventListener('click', logout);
    });
});

async function logout(event){
    event.stopPropagation();
    event.preventDefault();

	var target = event.target;
    
	if(target.id == "logout" || target.parentNode.id == "logout"){
        closeMobileMenu();

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

        var formdata	= new FormData();
	    formdata.append('_wpnonce', sim.restnonce);

        var result = await fetch(
            sim.base_url+'/wp-json/sim/v1/login/logout',
            {
                method: 'POST',
                credentials: 'same-origin',
                body: formdata
            }
        );
    
        var response	= await result.json();

        if(result.ok){
            display_message(response,'success', false, true);

            //redirect to homepage
            location.href	= sim.base_url;
        }else{
            console.error(response);
            display_message(response, 'error');
        };
    }
}
