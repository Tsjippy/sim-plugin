//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	sendStatistics();
});

//Hide or show the clicked tab
window.addEventListener("hashchange", function() {
    //send statistics
	sendStatistics();
});

function sendStatistics(){
    var formData = new FormData();
    formData.append('url',window.location.href);
    formData.append('_wpnonce', sim.restnonce);

	fetch(
		sim.base_url+'/wp-json/sim/v1/statistics/add_page_view',
		{
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		}
	);
}