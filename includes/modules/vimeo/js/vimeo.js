import {getUploadUrl} from './vimeo_upload.js';

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

window.wp.Uploader.prototype.init = function() { // plupload 'PostInit'
	this.uploader.bind('FileFiltered', function(up, files) {
		//show vimeo loader
		showLoader(document.querySelector('.upload-inline-status'), false, '<span class="vimeo" style="font-size:x-large;">Preparing upload to Vimeo</span>');
	});

	this.uploader.bind('BeforeUpload', function(up, file) {
		if(file.type.split("/")[0] == 'video'){
			getUploadUrl(file, up);

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