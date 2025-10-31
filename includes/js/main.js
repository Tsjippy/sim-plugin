console.log("Main.js loaded");

import { bind as NiceSelect } from 'nice-select2';
import { isMobileDevice } from './partials/mobile.js';
import { showLoader } from './partials/show_loader.js';
import { displayMessage } from './partials/display_message.js';
import { changeUrl, switchTab, displayTab } from './partials/tabs.js';
import { showModal, hideModals } from './partials/modals.js';
import { hasInternet, waitForInternet } from './partials/internet_connection.js';


export { displayMessage, isMobileDevice, showLoader, changeUrl, switchTab, displayTab, showModal, hideModals, hasInternet, waitForInternet };

export function attachNiceSelect(element, options = {searchable: true}){
	if(element._niceSelect == undefined){
		NiceSelect(element, options);
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

function bodyScrolling(type){
	//don't do anything on homepage
	//if(document.querySelector('body').classList.contains('home')) return;
	
	if(type == 'disable'){
		//disable scrolling of the body
		document.querySelector("body").style.overflow = 'hidden';
 		document.body.style.top			= `0px`;

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
		//enable scrolling of the body
		document.querySelector("body").style.overflow = '';
		document.body.style.top 		= '';

		var menu = document.querySelector("#masthead");
		menu.style.overflowY	= '';
		menu.style.top			= '';
		menu.style.left			= '';
		menu.style.right		= '';
		menu.style.bottom		= '';
		menu.style.position		= '';
	}
}

function positionSubSubMenus(){
	const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);

	document.querySelectorAll('#primary-menu .sub-menu .sub-menu').forEach(el=>{
		el.style.left		= '100%';
		el.parentNode.closest('ul').style.left	= '100%';

		// Get parent right coordinate
		let startPos	= el.parentNode.getBoundingClientRect()['right'];
		
		// el with
		let width	= el.getBoundingClientRect()['width'];

		// move to left if not fitting on screen
		if(startPos + width > vw){
			el.style.right	= '100%';
			el.style.left	= 'unset';
		}else{
			el.style.removeProperty('left');
		}

		// Reset left
		el.parentNode.closest('ul').style.removeProperty('left');
	});
}

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	// Display loaders
	document.querySelectorAll(`.loader-image-trigger`).forEach( el =>{
		let size	= el.dataset.size ? el.dataset.size : 50 ;
		let text	= el.dataset.text ? el.dataset.text : '';

		showLoader(el, true, size, text);
	});

	// remove any empty widgets
	document.querySelectorAll('.widget').forEach(w=>{
		if(w.innerHTML == ''){
			w.remove();
		}
	});

	//loop over all the tab buttons
	document.querySelectorAll('.tablink').forEach(function(tabButton){
		//Add the dataset if it does not exist yet.
		if(tabButton.dataset.paramVal == undefined){
			tabButton.dataset.paramVal = tabButton.textContent.replace(' ','-').toLowerCase().trim();
		}
	})

	//check for tab actions
	switchTab();

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		attachNiceSelect(select);
	});

	// hide mobile hidden
	if(isMobileDevice()){
		document.querySelectorAll('.hide-on-mobile:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	}else{
		document.querySelectorAll('.hide-on-desktop:not(.hidden)').forEach(el=>el.classList.add('hidden'));

		positionSubSubMenus();
	}

	// Check for messages
	let params = new Proxy(new URLSearchParams(window.location.search), {
		get: (searchParams, prop) => searchParams.get(prop),
	});

	if(params.message != null){
		let type = 'success';

		if(params.type != null){
			type	= params.type;
		}
		displayMessage(params.message, type);
	}
});

//Hide or show the clicked tab
window.addEventListener("mousedown", function(event) {	
	var target = event.target;
	
	//we clicked the menu
	if(target.closest('.menu-toggle') != null){
		event.preventDefault();
		if(target.closest('.menu-toggle').getAttribute("aria-expanded") == "true"){
			bodyScrolling('enable');
		}else{
			bodyScrolling('disable');
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
		document.querySelector('.menu-toggle').setAttribute("aria-expanded", 0);
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
	let modal	= document.querySelector('.modal:not(.hidden)');
	if(modal != undefined){
		let scrollBarWidth	= window.innerWidth - modal.clientWidth;
		if(
			target.matches(".modal .close") || 					// close button clicked
			(
				(
					scrollBarWidth > 0	&&						// there is a scrollbar
					event.clientX < modal.clientWidth			// but we did not click on it
				)	||
				scrollBarWidth	=== 0							// there is no scrollbar
			)	&&
			(
				target.closest('.modal-content') == null && 	// not clicked inside the modal
				target.closest('.swal2-container') == null &&	// not clicked on swal message container
				target.tagName == 'DIV'							// the target is a div
			)
		){
			//console.log(event.clientX);
			//console.log(window.outerWidth);
			hideModals();
		}
	}
});


// disable scrolling on number fields
document.addEventListener("wheel", function(event){
    if(document.activeElement.type === "number"){
        document.activeElement.blur();
    }
});