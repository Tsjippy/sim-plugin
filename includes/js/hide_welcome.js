console.log("hide_welcome.js loaded");
document.querySelector("#welcome-message-button").addEventListener("click", function(){
	//Hide the message
	document.querySelector("#welcome-message").style['display']='none';
	
	//AJAX
	var request = new XMLHttpRequest();

	request.open('POST', simnigeria.ajax_url, true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
	request.onload = function () {
		if (this.status >= 200 && this.status < 400) {
			// If successful
			console.log(this.response);
		} else {
			// If fail
			console.log(this.response);
		}
	};
	request.onerror = function() {
		// Connection error
	};
	request.send('action=hideWelcome');
});