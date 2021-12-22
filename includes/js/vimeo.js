//Catch click events
//Show upload form when the upload movie button is clicked
console.log("custom vimeo.js loaded");
window.addEventListener("click", function(event) {
	if (event.target.id == "upload_vimeo_video"){
		var form = document.getElementById("upload_vimeo_modal");
		if (form != null){
			form.style['display'] = "block";
		}
	}
});

/**
 Override function found in uploader.js
 * Used to notify WordPress abount completed upload.
 * @param callback
 */
WPVimeoVideos.Uploader.prototype.notifyWP = function (callback) {
    var self = this;
    // Skip the notify call if no valid data provided.
    if (!self.params.hasOwnProperty('wp') || (self.params.hasOwnProperty('wp') && !self.params.wp.hasOwnProperty('notify_endpoint'))) {
        console.log('Not valid notify_endpoint specified.');
        callback(null);
        return;
    }
    // Create request
    var http = new XMLHttpRequest();
    http.open('POST', self.params.wp.notify_endpoint, true);
    http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
    http.onreadystatechange = function () {
        if (http.readyState === 4) {
            var responseText = http.responseText;
            if (http.status === 200) { // OK
                var response = JSON.parse(responseText);
                callback(response);
            } else {
                if (self.params.hasOwnProperty('onWPNotifyError')) {
                    self.params.onWPNotifyError(responseText);
                }
            }
        }
    };
    // Collect data
    var data = WPVimeoVideos.Uploader.serializeObject({
        title: self.params.title,
        description: self.params.description,
        uri: self.currentUpload.uri
    });
	
	var vimeo_id = (self.currentUpload.uri).replace("/videos/","");
	/* Add the vimeo video to the content area */
	/* //creat a div
	var div = document.createElement("div");
	//create an iframe
	var iframe = document.createElement("iframe");
	//Add the iframe to the div
	div.appendChild(iframe);
	//Set the div class
	div.setAttribute("class","dgv-embed-container")
	//No borders to the iframe
	iframe.setAttribute("frameborder","0");
	//Set the iframe url
	iframe.setAttribute("src","https://player.vimeo.com/video/"+vimeo_id);
	//Get the mce editor
	var contenteditor = document.querySelector('.mce-edit-area').getElementsByTagName("iframe")[0].contentWindow.document;
	//add the div to the the editor
	contenteditor.querySelector("#tinymce").append(div);*/

	var shortcode = '[dgv_vimeo_video id="'+vimeo_id+'"]';
	var span = document.createElement("span");
	var shortcode_node = document.createTextNode(shortcode);
	span.appendChild(shortcode_node); 
	
	document.querySelector("#upload_vimeo_video").parentElement.appendChild(span);
	
	var contenteditor = document.querySelector('.mce-edit-area').getElementsByTagName("iframe")[0].contentWindow.document;
	contenteditor.querySelector("#tinymce").append(shortcode);
	
    console.log(data);
    http.send(data);
};
