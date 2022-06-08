console.log("Location.js loaded");

function fillLocationFields(event){
	var target	= event.target;
	var form	= target.closest('form');
	
	var option	= target.options[target.selectedIndex];
	var value	= option.value;
	var name	= option.text;
	
	//Fill the fields based on the selected compound
	if(value == 'modal'){
		Main.showModal('add_compound');
	}else if (name != ""){
		//Get the compounds from the compounds variable
		form.querySelector("[name='location[address]']").value		= name+' State';
		form.querySelector("[name='location[latitude]']").value		= sim.locations[value]["lat"];
		form.querySelector("[name='location[longitude]']").value	= sim.locations[value]["lon"];
	}
}

//dynamically load google maps script only when needed
function loadGoogleMapsScript(){
	if(document.getElementById('googlemaps') == null){
		const script = document.createElement('script');
		script.id = 'googlemaps';
		script.src = '//maps.googleapis.com/maps/api/js?key=AIzaSyC0FgB-vZ1p3dgKEBwPxb7UgqVvN8zNNms'
		script.async = true;
		document.body.append(script);
	}
}

document.addEventListener("DOMContentLoaded",function() {
	loadGoogleMapsScript();

	//Set coordinates if compound is selected
	document.querySelectorAll("[name='location[compound]'").forEach(function(element){
		element.addEventListener('change', fillLocationFields);
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
	var timer 	= null;
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
				Main.displayMessage("Geolocation is not supported by your browser.");
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