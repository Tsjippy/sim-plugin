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
                iconHtml: '<img src="'+sim.loadingGif+'">',
                title: 'Logging out...',
                showConfirmButton: false,
                customClass: {
                    icon: 'no-border'
                }
            };
            
            Swal.fire(options);
        }

        var formData	= new FormData();

        var response    = await fetchRestApi('logout', formData);

        if(response){
            main.displayMessage(response,'success', false, true);

            //redirect to homepage
            location.href	= sim.baseUrl;
        }
    }
}
