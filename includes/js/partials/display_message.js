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