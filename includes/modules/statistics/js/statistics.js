//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	send_statistics();
});

//Hide or show the clicked tab
window.addEventListener("hashchange", function() {
    //send statistics
	send_statistics();
});

function send_statistics(){
    var request = new XMLHttpRequest();

    request.open('POST', sim.ajax_url, true);
    
    var formData = new FormData();
    formData.append('action','add_page_view');
    formData.append('url',window.location.href);
    request.send(formData);
}