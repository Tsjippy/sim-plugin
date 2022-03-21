console.log("Main.js loaded");

function change_url(target, second_tab=''){
	var new_param	= target.dataset.param_val;

	var hash		= target.dataset.hash;

	const url = new URL(window.location);

	//Change the url params
	if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content') == true){
		//Add query_arg if it is a main tab
		url.searchParams.set('main_tab', new_param);
	}else{
		url.searchParams.set('second_tab', new_param);
	}

	if(second_tab != ''){
		url.searchParams.set('second_tab', second_tab);
	}
	
	window.history.pushState({}, '', url);

	if(hash != null){
		window.location.hash	= hash;
	}else{
		window.location.hash	= '';
	}

	// switch tab when clicking on a change url link
	if(target.tagName == 'A'){
		switch_tab();
	}
}

function switch_tab(event=null){
	const url = new URL(window.location);
	const params = new Proxy(new URLSearchParams(window.location.search), {
		get: (searchParams, prop) => searchParams.get(prop),
	});
	
	var main_tab 	= params.main_tab;
	var last_tab	= '';
	if(main_tab != null){
		//find the tab and display it
		document.querySelectorAll('[data-param_val="'+main_tab+'"]').forEach(tabbutton=>{
			//only process non-modal tabs
			if(tabbutton.closest('.modal') == null){
				var result	= display_tab(tabbutton);
				if(result != false){
					last_tab	= result;
				}
			}
		});
	}

	var second_tab = params.second_tab;
	if(second_tab != null){
		//find the tab and display it
		last_tab.querySelectorAll('[data-param_val="'+second_tab+'"]').forEach(tabbutton=>{
			display_tab(tabbutton);
		});
	}
}

function display_tab(tab_button){
	//remove all existing highlights
	document.querySelectorAll('.highlight').forEach(el=>el.classList.remove('highlight'));

	// Get content area
	if(tab_button.dataset.target == undefined){
		var tab = document.querySelector('#'+tab_button.dataset.param_val);
	}else{
		var tab = tab_button.closest('div').querySelector('#'+tab_button.dataset.target);
	}
	
	if(tab != null){
		tab.classList.remove('hidden');

		if(tab_button.tagName != 'A'){
			//Mark the other tabbuttons as inactive
			tab_button.parentNode.querySelectorAll('.active:not(#'+tab_button.id+')').forEach(child=>{
				//Make inactive
				child.classList.remove("active");
					
				//Hide the tab
				child.closest('div').querySelector('#'+child.dataset.target).classList.add('hidden');
			});
			
			//Mark the tabbutton as active
			tab_button.classList.add("active");
		}

		//scroll to field
		if (window.location.hash) {
			var hash 		= window.location.hash.replace('#','');

			var hash_field	= tab.querySelector('[name^="'+hash+'"]');
		
			hash_field.scrollIntoView({block: "center"});

			var el			= hash_field.closest('.inputwrapper');
			if(el != null){
				hash_field.closest('.inputwrapper').classList.add('highlight');
			}
			hash_field.classList.add('highlight');
			hash_field.focus();
		}

		position_table();

		return tab;
	}else{
		return false;
	}
}

function getRoute(target,lat,lon){
	//Leave the origin empty on a mobile device to use the current location
	if(isMobileDevice()){
		var origin = '';
	}else{
		var origin = '&origin='+sim.address;
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
	loader.setAttribute("src", sim.loading_gif);
	loader.style["height"]= "30px";

	wrapper.insertAdjacentElement('beforeEnd', loader);
	if(replace){
		element.parentNode.replaceChild(wrapper, element);
	}else{
		element.parentNode.insertBefore(wrapper, element.nextSibling);
	}

	return wrapper;
}

function display_message(message, icon, autoclose=false, no_ok=false, timer=1500){
	if(message == undefined){
		return;
	}
	
	if(typeof(Swal) != 'undefined'){
		var options = {
			icon: icon,
			title: message.trim(),
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
		
		Swal.fire(options);
	}else{
		alert(message.trim());
	}
}

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	//loop over all the tab buttons
	document.querySelectorAll('.tablink').forEach(function(tab_button){
		//Add the dataset if it does not exist yet.
		if(tab_button.dataset.param_val == undefined){
			tab_button.dataset.param_val = tab_button.textContent.replace(' ','_').toLowerCase();
		}
	})

	//check for tab actions
	switch_tab();

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

		//scroll to top
		window.scrollTo(0, 0);

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
		document.querySelector('.menu-toggle') != null &&
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
		//change the url in browser
		change_url(target);

		//show the tab
		display_tab(target);
	}		
});

//check internet
async function has_internet(){
	await fetch('https://google.com', {
		method: 'GET', // *GET, POST, PUT, DELETE, etc.
		mode: 'no-cors',
	}).then((result) => {
		window['online'] = true;
	}).catch(e => {
		window['online'] = false;
	});

	return window['online'];
}

async function waitForInternet(){
	var internet	= await has_internet();
	if(!internet){
		display_message('You have no internet connection, waiting till internet is back...', 'warning', true, true, 5000);

		while(!internet){
			if(window['online']){
				break;
			}

			internet	= await has_internet();
		}
	}
}