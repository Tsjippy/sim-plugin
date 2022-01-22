console.log("Main.js loaded");

function change_hash(target){
	if(target.dataset.hash != null){
		var new_hash	= target.dataset.hash;
	}else{
		var new_hash	= target.textContent;
	}
	//Change the hash
	if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content') == true){
		//Add anchor if it is a main tab
		location.hash = new_hash;
	}else if(location.hash.replace('#','').split('#').length>1){
		var hash = location.hash.replace('#','').split('#');
		//Add anchor if it is a secondary tab
		location.hash = '#'+hash[0]+'#'+new_hash;
	}else{
		location.hash = location.hash+'#'+new_hash;
	}

	if(target.dataset.hash != null){
		process_hash();
	}
}

function process_hash(event=null){
	var content = decodeURIComponent(window.location.hash.replace('#',''));
	
	//split the hash on #
	content = content.split('#');
	
	//loop over all the tab buttons
	document.querySelectorAll('.tablink').forEach(function(tab_button){
		//Add the dataset if it does not exist yet.
		if(tab_button.dataset.text == undefined){
			tab_button.dataset.text = tab_button.textContent;
		}
	})
	
	tab_button = '';
	//find the tab and display it
	document.querySelectorAll('[data-text="'+content[0]+'"]').forEach(tabbutton=>{
		//only process non-modal tabs
		if(tabbutton.closest('.modal') == null){
			tab_button = tabbutton;
			display_tab(tabbutton);
		}
	})
	
	//if there is a second tab
	if(content.length>1){
		//get the content of the main tab
		var maincontent = document.getElementById(tab_button.dataset.target);
		
		//look in the main content for the second tabbutton
		var secondtab = maincontent.querySelector('[data-text="'+content[1]+'"]');
		display_tab(secondtab);
	}
}

function display_tab(tab_button){
	//reset hash if something goes wrong
	if(tab_button == null){
		console.log("resetting hash");
		window.location = window.location.href.split('#')[0];
	}
	
	//Show the  tab
	var tab = tab_button.closest('div').querySelector('#'+tab_button.dataset.target);
	
	if(tab != null){
		tab.classList.remove('hidden');
		//Mark the other tabbuttons as inactive
		tab_button.parentNode.childNodes.forEach(child=>{
			if(child.classList != null && child.classList.contains('active') && child != tab_button){
				//Make inactive
				child.classList.remove("active");
					
				//Hide the tab
				child.closest('div').querySelector('#'+child.dataset.target).classList.add('hidden');
			}
		});
		
		//Mark the tabbutton as active
		tab_button.classList.add("active");
	}
}

function getRoute(target,lat,lon){
	//Leave the origin empty on a mobile device to use the current location
	if(isMobileDevice()){
		var origin = '';
	}else{
		var origin = '&origin='+simnigeria.address;
	}
	var url = 'https://www.google.com/maps/dir/?api=1&destination='+lat+','+lon+origin;
	var win = window.open(url, '_blank');
	win.focus();
}

//Check if on mobile
function isMobileDevice() {
	return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
};

function showLoader(element, replace=true, message=''){
	if(element == null){
		return;
	}
	
	var wrapper	= document.createElement("DIV");
	wrapper.setAttribute('class','loaderwrapper');
	if(message != ''){
		wrapper.innerHTML	= message;
	}

	var loader	= document.createElement("IMG");
	loader.setAttribute('class','loadergif');
	loader.setAttribute("src", simnigeria.loading_gif);
	loader.style["height"]= "30px";

	wrapper.insertAdjacentElement('beforeEnd', loader);
	if(replace){
		element.parentNode.replaceChild(wrapper, element);
	}else{
		element.parentNode.insertBefore(wrapper, element.nextSibling);
	}

	return wrapper;
}

function send_statistics(){
	var request = new XMLHttpRequest();

	request.open('POST', simnigeria.ajax_url, true);
	
	var formData = new FormData();
	formData.append('action','add_page_view');
	formData.append('url',window.location.href);
	request.send(formData);
}

function display_message(message, icon, autoclose=false, no_ok=false){
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

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	//check for tab actions
	if(window.location.hash != ""){
		process_hash()
	}else{
		//send statistics
		send_statistics();
	}

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
});

function body_scrolling(type){
	//don't do anything on homepage
	if(document.querySelector('body').classList.contains('home')) return;
	
	if(type == 'disable'){
		//disable scrolling of the body
		document.querySelector("body").style.overflow = 'hidden';

		var menu = document.querySelector("#masthead");
		menu.style.overflowY	= 'scroll';
		menu.style.top			= '0px';
		menu.style.left			= '0';
		menu.style.right		= '0';
		menu.style.bottom		= '0';
		menu.style.position		= 'absolute';
	}else{
		//disable scrolling of the body
		document.querySelector("body").style.overflow = '';

		var menu = document.querySelector("#masthead");
		menu.style.overflowY	= '';
		menu.style.top			= '';
		menu.style.left			= '';
		menu.style.right		= '';
		menu.style.bottom		= '';
		menu.style.position		= '';
	}
}

//Hide or show the clicked tab
window.addEventListener("click", function(event) {
	var target = event.target;

	//close modal on close click
	if(target.matches(".close")){
		target.closest('.modal').classList.add('hidden');
	}
	
	//we clicked the menu
	if(target.closest('.menu-toggle') != null){
		if(document.querySelector('.menu-toggle').getAttribute("aria-expanded")=="true"){
			body_scrolling('disable');
		}else{
			body_scrolling('enable');
		}
	}

	//if clicked outside the menu, then close the menu
	if(
		document.querySelector('.menu-toggle').getAttribute("aria-expanded")=="true" &&
		target.closest('#site-navigation') == null && 
		target.closest('#mobile-menu-control-wrapper') == null
	){
		document.querySelector('#mobile-menu-control-wrapper').classList.remove("toggled");
		document.querySelector('.menu-toggle').setAttribute("aria-expanded", 'false');
		document.querySelector('#site-navigation').classList.remove("toggled");
		body_scrolling('enable');
	}

	//Process the click on tab button
	if(target.matches(".tablink")){
		//show the tab
		display_tab(target);
		//change the hash in browser
		change_hash(target);
		//send statistics
		send_statistics();
	}		
});