console.log("Main.js loaded");

import {clickListener} from './globals.js';
import {process_hash} from './tabs.js';
import {send_statistics} from './statistics.js';
import NiceSelect from "./nice-select2.js";
import {hide_modals} from './modals.js';

hide_modals();
//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	//check for tab actions
	if(window.location.hash != ""){
		process_hash();
	}else{
		//send statistics
		send_statistics();
	}

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
});

//Hide or show the clicked tab
window.addEventListener("click", function(event) {
	var target = event.target;

	for(var x=0; x++; x<clickListener.length){
		clickListener[x](target);
	}
});