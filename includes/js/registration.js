console.log("Registration.js loaded");

document.addEventListener("DOMContentLoaded",function() {
	var first_name_element 	= document.getElementById("first_name");
	var last_name_element 	= document.getElementById("last_name");

	if (typeof(first_name_element) != 'undefined' && first_name_element != null && typeof(last_name_element) != 'undefined' && last_name_element != null){
		//Attach an event listener to the submit button
		document.getElementById("wp-submit").addEventListener("click", function setUsername(e){
			var button = this;
			//Disable the submit button
			button.disabled = true;
			//prevent the normal redirect behavior
			e.preventDefault();
			
			//Show the spinner
			var img = document.createElement('img'); 
			img.src = simnigeria.loading_gif;
			img.style["height"]="30px";
			//img.style["margin"]= "15px 0px 0px 10px";
			img.style["position"]= "absolute";
			button.parentNode.insertBefore(img, button.nextSibling);
			
			//Rerieve the first and last name from the form
			var first_name = document.getElementById("first_name").value;
			var last_name = document.getElementById("last_name").value;	
			first_name = first_name.charAt(0).toUpperCase() + first_name.slice(1);
			last_name = last_name.charAt(0).toUpperCase() + last_name.slice(1);
			document.getElementById("first_name").value = first_name;
			document.getElementById("last_name").value = last_name;
			
			//Form is filled, set the UserName via AJAX
			if (first_name != "" && last_name != ""){
				var request = new XMLHttpRequest();
				request.open('POST', simnigeria.ajax_url, true);
				request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');

				request.onload = function () {
					if (this.status >= 200 && this.status < 400) {
						// If successful
						console.log("Username us "+this.response);
						
						//Set the username field
						document.getElementById("user_login").value = this.response;
						//remove the listener
						button.removeEventListener("click", setUsername);
						//Enable the button again
						button.disabled = false;
						//Click the button with normal behavior
						button.click();
						//Disable the button again
						button.disabled = true;
					} else {
						// If fail
						console.log(this.response);
					}
				};
				request.onerror = function() {
					// Connection error
				};
				request.send('action=getUserName&first_name='+first_name+"&last_name="+last_name);
			//Form is not filled completely, click the button so they see a warning about it
			}else{
				//Set the username field with a text so that people wont get a warning about it, (they will get a warning about their first or last name though
				document.getElementById("user_login").value = "dummy";
				//remove the listener
				button.removeEventListener("click", setUsername);
				//Enable the button again
				button.disabled = false;
				//Continue
				button.click();
				//Disable the button again
				button.disabled = true;
			}
		});
	}
});