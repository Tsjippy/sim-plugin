var clickListener = require('./globals.js');
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

export function process_hash(event=null){
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

function click_listener(target){
    //Process the click on tab button
	if(target.matches(".tablink")){
		//show the tab
		display_tab(target);
		//change the hash in browser
		change_hash(target);
		//send statistics
		send_statistics();
	}	
}

clickListener.push( click_listener);