//filter the content
//the filter props are passed on to wp_ajax_query_attachments
// https://developer.wordpress.org/reference/functions/wp_ajax_query_attachments/
window.wp = window.wp || {};
var VisibilityFilter = wp.media.view.AttachmentFilters.extend({
	id: 'visibility-filter',
	createFilters: function() {
		var filters = {};
		var that = this;

		// Add the option to select ALL
		filters.all = {
			text: 'Show all',
			priority: 10
		};
		filters['all']['props'] = {};
		filters['all']['props']['visibility'] = '';
		
		filters['public'] = {
			text: 'Show public',
		};
		filters['public']['props'] = {};
		filters['public']['props']['visibility'] = 'public';
		
		filters['private'] = {
			text: 'Show private',
		};
		filters['private']['props'] = {};
		filters['private']['props']['visibility'] = 'private';

		this.filters = filters;
	}
});

/**
	 * Add our filter dropdown to the menu bar
*/
var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
	createToolbar: function() {
		var that = this;
		i = 1;

		// Make sure to load the original toolbar
		AttachmentsBrowser.prototype.createToolbar.call(this);

		// Get the labels and items for each mcm_taxonomies
		that.toolbar.set( 'VisibilityFilter', new VisibilityFilter({
			controller: that.controller,
			model: that.collection.props,
			priority: -80 + 10*i++,
		}).render() );
	}
});

/* 
		Show vimeo in wp library
 */
wp.media.view.Attachment.Details.prototype.deleteAttachment = function( event ) {
	event.preventDefault();

	this.getFocusableElements();
	var link = document.querySelector('.attachment-details-copy-link').value;
	if(link.includes('https://vimeo.com/')){
		var message	= "This will remove this video from Vimeo, are you sure?";
	}else{
		var message	= wp.media.view.l10n.warnDelete;
	}

	Swal.fire({
		icon: 'warning',
		title: 'confirm delete',
		text: message,
		confirmButtonColor: "#bd2919",
		showCancelButton: true,
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	}).then((result) => {
		if (result.isConfirmed) {
			if(link.includes('https://vimeo.com/')){
				//remove from vimeo
				var vimeo_id	= link.replace('https://vimeo.com/','');				

				var formdata = new FormData();
				formdata.append('action', 'delete_vimeo');
				formdata.append('vimeo_id', vimeo_id);
				sendAJAX(formdata);
			}

			//remove from wp
			this.model.destroy();
			this.moveFocus();
		};
	});
}

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

/* jQuery.extend( wp.Uploader.prototype, {
	init : function() { // plupload 'PostInit'
		this.uploader.bind('BeforeUpload', function(file) {
			console.log('BeforeUpload file=%o', file);
		});
	},
	success : function( file_attachment ) { // plupload 'FileUploaded'
		console.log( file_attachment );
	}
}); */

/* window.wp.Uploader.prototype.init = function() { // plupload 'PostInit'
	this.uploader.bind('FilesAdded', function(up, files) {
		//console.log('BeforeUpload file=%o');
	});

	this.uploader.bind('BeforeUpload', function(up, file) {
		console.log('BeforeUpload file=%o');

		var formdata = new FormData();
		formdata.append('action', 'prepare-vimeo-upload');
		formdata.append('file_size', file.attachment.attributes.size);
		
		var request = new XMLHttpRequest();
		request.open('POST', simnigeria.ajax_url, false);
		request.send(formdata);

		var upload_url	= request.responseText;

		console.log(upload_url);

		//https://github.com/vimeo/vimeo.js#installation
		var upload = new tus.Upload(file.getSource(), {
			uploadUrl: upload_url,
			chunk_size: '20000kb',
			onError: function(error) {
				console.log("Failed because: " + error)
			},
/* 			onProgress: function(bytesUploaded, bytesTotal) {
				var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)

				document.querySelectorAll('.media-progress-bar > div').forEach(div=>div.style.width=percentage+'%')
				console.log(bytesUploaded, bytesTotal, percentage + "%")
			}, 
			onSuccess: function() {
				console.log("Download %s from %s", upload.file.name, upload.url)
			}
		})
		upload.start();

		return false;
	});
}

window.wp.Uploader.prototype.added = function(model){
	//model.attributes.file.destroy();
}; */
 
/* wp.Uploader.queue.bind('add',function(model){
	if(model.attributes.file.type.split("/")[0] == 'video'){
		//upload to vimeo straight away
		//clear queue
		model.attributes.file.destroy();
/* 		wp.Uploader.queue._reset();
		wp.Uploader.queue.remove(model)
		model.clear();
		model.destroy(); 
	}
}); */

//wp.Uploader.bind('FilesAdded',function(){console.log('1234sadsa')});
