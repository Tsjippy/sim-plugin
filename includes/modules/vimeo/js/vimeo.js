/* 
		Show vimeo in wp library
*/

//replace preview with vimeo iframe
wp.media.view.Attachment.Details.prototype.render = function() {
	wp.media.view.Attachment.prototype.render.apply( this, arguments );

	wp.media.mixin.removeAllPlayers();
	this.$( 'audio, video' ).each( function (i, elem) {
		var el = wp.media.view.MediaDetails.prepareSrc( elem );
		load_vimeo_video(el);

		new window.MediaElementPlayer( el, wp.media.mixin.mejsSettings );
	} );
}

//replace preview with vimeo iframe
function load_vimeo_video(el) {
	var vimeo_link	= el.src;
	if(vimeo_link == ''){
		//try again after a few miliseconds till the el.src is available
		setTimeout(load_vimeo_video, 20, el);
	}else{
		//if this is a vimeo item
		if(vimeo_link.includes('https://vimeo.com/')){
			//make sure there is not already a loader
			if(document.querySelector('#loaderwrapper') == null){
				//create a div container holding the loading gif
				var wrapper				= document.createElement("DIV");
				wrapper.id				='loaderwrapper';
				wrapper.style.margin	= 'auto';
				wrapper.style.width		= 'fit-content';

				wrapper.innerHTML	= '<img src="'+media_vars.loading_gif+'" style="max-height: 100px;"><br><b>Loading Vimeo video</b>';
		
				//add the div replacing the default video screen
				element	= document.querySelector('.wp-media-wrapper.wp-video');
				element.parentNode.replaceChild(wrapper, element);
			}

			//get the vimeo id
			var vimeo_id = vimeo_link.replace('https://vimeo.com/','');

			//build an iframe element to show the vimeo video
			var iframe			= document.createElement("iframe");

			//run when iframe is loaded
			iframe.onload		= function(){
				//show iframe
				iframe.style.display= '';
				//remove loading gif
				wrapper.remove();
			};
			iframe.src			= 'https://player.vimeo.com/video/'+vimeo_id;
			iframe.style.width	= '100%';
			iframe.style.height	= '100%';
			iframe.style.display= 'none';

			//add the loader image after the loading gif, it is hidden until fully loaded 
			wrapper.parentNode.insertBefore(iframe, wrapper.nextSibling);
		}
	}
};

wp_uploader = '';
plupload_file='';
window.wp.Uploader.prototype.init = function() { // plupload 'PostInit'
	this.uploader.bind('FileFiltered', function(up, files) {
		//show vimeo loader
		showLoader(document.querySelector('.upload-inline-status'), false, '<span class="vimeo" style="font-size:x-large;">Preparing upload to Vimeo</span>');
	});

	this.uploader.bind('BeforeUpload', function(up, file) {
		wp_uploader=up;
		plupload_file=file;
		if(file.type.split("/")[0] == 'video'){
			upload_to_vimeo(file.getNative());

			//mark plupload as finished
	 		file.percent							= 100;
			file.attachment.attributes.percent		= 100;
			file.loaded								= file.size;
			file.attachment.attributes.loaded		= file.size;
			file.status								= 5;
			file.attachment.attributes.status		= 5;
			file.attachment.attributes.uploading	= false;
			return false;
		}
	});
}

function upload_to_vimeo(file){
	var formdata = new FormData();
	formdata.append('action', 'prepare-vimeo-upload');
	formdata.append('file_size', file.size);
	formdata.append('file_name', file.name);
	formdata.append('file_type', file.type);
	
	var request = new XMLHttpRequest();
	request.open('POST', sim.ajax_url, false);
	request.send(formdata);

	if(request.status != 200){
		console.error("Failed because: " + request.responseText);
		wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);
		return;
	}

	var response 		= JSON.parse(request.response);
	var upload_url		= response.upload_link;
	var post_id			= response.post_id;
	var upload_start	= response.upload_start;

	//https://github.com/vimeo/vimeo.js#installation
	var upload = new tus.Upload(file, {
		uploadUrl: upload_url,
		chunk_size: 5000000,
		onError: function(error) {
			console.error("Failed because: " + error);
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);

			return;
		},
		onProgress: function(bytesUploaded, bytesTotal) {
			//calculate percentage
			var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)

			//show percentage in progressbar
			document.querySelectorAll('.media-progress-bar > div').forEach(div=>{
				div.style.width	= percentage+'%';
				div.innerHTML	= '<span style="width:100%;text-align:center;color:white;display:block;font-size:smaller;">'+percentage+'%</span>';
			});
		}, 
		onSuccess: function() {
			//get wp post details
			var formdata = new FormData();
			formdata.append('action', 'add-uploaded-vimeo');
			formdata.append('post_id', post_id);
			
			var request = new XMLHttpRequest();
			request.open('POST', sim.ajax_url, false);
			request.send(formdata);

			//mark as uploaded
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);

			document.querySelector('[data-id="'+post_id+'"] .filename>div').textContent='Uploaded to Vimeo';
		},
        onShouldRetry: function(err, retryAttempt, options) {
          var status = err.originalResponse ? err.originalResponse.getStatus() : 0
          // If the status is a 403, we do not want to retry.
          if (status === 403) {
            return false
          }
          
          // For any other status code, tus-js-client should retry.
          return true
        },
		onChunkComplete: function(chunkSize, bytesAccepted, bytesTotal){

		}
	})
	
	// Start the upload
	upload.start()
}

plupload.addFileFilter('max_file_size', function(maxSize, file, cb) {
	var undef;
   
	// Invalid file size
	if (file.size !== undef && maxSize && file.size > maxSize && file.type.split("/")[0] != 'video') {
	  this.trigger('Error', {
		code : plupload.FILE_SIZE_ERROR,
		message : plupload.translate('File size error.'),
		file : file
	  });
	  cb(false);
	} else {
	  cb(true);
	}
});