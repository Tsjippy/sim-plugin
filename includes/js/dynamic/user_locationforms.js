console.log("Location.js loaded");

window['add_new_compound_data'] = function(result){
	//If succesfull
	if (result.status >= 200 && result.status < 400) {
		var add_compound_form = document.getElementById('add_compound_form');
		
		if(add_compound_form != null){
			//get the details from the form
			var name 		= add_compound_form.querySelector('#location_name').value;
			var addres 		= add_compound_form.querySelector('#location\\[address\\]').value;
			var latitude 	= add_compound_form.querySelector('#location\\[latitude\\]').value
			var longitude 	= add_compound_form.querySelector('#location\\[longitude\\]').value;
			
			var mainform = document.getElementById('location_info');
			
			//Fill in the new address and location
			mainform.querySelector('#location\\[address\\]').value 		= addres;
			mainform.querySelector('#location\\[latitude\\]').value 	= latitude;
			mainform.querySelector('#location\\[longitude\\]').value 	= longitude;
			
			//Add new compound to dropdown
			var newOption = new Option(name, name, true, true);
			
			jQuery('#location\\[compound\\]').append(newOption).trigger('change');
		}
	}
	
	hide_modals();
	
	return;
}

function filllocationfields(event){
	var target	= event.target;
	var form	= target.closest('form');
	
	var option	= target.options[target.selectedIndex];
	var value	= option.value;
	var name	= option.text;
	
	//Fill the fields based on the selected compound
	if(value == 'modal'){
		show_modal('add_compound');
	}else if (name != ""){
		//Get the compounds from the compounds variable
		var compounds = simnigeria.compounds;
		form.querySelector("[name='location[address]']").value		= name+' State';
		form.querySelector("[name='location[latitude]']").value		= compounds[value]["lat"];
		form.querySelector("[name='location[longitude]']").value	= compounds[value]["lon"];

		/* 
		//Loop over all compounds
		for (index = 0; index < compounds.length; index++) { 
			//Check if this is the selected compound
			if(compounds[index]["title"] == name){
				//Fill the elements with the compound data
				form.querySelector("[name='location[address]']").value		= compounds[index]["address"];
				form.querySelector("[name='location[latitude]']").value		= compounds[index]["coord_x"];
				form.querySelector("[name='location[longitude]']").value	= compounds[index]["coord_y"]; 
			}
		}
		*/
	}
}

//dynamically load google maps script only when needed
function load_google_maps_script(){
	if(document.getElementById('googlemaps') == null){
		const script = document.createElement('script');
		script.id = 'googlemaps';
		script.src = '//maps.googleapis.com/maps/api/js?key=AIzaSyC0FgB-vZ1p3dgKEBwPxb7UgqVvN8zNNms'
		script.async = true;
		document.body.append(script);
	}
}

document.addEventListener("DOMContentLoaded",function() {
	load_google_maps_script();

	//Set coordinates if compound is selected
	document.querySelectorAll("[name='location[compound]'").forEach(function(element){
		element.addEventListener('change', filllocationfields);
	});

	
	//Add event listener to the latitude field
	var element = document.querySelector(".latitude");
	if (typeof(element) != 'undefined' && element != null){
		element.addEventListener('keydown', setTimer);
	}
	
	//Add event listener to the longitude field
	var element = document.querySelector(".longitude");
	if (typeof(element) != 'undefined' && element != null){
		element.addEventListener('keydown', setTimer);
	}
	
	//Only continue if typing has stopped for 1 second
	var timer = null;
	function setTimer(e) {
		clearTimeout(timer); 
		timer = setTimeout(setAddress, 1000)
	}
	
	//Reverse geocode coordinates to address, using the Google API
	function setAddress(){
		var lat = document.querySelector(".latitude").value;
		var lon = document.querySelector(".longitude").value;
		var address_field = document.querySelector(".address");
		
		var geocoder = new google.maps.Geocoder();
		if (lat != "" && lon != ""){
			var latlng = { lat: parseFloat(lat), lng: parseFloat(lon) };
			
			geocoder.geocode({ location: latlng }, 
				function(results, status) {
					if (status === "OK") {
					  if (results[0]) {
						  document.querySelectorAll(".address").forEach(function(field){field.value = results[0].formatted_address});
					  }
					}
				}
			);
		}
	}
	
	//Only fill the coordinates after someone stopped typing in the address field
	var timer = null;
	var element = document.querySelector(".address");
	if (typeof(element) != 'undefined' && element != null){
		element.addEventListener('keydown', function(e) {
			clearTimeout(timer); 
			timer = setTimeout(setCoordinates, 1000,e)
		});
	}

	function setCoordinates(event){
		//Geocode address to coordinates
		var geocoder = new google.maps.Geocoder();
		var address = event.target.value;
		geocoder.geocode({ address: address}, 
			function(results, status) {
				if (status === "OK") {
					document.querySelectorAll(".latitude").forEach(function(field){field.value = (results[0].geometry.location.lat()).toFixed(7)});
					document.querySelectorAll(".longitude").forEach(function(field){field.value = (results[0].geometry.location.lng()).toFixed(7)});
				}
			}
		);
	};
	
	//If the current locationbutton is clicked, get the location, and fill the form
	document.addEventListener('click',function(event) {
		//Check user location
		if(event.target.name=='use_current_location_button'){
			if (navigator.geolocation) {
				navigator.geolocation.getCurrentPosition(showPosition);
			} else { 
				alert = "Geolocation is not supported by your browser.";
			}
		}
	});

	function showPosition(position) {
		var lat = (position.coords.latitude).toFixed(7);
		var lon = (position.coords.longitude).toFixed(7);
		
		document.querySelectorAll(".latitude").forEach(function(field){field.value = lat});
		document.querySelectorAll(".longitude").forEach(function(field){field.value = lon});
		setAddress();
	}
});

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic user_location forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_user_location');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_user_location_fields(el));
});

window.addEventListener("click", user_location_listener);
window.addEventListener("input", user_location_listener);

user_location_prev_el = '';
function user_location_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		return;
	}
	
	//prevent duplicate event handling
	if(el == user_location_prev_el){
		return;
	}
	user_location_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ user_location_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_user_location_fields(el);
}

function process_user_location_fields(el){
	var name = el.name;

}