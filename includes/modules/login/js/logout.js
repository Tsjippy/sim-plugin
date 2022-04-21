import {
    closeMobileMenu,
    fetchRestApi
} from './shared.js';

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

        var response    = await fetchRestApi('logout', formdata);

        if(response){
            display_message(response,'success', false, true);

            //redirect to homepage
            location.href	= sim.base_url;
        }
    }
}
