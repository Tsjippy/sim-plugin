console.log("Main.js loaded");

export function changeUrl(target, secondTab=''){
	var newParam	= target.dataset.param_val;
	var hash		= target.dataset.hash;
	const url 		= new URL(window.location);

	//Change the url params
	if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content') == true){
		//Add query_arg if it is a main tab
		url.searchParams.set('main_tab', newParam);
		url.searchParams.delete('second_tab');
	}else{
		url.searchParams.set('second_tab', newParam);
	}

	if(secondTab != ''){
		url.searchParams.set('second_tab', secondTab);
	}
	
	window.history.pushState({}, '', url);

	if(hash != null){
		window.location.hash	= hash;
	}else{
		window.location.hash	= '';
	}

	// switch tab when clicking on a change url link
	if(target.tagName == 'A'){
		switchTab();
	}
}

function switchTab(event=null){
	const params = new Proxy(new URLSearchParams(window.location.search), {
		get: (searchParams, prop) => searchParams.get(prop),
	});
	
	var mainTab 	= params.main_tab;
	var lastTab		= '';
	if(mainTab != null){
		//find the tab and display it
		document.querySelectorAll(`[data-param_val="${mainTab}"]`).forEach(tabbutton=>{
			//only process non-modal tabs
			if(tabbutton.closest('.modal') == null){
				var result	= displayTab(tabbutton);
				if(result){
					lastTab	= result;
				}
			}
		});
	}

	var secondTab = params.secondTab;
	if(secondTab != null){
		//find the tab and display it
		lastTab.querySelectorAll(`[data-param_val="${secondTab}"]`).forEach(tabbutton=>{
			displayTab(tabbutton);
		});
	}
}

function displayTab(tabButton){
	//remove all existing highlights
	document.querySelectorAll('.highlight').forEach(el=>el.classList.remove('highlight'));

	// Get content area
	if(tabButton.dataset.target == undefined){
		var tab = document.querySelector('#'+tabButton.dataset.param_val);
	}else{
		var tab = tabButton.closest('div').querySelector('#'+tabButton.dataset.target);
	}
	
	if(tab != null){
		tab.classList.remove('hidden');

		if(tabButton.tagName != 'A'){
			//Mark the other tabbuttons as inactive
			tabButton.parentNode.querySelectorAll(':scope > .active:not(#'+tabButton.id+')').forEach(child=>{
				//Make inactive
				child.classList.remove("active");
					
				//Hide the tab
				var childTab	= child.closest('div').querySelector('#'+child.dataset.target);
				if(childTab == null){
					console.error('Tab to hide not found:');
					console.error(child.closest('div'));
					console.error('#'+child.dataset.target);
				}else{
					childTab.classList.add('hidden');
				}
			});
			
			//Mark the tabbutton as active
			tabButton.classList.add("active");
		}

		//scroll to field
		if (window.location.hash) {
			var hash 		= window.location.hash.replace('#','');

			var hashField	= tab.querySelector('[name^="'+hash+'"]');
		
			hashField.scrollIntoView({block: "center"});

			var el			= hashField.closest('.inputwrapper');
			if(el != null){
				hashField.closest('.inputwrapper').classList.add('highlight');
			}
			hashField.classList.add('highlight');
			hashField.focus();
		}

		// position any tables on this tab, as they can only be positioned when visible
		if(typeof(SimTableFunctions) != 'undefined'){
			SimTableFunctions.positionTable();
		}

		return tab;
	}else{
		return false;
	}
}

export function getRoute(target,lat,lon){
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
export function isMobileDevice() {
	return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
};

function bodyScrolling(type){
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

export function showLoader(element, replace=true, message=''){
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
	loader.setAttribute("src", sim.loadingGif);
	loader.style["height"]= "30px";

	wrapper.insertAdjacentElement('beforeEnd', loader);
	if(replace){
		element.parentNode.replaceChild(wrapper, element);
	}else{
		element.parentNode.insertBefore(wrapper, element.nextSibling);
	}

	return wrapper;
}

export function displayMessage(message, icon, autoclose=false, no_ok=false, timer=1500){
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

export function showModal(modal_id){
	var modal = document.getElementById(modal_id+"_modal");
	
	if(modal != null){
		modal.classList.remove('hidden');
	}	
}

export function hideModals(){
	document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>{
		modal.classList.add('hidden');
	});
}

//check internet
async function hasInternet(){
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

export async function waitForInternet(){
	var internet	= await hasInternet();
	if(!internet){
		displayMessage('You have no internet connection, waiting till internet is back...', 'warning', true, true, 5000);

		while(!internet){
			if(window['online']){
				break;
			}

			internet	= await hasInternet();
		}
	}
}

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	// remove any empty widgets
	document.querySelectorAll('.widget').forEach(w=>{
		if(w.innerHTML == ''){
			w.remove();
		}
	});

	//loop over all the tab buttons
	document.querySelectorAll('.tablink').forEach(function(tabButton){
		//Add the dataset if it does not exist yet.
		if(tabButton.dataset.param_val == undefined){
			tabButton.dataset.param_val = tabButton.textContent.replace(' ','_').toLowerCase();
		}
	})

	//check for tab actions
	switchTab();

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
});


//Hide or show the clicked tab
window.addEventListener("click", function(event) {	
	var target = event.target;

	//close modal on close click
	if(target.matches(".modal .close")){
		target.closest('.modal').classList.add('hidden');
	}
	
	//we clicked the menu
	if(target.closest('.menu-toggle') != null){
		event.preventDefault();
		if(document.querySelector('.menu-toggle').getAttribute("aria-expanded")=="true"){
			bodyScrolling('disable');
		}else{
			bodyScrolling('enable');
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
		bodyScrolling('enable');
	}

	//Process the click on tab button
	if(target.matches(".tablink")){
		event.preventDefault();
		//change the url in browser
		changeUrl(target);

		//show the tab
		displayTab(target);
	}	
	
	//close modal if clicked outside of modal
	if(target.closest('.modal-content') == null && target.closest('.swal2-container') == null && target.tagName=='DIV'){
		hideModals();
	}
});
