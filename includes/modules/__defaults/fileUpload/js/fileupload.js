console.log("Fileupload.js loaded");

let totalFiles			= 0;
let fileUploadWrap		= '';
let datasetString		= '';

function createProgressBar(target){
	//Show loading gif
	target.closest('.upload_div').querySelectorAll(".loadergif_wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".uploadmessage").forEach(el =>{
			el.textContent = "Preparing upload";
			el.classList.add('upload-message');
		});
	});
	
	var html	= ` 
	<div id="progress-wrapper" class="hidden">
		<progress id="upload_progress" value="0" max="100"></progress>
		<span id="progress_percentage">   0%</span>
	</div>
	`;

	fileUploadWrap.insertAdjacentHTML('beforeEnd', html);
}

function addPreview(link, value){
	var name		= fileUploadWrap.querySelector('.file_upload').name.replace('_files','');

	var html = `
	<div class='document'>
		<input type='hidden' name='${name}' value='${value}'>
		${link}
		<img class="remove_document_loader hidden" src="${sim.loadingGif}" style="height:40px;">
	</div>`;

	fileUploadWrap.querySelector('.documentpreview').insertAdjacentHTML('beforeend', html);

	return fileUploadWrap.querySelector('.documentpreview .document');
}

async function fileUpload(target){
	var s			= "";
	fileUploadWrap	= target.closest('.file_upload_wrap');
	totalFiles 		= target.files.length;

	if (totalFiles < 0){
		Main.displayMessage("Please select some files before hitting the uplad button!", 'error');
		return;
	}
	
	//Create a formData element
	var formData = new FormData();
	
	//Add the ajax action name
	formData.append('action', 'upload_files');
	
	//Loop over the dataset attributes and add them to post
	target.parentNode.querySelectorAll('input').forEach( input =>{
			formData.append(input.name, input.value);
			datasetString += `data-${input.name}="${input.value}`;
		}
	);

	//Add all the files to the formData
	for (var index = 0; index < totalFiles; index++) {
		// file is a video and vimeo enabled
		if(target.files[index].type.split('/')[0] == 'video' && typeof(vimeoUploader) == 'object'){
			createProgressBar(target);

			//update post id on a postform
			await uploadVideo(target.files[index]);
		// file to big
		}else if(target.files[index].size > sim.maxFileSize){
			Main.displayMessage('File too big, max file size is '+(parseInt(sim.maxFileSize)/1024/1024)+'MB','error');
			target.value = '';
			return;
		}else{
			formData.append('files[]', target.files[index]);
		}
	}

	// No files remaining to upload
	if(formData.get('files[]') == null){
		return;
	}
	
	//AJAX request
	var request = new XMLHttpRequest();
	
	//Listen to the state changes
	request.onreadystatechange = function(){
		//If finished
		if(request.readyState == 4){
			//Success
			if (request.status >= 200 && request.status < 400) {
				fileUploadSucces(request.responseText)
			//Error
			}else{
				Main.displayMessage(JSON.parse(request.responseText).error,'error');
			}
			
			//Hide loading gif
			document.querySelectorAll(".loadergif_wrapper").forEach(
				function(loader){
					loader.classList.add('hidden');
				}
			);
				
			//Clear the input
			fileUploadWrap.querySelector('.file_upload').value = "";
		}
	};
	
	//Listen to the upload status
	request.upload.addEventListener('progress', fileUploadProgress, false);
	
	request.open('POST', sim.ajaxUrl, true);
	
	//Create a progressbar
	createProgressBar(target);
	
	//Send AJAX request
	if (totalFiles > 1){
		s = "s";
	}
	target.closest('.upload_div').querySelector('.uploadmessage').textContent = "Uploading document"+s;
	document.getElementById('progress-wrapper').classList.remove('hidden');

	request.send(formData);
}

function fileUploadProgress(e){
	if(e.lengthComputable){
		var max = e.total;
		var current = e.loaded;

		var percentage = (current * 100)/max;
		percentage = Math.round(percentage*10)/10
		
		document.getElementById("upload_progress").value 			= percentage;
		document.getElementById("progress_percentage").textContent	= `   ${percentage}%`;

		if(percentage >= 99){
			//Remove progress barr
			document.getElementById("progress-wrapper").remove();
			
			// process completed
			fileUploadWrap.querySelectorAll(".uploadmessage").forEach(el =>{
				//Change message text
				if (totalFiles > 1){
					el.textContent = "Processing documents";
				}else{
					el.textContent = "Processing document";
				}
			});
		}
	}  
}

function fileUploadSucces(result){
	var imgUrls		= JSON.parse(result);
	var src			= '';
	
	for(const element of imgUrls) {
		src 			= element['url'];
		var url 		= sim.baseUrl+'/'+src;
		var value		= '';
		var anchorLink	= '';
		
		if(element['id'] != undefined){
			datasetString += ' data-libraryid="'+element['id']+'"'
		}

		if(element['id'] == undefined){
			value		= url;
		}else{
			value		= element['id'];
		}

		//is image
		if (src.toLowerCase().match(/\.(jpe|jpeg|jpg|gif|png)$/) != null){
			//Add the image
			anchorLink	= `<a class="fileupload" href="${url}"><img src="${url}" alt="picture" style="width:150px;height:150px;"></a>`;
		}else{
			//Add a link
			anchorLink	= `<a class="fileupload" href="${url}">${filename}</a>`;
		}
		anchorLink += `<button type="button" class="remove_document button" data-url="${src}" ${datasetString}>X</button>`;

		addPreview(anchorLink, value);
	}
	
	if(imgUrls.length==1){
		var fileName 	= src.split("/")[src.split("/").length-1];
		Main.displayMessage(`The file ${fileName} has been uploaded succesfully.`,'success',true);
	}else{
		Main.displayMessage("The files have been uploaded succesfully.",'success',true);
	}
	
	//Hide upload button if only one file allowed
	if(!fileUploadWrap.querySelector('.file_upload').multiple){
		fileUploadWrap.querySelector('.upload_div').classList.add('hidden');
	}
}

async function removeDocument(target){
	var data = new FormData();		
	//Loop over the dataset attributes and add them to post
	for(var d in target.dataset){
		if(target.dataset[d] != ''){
			data.append(d,target.dataset[d]);
		}
	}
	
	//hide the remove button
	target.style.display = "none";
	
	//add an id to the remove button so we can query for it later
	target.parentNode.classList.add('removethisdocument');
	
	//show loader
	target.parentNode.querySelector('.remove_document_loader').classList.remove('hidden');
	
	var response	= await FormSubmit.fetchRestApi('remove_document', data);

	if(response){
		var docWrapper		= target.closest('.document');
		fileUploadWrap		= docWrapper.closest('.file_upload_wrap');
		
		//hide the loading gif
		docWrapper.querySelector('.remove_document_loader').classList.add('hidden');
			
		// If successful
		try{
			//show the upload field
			fileUploadWrap.querySelector('.upload_div').classList.remove('hidden');
		}catch{
			//remove featured image id
			document.querySelector('[name="post_image_id"]').value = '';
		}
		
		//remove the document/picture
		docWrapper.remove();
		
		//var re = new RegExp('(.*)[0-9](.*)',"g");
		//reindex documents
		fileUploadWrap.querySelectorAll('.file_url').forEach((el,index)=>{
			//el.name = el.name.replace(re, '$1'+index+'$2');
			el.name = el.name.replace(/(\d)/g, index);
		});

		Main.displayMessage(response);
	}
}

function refreshVimeoiFrame(){
	// Show the vimeo video after 60 seconds when the video is hopefully rendered
	document.querySelectorAll('.vimeo-embed-container.loading iframe').forEach(el=>{
		// replace the src with itself to refresh the iframe
		el.src	= el.src;
	});
}

async function uploadVideo(file){
	var uploader	= new vimeoUploader.VimeoUpload(file);
	var upload		= await uploader.tusUploader();
	var s			= '';

	// Could not upload
	if(!upload){
		//clear file upload
		fileUploadWrap.querySelector('.file_upload').value = "";
				
		//Remove progress barr
		document.getElementById("progress-wrapper").remove();

		// Hide the loader
		document.querySelector('.loadergif_wrapper:not(.hidden)').classList.add('hidden');

		return false;
	}

	// Upload started
	if (totalFiles > 1){
		s = "s";
	}else{
		s = "";
	}

	upload.options.onProgress   = function(bytesUploaded, bytesTotal) {
		//calculate percentage
		var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)
	
		//show percentage in progressbar
		document.getElementById("upload_progress").value			= percentage;
		document.getElementById("progress_percentage").textContent	= `   ${percentage}%`;

		if(percentage>98){
			document.querySelector('.upload-message').textContent = "Processing video"+s;
			document.getElementById('progress-wrapper').classList.add('hidden');
		}
	};

	upload.options.onSuccess    = async function() {
		var postId	= uploader.storedEntry.postId;
		// Add post id of the video to the form
		var formData = new FormData();
        formData.append('post_id', postId);
    
        var request = new XMLHttpRequest();
        request.open('POST', sim.baseUrl+'/wp-json/sim/v1/vimeo/add_uploaded_vimeo', false);
        request.send(formData);

		//Remove progress barr
		document.getElementById("progress-wrapper").remove();
		
		let link	= `
		<div class="vimeo-wrapper">
			<div class='vimeo-embed-container loading'>
				<iframe src='https://player.vimeo.com/video/${uploader.storedEntry.vimeoId}' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe><br>
			</div>
		</div>`;

		var preview		= addPreview(link, postId);

		// Hide the loader
		document.querySelector('.loadergif_wrapper:not(.hidden)').classList.add('hidden');

		Main.displayMessage(`The file ${file.name} has been uploaded succesfully.`,'success',true);
		
		//Hide upload button if only one file allowed
		if(!fileUploadWrap.querySelector('.file_upload').multiple){
			fileUploadWrap.querySelector('.upload_div').classList.add('hidden');
		}

		//clear file upload
		fileUploadWrap.querySelector('.file_upload').value = "";
		
		uploader.urlStorage.removeUpload(uploader.storedEntry.urlStorageKey);

		// check if we are uploading from frontend posting form
		var postForm = document.getElementById('postform');
		if(postForm != null){
			postForm.querySelector('[name="update"]').value		= 'true';
			postForm.querySelector('[name="post_id"]').value	= postId;
		}

		// Wait for the video to be processed on Vimeo
		var result	= '';
		while(!result.ok){
			await new Promise(res => setTimeout(res, 10000));
			result = await fetch(
				`https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com/${uploader.storedEntry.vimeoId}`,
				{method: 'GET'}
			);
		}
		var response	= await result.json();

		preview.querySelector('.vimeo-wrapper').innerHTML = DOMPurify.sanitize(response.html);
	}

	upload.options.onError      = function(error) {
		console.error("Failed because: " + error);				
	}

	document.querySelector('.upload-message').textContent = "Uploading video"+s;
	document.getElementById('progress-wrapper').classList.remove('hidden');

	upload.start();
}

//Remove picture on button click
window.addEventListener("click", function(event) {
	var target = event.target;
	
	if (target.className.includes("remove_document")){
		event.preventDefault();
		removeDocument(target);
	}
});

window.addEventListener("change", event => {
	var target = event.target;

	if (target.className.includes("file_upload")){
		event.preventDefault();
		fileUpload(target);
	}
});