console.log("Main.js loaded");

import 'nice-select2';
import {displayMessage, showLoader, isMobileDevice, } from './imports.js';
export  {displayMessage, showLoader, isMobileDevice};

export function changeUrl(target, secondTab=''){
	let newParam	= target.dataset.param_val;
	let hash		= target.dataset.hash;
	const url 		= new URL(window.location);

	//Change the url params
	if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content')){
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
	let params = new Proxy(new URLSearchParams(window.location.search), {
		get: (searchParams, prop) => searchParams.get(prop),
	});
	
	let mainTab 	= params.main_tab;
	let lastTab		= '';

	if(mainTab != null){
		//find the tab and display it
		document.querySelectorAll(`[data-param_val="${mainTab}"]:not(.active)`).forEach(tabbutton=>{
			//only process non-modal tabs
			if(tabbutton.closest('.modal') == null){
				let result	= displayTab(tabbutton);
				if(result){
					lastTab	= result;
				}
			}
		});
	}

	let secondTab = params.second_tab;
	if(secondTab != null){
		//find the tab and display it
		document.querySelectorAll(`[data-param_val="${secondTab}"]:not(.active)`).forEach(tabbutton=>{
			displayTab(tabbutton);
		});
	}
}

export function displayTab(tabButton){

	//remove all existing highlights
	document.querySelectorAll('.highlight').forEach(el=>el.classList.remove('highlight'));

	let tab;
	// Get content area
	if(tabButton.dataset.target == undefined){
		tab = document.querySelector('#'+tabButton.dataset.param_val);
	}else{
		tab = tabButton.closest('div:not(.tablink-wrapper)').querySelector('#'+tabButton.dataset.target);
	}
	
	if(tab != null){
		tab.classList.remove('hidden');

		if(tabButton.tagName != 'A'){
			//Mark the other tabbuttons as inactive
			tabButton.parentNode.querySelectorAll(`:scope > .active:not(#${tabButton.id})`).forEach(child=>{
				//Make inactive
				child.classList.remove("active");
					
				//Hide the tab
				var childTab	= child.closest('div:not(.tablink-wrapper)').querySelector('#'+child.dataset.target);
				if(childTab == null){
					console.error('Tab to hide not found:');
					console.error(child.closest('div:not(.tablink-wrapper)'));
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
			var hash 		= window.location.hash.replace('#', '');

			var hashField	= tab.querySelector(`[name^="${hash}"]`);
		
			if(hashField != null){
				hashField.scrollIntoView({block: "center"});

				var el			= hashField.closest('.inputwrapper');
				if(el != null){
					hashField.closest('.inputwrapper').classList.add('highlight');
				}
				hashField.classList.add('highlight');
				hashField.focus();
			}
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

export function showModal(modal){
	if(typeof(modal) == 'string'){
		modal = document.getElementById(modal + "_modal");
	}
	
	if(modal != null){
		// Prevent main page scrolling
		document.body.style.top			= `-${window.scrollY}px`;
		document.body.style.position 	= 'fixed';
		let prim						= document.getElementById('primary');
		if(prim != null){
			prim.style.zIndex			= 99999;
		}

		modal.classList.remove('hidden');

		modal.style.display	= 'block';
	}	
}

export function hideModals(){
	let modals	= document.querySelectorAll('.modal:not(.hidden)');
	if(modals.length != 0){
		modals.forEach(modal=>{
			modal.classList.add('hidden');
			modal.style.removeProperty('display');
			
			const event = new Event('modalclosed');
			modal.dispatchEvent(event);
		});

		// Turn main page scrolling on again
		const scrollY					= document.body.style.top;
		document.body.style.position 	= '';
		document.body.style.top 		= '';
		window.scrollTo(0, parseInt(scrollY || '0') * -1);
		let prim						= document.getElementById('primary');
		if(prim != null){
			prim.style.zIndex			= 1;
		}
	}
}

//check internet
async function hasInternet(){
	if(location.href.includes('localhost')){
		window['online'] = true;
		return window['online'];
	}

	await fetch(location.href).then((result) => {
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
		if(select._niceselect  == undefined){
			NiceSelect.bind(select, {searchable: true});
		}
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