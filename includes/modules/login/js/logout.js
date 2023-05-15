import {
    closeMobileMenu,
    fetchRestApi
} from './shared.js';

console.log("logout.js loaded");

//Logout user
document.addEventListener("DOMContentLoaded",function() {

	document.querySelectorAll('.logout.hidden').forEach(el=>{
        el.addEventListener('click', logout);

        el.classList.remove('hidden');
    });
});

async function logout(event){
    event.stopPropagation();
    event.preventDefault();

	var target = event.target;
    
	if(target.matches(".logout")){
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
            
            if(document.fullscreenElement != null){
                options['target']	= document.fullscreenElement;
            }
            
            Swal.fire(options);
        }

        var formData	= new FormData();

        var response    = await fetchRestApi('logout', formData);

        if(response){
            Main.displayMessage(response,'success', false, true);

            //redirect to homepage
            location.href	= sim.baseUrl;
        }
    }
}


