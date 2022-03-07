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
                return startUpload(file);
            }
            // cleanup
            urlStorage.removeUpload(storedEntry.urlStorageKey);
        }

        var formdata = new FormData();
        formdata.append('action', 'prepare-vimeo-upload');
        formdata.append('file_size', file.size);
        formdata.append('file_name', file.name);
        formdata.append('file_type', file.type);
        const response = await fetch(sim.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formdata
        });
        const data          = await response.json();
        var uploadUrl		= data.upload_link;
        var postId		    = data.post_id;
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
			document.querySelectorAll('.media-progress-bar > div').forEach(div=>{
				div.style.width	= percentage+'%';
				div.innerHTML	= '<span style="width:100%;text-align:center;color:white;display:block;font-size:smaller;">'+percentage+'%</span>';
			});
		}, 
        async onSuccess () {
            urlStorage.removeUpload(storedEntry.urlStorageKey);
            
            //get wp post details
			var formdata = new FormData();
			formdata.append('action', 'add-uploaded-vimeo');
			formdata.append('post_id', storedEntry.postId);
			
			var request = new XMLHttpRequest();
			request.open('POST', sim.ajax_url, false);
			request.send(formdata);

			//mark as uploaded
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);

			document.querySelector('[data-id="'+storedEntry.postId+'"] .filename>div').textContent='Uploaded to Vimeo';
        },
        onError (error) {
            console.error("Failed because: " + error);
			wp_uploader.dispatchEvent('fileUploaded', plupload_file, request);

			return;
        },
    });

    upload.start();
}