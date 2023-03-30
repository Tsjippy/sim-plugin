import {VimeoUpload} from './vimeo_upload.js';
import { showLoader } from './../../../js/imports.js';

console.log('vimeo loaded');
/* 
		Show vimeo in wp library
*/
//replace preview with vimeo iframe
// copied from C:\xampp\htdocs\simnigeria\wp-includes\js\media-views.js
wp.media.view.Attachment.Details.prototype.render = function() {
	wp.media.view.Attachment.prototype.render.apply( this, arguments );

	wp.media.mixin.removeAllPlayers();
	this.$( 'audio, video' ).each( function (i, elem) {
		
		var el = wp.media.view.MediaDetails.prepareSrc( elem );
		new window.MediaElementPlayer( el, wp.media.mixin.mejsSettings );
		if(elem.tagName == 'VIDEO'){
			loadVimeoVideo(el);
		}
	} );
} 

//replace preview with vimeo iframe
function loadVimeoVideo(el) {
	var vimeoLink	= el.src;
	if(el.querySelector('source') != null){
		vimeoLink	= el.querySelector('source').src;
	}
	if(vimeoLink == '' || document.querySelector('.wp-media-wrapper.wp-video') == null){
		//try again after a few miliseconds till the el.src is available
		setTimeout(loadVimeoVideo, 20, el);
	//if this is a vimeo item
	}else if(vimeoLink.includes('https://vimeo.com/')){
		var wrapper	= document.querySelector('#loaderwrapper');

		//make sure there is not already a loader
		if(document.querySelector('#loaderwrapper') == null){
			//create a div container holding the loading gif
			wrapper					= document.createElement("DIV");
			wrapper.id				='loaderwrapper';
			wrapper.style.margin	= 'auto';
			wrapper.style.width		= 'fit-content';

			wrapper.innerHTML		= `<img src="${media_vars.loadingGif}" style="max-height: 100px;"><br><b>Loading Vimeo video</b>`;
	
			//add the div replacing the default video screen
			var element	= document.querySelector('.wp-media-wrapper.wp-video');
			element.parentNode.replaceChild(wrapper, element);
		}

		//get the vimeo id
		var vimeoId = vimeoLink.replace('https://vimeo.com/','');

		//build an iframe element to show the vimeo video
		var iframe	= document.createElement("iframe");

		//run when iframe is loaded
		iframe.onload		= function(){
			//show iframe
			iframe.style.display= '';
			
			//remove loading gif
			wrapper.remove();
		};
		iframe.src			= 'https://player.vimeo.com/video/'+vimeoId;
		iframe.style.width	= '100%';
		iframe.style.height	= '100%';
		iframe.style.display= 'none';

		//add the loader image after the loading gif, it is hidden until fully loaded 
		wrapper.parentNode.insertBefore(iframe, wrapper.nextSibling);
	}else{
		var el = wp.media.view.MediaDetails.prepareSrc( elem );
		new window.MediaElementPlayer( el, wp.media.mixin.mejsSettings );
	}
}

window.wp.Uploader.prototype.init = function() { // plupload 'PostInit'
	this.uploader.bind('FileFiltered', function(_up, _files) {
		if(_files.type.split("/")[0] == 'video'){
			//show vimeo loader
			try{
				showLoader(document.querySelector('.upload-inline-status'), false, '<span class="vimeo" style="font-size:x-large;">Preparing upload to Vimeo</span>');
			}catch(error){
				console.error(error);
			}
		}
	})

	this.uploader.bind('BeforeUpload', function(up, file) {
		if(file.type.split("/")[0] == 'video'){
			wpMediaUpload(file, up);

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

// upload video to vimeo
async function wpMediaUpload (plupload_file, wp_uploader) {
    var file    = plupload_file.getNative();
    if (!file) return;

    //update upload count
    document.querySelector('.upload-details .upload-index').textContent    = wp_uploader.total.uploaded+1;
    
    document.querySelector('.upload-details .upload-filename').textContent = wp_uploader.files[wp_uploader.total.uploaded].name;

	var uploader	= new VimeoUpload(file);
	var upload		= await uploader.tusUploader();

    upload.options.onProgress   = function(bytesUploaded, bytesTotal) {
		// Show loader
		if(document.querySelector('.loaderwrapper .media-progress-bar') == null){
			document.querySelector('.loaderwrapper').innerHTML	= `
			<div class="media-progress-bar" style='display:block;height: 20px;'>
				<div style="width: 0%;height: 20px;">
					<span style="width:100%;text-align:center;color:white;display:block;font-size:smaller;">0.00%</span>
				</div>
			</div>`;
		}

        //calculate percentage
        let percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)
    
        //show percentage in progressbar
        document.querySelectorAll('.loaderwrapper .media-progress-bar > div, .media-progress-bar > div, .selection-view .uploading:first-child .media-progress-bar > div, .media-uploader-status.uploading .media-progress-bar > div').forEach(div=>{
            div.style.width	= percentage+'%';
            div.innerHTML	= '<span style="width:100%;text-align:center;color:white;display:block;font-size:smaller;">'+percentage+'%</span>';
        });
    };

    upload.options.onSuccess    = async function() {
        uploader.removeFromStorage();
        
        //get wp post details
        let formData = new FormData();
        formData.append('post_id', uploader.storedEntry.postId);
    
        let request = new XMLHttpRequest();
        request.open('POST', `${sim.baseUrl}/wp-json${sim.restApiPrefix}/vimeo/add_uploaded_vimeo`, false);
        request.send(formData);
    
        //mark as uploaded
	    wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);
	    document.querySelector('[data-id="'+uploader.storedEntry.postId+'"] .filename>div').textContent='Uploaded to Vimeo';    

		document.querySelectorAll('.loaderwrapper').forEach(el=>el.remove());
    }

    upload.options.onError      = function(error) {
        console.error("Failed because: " + error);
        wp_uploader.dispatchEvent('fileUploaded', plupload_file, '');
    }

    upload.start();
}

// Exclude videos from max size filter
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