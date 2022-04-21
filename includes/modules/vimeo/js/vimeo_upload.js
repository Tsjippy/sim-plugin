import * as tus from 'tus-js-client';

// 0. get a reference to the urlStorage
const { urlStorage, fingerprint: getFingerprint } = tus.defaultOptions;
let storedEntry = {};
let upload = null;

// 1. get the upload url first and save it into urlStorage
export async function getUploadUrl (plupload_file, wp_uploader) {
    try {
        var file    = plupload_file.getNative();
        if (!file) return;

        let fingerprint = await getFingerprint(file, { endpoint: sim.ajax_url });
        let storedEntries = await urlStorage.findUploadsByFingerprint(fingerprint);

        if (storedEntries.length) {
            storedEntry = storedEntries[0];
            if (storedEntry.uploadUrl) {
                console.debug('previous URL found: ' + storedEntry.uploadUrl);
                return startUpload(plupload_file, wp_uploader);
            }
            // cleanup
            urlStorage.removeUpload(storedEntry.urlStorageKey);
        }

        var formdata = new FormData();
        formdata.append('file_size', file.size);
        formdata.append('file_name', file.name);
        formdata.append('file_type', file.type);

        var response    = await fetchRestApi('vimeo/prepare_vimeo_upload', formdata);

        //Failed
        if(!response){
            console.error('Failed');
            console.error(formdata);
            console.log(file);
            return;
        }

        var uploadUrl		= response.upload_link;
        var postId		    = response.post_id;
        console.debug('new upload URL: ' + uploadUrl);

        storedEntry = {
            size: file.size,
            metadata: {
                filename: file.name,
                filetype: file.type,
            },
            creationTime: new Date().toString(),
            uploadUrl,
            postId
        };

        storedEntry.urlStorageKey = await urlStorage.addUpload(fingerprint, storedEntry);

        startUpload(plupload_file, wp_uploader);

    } catch (error) {
        console.error('Error:', error);
    }
}

// 2. then upload, and remove the upload reference from urlStorage when done
function startUpload (plupload_file, wp_uploader) {
    upload = new tus.Upload(plupload_file.getNative(), {
        uploadUrl: storedEntry.uploadUrl,
        headers: {
            // https://developer.vimeo.com/api/upload/videos#resumable-approach-step-2
            Accept: 'application/vnd.vimeo.*+json;version=3.4' // required
        },
        chunkSize: 50000000, // required
        onProgress: function(bytesUploaded, bytesTotal) {
			//calculate percentage
			var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)

			//show percentage in progressbar
			document.querySelectorAll('.attachments-wrapper .uploading:first-child .media-progress-bar > div, .selection-view .uploading:first-child .media-progress-bar > div, .media-uploader-status.uploading .media-progress-bar > div').forEach(div=>{
				div.style.width	= percentage+'%';
				div.innerHTML	= '<span style="width:100%;text-align:center;color:white;display:block;font-size:smaller;">'+percentage+'%</span>';
			});
		}, 
        async onSuccess () {
            urlStorage.removeUpload(storedEntry.urlStorageKey);
            
            //get wp post details
			var formdata = new FormData();
			formdata.append('post_id', storedEntry.postId);

            var request = new XMLHttpRequest();
			request.open('POST', sim.base_url+'/wp-json/sim/v1/vimeo/add_uploaded_vimeo', false);
			request.send(formdata);

            //var response    = await fetchRestApi('vimeo/add_uploaded_vimeo', formdata);

			//mark as uploaded
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);
            //wp_uploader.dispatchEvent('fileUploaded', plupload_file, response);

			document.querySelector('[data-id="'+storedEntry.postId+'"] .filename>div').textContent='Uploaded to Vimeo';
        },
        onError (error) {
            console.error("Failed because: " + error);
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, response);

			return;
        },
    });

    //update upload count
    document.querySelector('.upload-details .upload-index').textContent    = wp_uploader.total.uploaded+1;
    
    document.querySelector('.upload-details .upload-filename').textContent = wp_uploader.files[wp_uploader.total.uploaded].name;

    upload.start();
}