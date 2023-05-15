console.log("Welcome.js loaded");

function hideMessage(){
	//Hide the message
	document.querySelector("#welcome-message").classList.add('hidden');

	let formData = new FormData();
	formData.append('_wpnonce', sim.restNonce);
	fetch(
		`${sim.baseUrl}/wp-json${sim.restApiPrefix}/frontpage/hide_welcome`, 
		{
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		}
	).catch(err => console.error(err));
}

document.addEventListener("DOMContentLoaded", function() {

	let el=document.querySelector("#welcome-message-button");
	if(el != null){
		el.addEventListener("click", hideMessage);
	}
});