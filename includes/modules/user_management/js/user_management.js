async function disableUserAccount(target){
	var response	= await FormSubmit.submitForm(target, 'user_management/disable_useraccount');

	if(response){
		if(target.textContent.includes('Disable')){
			target.textContent	= target.textContent.replace('Disable', 'Enable');
		}else{
			target.textContent	= target.textContent.replace('Enable', 'Disable');
		}
		main.displayMessage(response);
	}
}

async function updateUserRoles(target){
    var response	= await FormSubmit.submitForm(target, 'user_management/update_roles');

	if(response){
		main.displayMessage(response);
	}
}

async function extendValidity(target){
	var response	= await FormSubmit.submitForm(target, 'user_management/extend_validity');

	if(response){
		main.displayMessage(response);
	}
}

async function createUserAccount(target){
    var response	= await FormSubmit.submitForm(target, 'user_management/add_useraccount');

	if(response){
		main.displayMessage(response.message);
	}
}

document.addEventListener('click', ev=>{
    const target    = ev.target;

    if(target.name == "disable_useraccount"){	
        disableUserAccount(target);
    }else if(target.name == 'updateroles'){
        updateUserRoles(target);
    }else if(target.name == 'extend_validity'){
        extendValidity(target);
    }else if(target.name == 'adduseraccount'){
		createUserAccount(target);
	}
});