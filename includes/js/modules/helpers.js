export function showLoader(element,replace=true){
	if(element == null){
		return;
	}
	
	var loader = document.createElement("IMG");
	loader.setAttribute('class','loadergif')
	loader.setAttribute("src", simnigeria.loading_gif);
	loader.style["height"]= "30px";
	if(replace){
		element.parentNode.replaceChild(loader, element);
	}else{
		element.parentNode.insertBefore(loader, element.nextSibling);
	}

	return loader;
}

export function display_message(message, icon, autoclose=false, no_ok=false){
	if(message == undefined){
		return;
	}
	
	if(typeof(Swal) != 'undefined'){
		var options = {
			icon: icon,
			title: message,
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
			options['timer'] = 1500;
		}
		
		Swal.fire(options);
	}else{
		alert(message);
	}
}

//Check if on mobile
export function isMobileDevice() {
	return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
};