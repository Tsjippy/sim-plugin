console.log("Fileupload.js loaded");

let totalfiles			= 0;
let file_upload_wrap	= '';
dataset_string			= '';

function createProgressBar(target, filetype){
	//Show loading gif
	target.closest('.upload_div').querySelectorAll(".loadergif_wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".uploadmessage").forEach(el =>{
			//Change message text
			if (totalfiles > 1){
				el.textContent = `Uploading ${filetype}s`;
			}else{
				el.textContent = `Uploading ${filetype}`;
			}
		});
	});
	
	var progressbar		= document.createElement('progress');
	progressbar.id		= "upload_progress";
	progressbar.value	= "0";
	progressbar.max		= "100";
	file_upload_wrap.appendChild(progressbar);

	var percentage 			= document.createElement('span');
	percentage.id			= "progress_percentage";
	percentage.textContent	= "   0%"
	file_upload_wrap.appendChild(percentage);
}

function addPreview(link, value){
	var name		= file_upload_wrap.querySelector('.file_upload').name.replace('_files','');

	var html = `
	<div class='document'>
		<input type='hidden' name='${name}' value='${value}'>
		${link}
		<img class="remove_document_loader hidden" src="${sim.loading_gif}" style="height:40px;">
	</div>`;

	file_upload_wrap.querySelector('.documentpreview').insertAdjacentHTML('beforeend', html);
}

async function file_upload(target){
	file_upload_wrap = target.closest('.file_upload_wrap');
	
	//Create a formdata element
	var form_data = new FormData();
	
	//Add the ajax action name
	form_data.append('action', 'upload_files');
	
	//Loop over the dataset attributes and add them to post
	target.parentNode.querySelectorAll('input').forEach(
		function(input){
			form_data.append(input.name, input.value);
			dataset_string += `data-${input.name}="${input.value}`;
		}
	);

	// Read selected files
	totalfiles = target.files.length;
	if (totalfiles > 0){
		//Add all the files to the formdata
		for (var index = 0; index < totalfiles; index++) {
			// file is a video and vimeo enabled
			if(target.files[index].type.split('/')[0] == 'video' && typeof(vimeoUploader) == 'object'){
				createProgressBar(target, 'video');
				await uploadVideo(target.files[index]);
			// file to big
			}else if(target.files[index].size > sim.max_file_size){
				display_message('File too big, max file size is '+(parseInt(sim.max_file_size)/1024/1024)+'MB','error');
				target.value = '';
				return;
			}else{
				form_data.append('files[]', target.files[index]);
			}
		}
	}else{
		display_message("Please select some files before hitting the uplad button!", 'error');
		return;
	}

	// No files remaining to upload
	if(form_data.get('files[]') == null){
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
				file_upload_upload_succes(request.responseText)
			//Error
			}else{
				display_message(JSON.parse(request.responseText).error,'error');
			}
			
			//Hide loading gif
			document.querySelectorAll(".loadergif_wrapper").forEach(
				function(loader){
					loader.classList.add('hidden');
				}
			);
				
			//Clear the input
			file_upload_wrap.querySelector('.file_upload').value = "";
		}
	};
	
	//Listen to the upload status
	request.upload.addEventListener('progress', file_upload_progress, false);
	
	request.open('POST', sim.ajax_url, true);
	
	//Create a progressbar
	createProgressBar(target, 'file');
	
	//Send AJAX request
	request.send(form_data);
}

function file_upload_progress(e){
	if(e.lengthComputable){
		var max = e.total;
		var current = e.loaded;

		var percentage = (current * 100)/max;
		percentage = Math.round(percentage*10)/10
		
		document.getElementById("upload_progress").value 			= percentage;
		document.getElementById("progress_percentage").textContent	= `   ${percentage}%`;

		if(percentage >= 99){
			//Remove progress barr
			document.getElementById("upload_progress").remove();
			document.getElementById("progress_percentage").remove();
			
			// process completed
			file_upload_wrap.querySelectorAll(".uploadmessage").forEach(el =>{
				//Change message text
				if (totalfiles > 1){
					el.textContent = "Processing documents";
				}else{
					el.textContent = "Processing document";
				}
			});
		}
	}  
}

function file_upload_upload_succes(result){
	var imgurls			= JSON.parse(result);
	
	for(var index = 0; index < imgurls.length; index++) {
		var src = imgurls[index]['url'];
		
		if(imgurls[index]['id'] != undefined){
			dataset_string += ' data-libraryid="'+imgurls[index]['id']+'"'
		}
		
		var filename 	= src.split("/")[src.split("/").length-1];
		
		var url 		= sim.base_url+'/'+src;

		if(imgurls[index]['id'] == undefined){
			var value		= url;
		}else{
			var value		= imgurls[index]['id'];
		}

		//is image
		if (src.toLowerCase().match(/\.(jpe|jpeg|jpg|gif|png)$/) != null){
			//Add the image
			var link	= `<a class="fileupload" href="${url}"><img src="${url}" alt="picture" style="width:150px;height:150px;"></a>`;
		}else{
			//Add a link
			var link	= `<a class="fileupload" href="${url}">${filename}</a>`;
		}
		link += `<button type="button" class="remove_document button" data-url="${src}" ${dataset_string}>X</button>`;

		addPreview(link, value);
	}
	
	if(imgurls.length==1){
		display_message("The file " + filename + " has been uploaded succesfully.",'success',true);
	}else{
		display_message("The files have been uploaded succesfully.",'success',true);
	}
	
	//Hide upload button if only one file allowed
	if(file_upload_wrap.querySelector('.file_upload').multiple == false){
		file_upload_wrap.querySelector('.upload_div').classList.add('hidden');
	}
}

async function remove_document(target){
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
	
	var response	= await fetchRestApi('remove_document', data);

	if(response){
		var docwrapper			= target.closest('.document');
		var file_upload_wrap	= docwrapper.closest('.file_upload_wrap');
		
		//hide the loading gif
		docwrapper.querySelector('.remove_document_loader').classList.add('hidden');
			
		// If successful
		try{
			//show the upload field
			file_upload_wrap.querySelector('.upload_div').classList.remove('hidden');
		}catch{
			//remove featured image id
			document.querySelector('[name="post_image_id"]').value = '';
		}
		
		//remove the document/picture
		docwrapper.remove();
		
		var re = new RegExp('(.*)[0-9](.*)',"g");
		//reindex documents
		file_upload_wrap.querySelectorAll('.file_url').forEach((el,index)=>{
			el.name = el.name.replace(re, '$1'+index+'$2');
		});

		display_message(response);
	}
}

async function uploadVideo(file){
	var uploader	= new vimeoUploader.VimeoUpload(file);
	var upload		= await uploader.tusUploader();

	upload.options.onProgress   = function(bytesUploaded, bytesTotal) {
		//calculate percentage
		var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)
	
		//show percentage in progressbar
		document.getElementById("upload_progress").value			= percentage;
		document.getElementById("progress_percentage").textContent	= `   ${percentage}%`;
	};

	upload.options.onSuccess    = async function() {
		uploader.urlStorage.removeUpload(uploader.storedEntry.urlStorageKey);

		//Remove progress barr
		document.getElementById("upload_progress").remove();
		document.getElementById("progress_percentage").remove();

		var link	= `
		<div class="vimeo-wrapper">
			<div class='vimeo-embed-container'>
				<iframe src='https://player.vimeo.com/video/${uploader.storedEntry.vimeoId}' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
			</div>
		</div>`;

		addPreview(link, uploader.storedEntry.postId, uploader.storedEntry.postId);

		display_message(`The file ${file.name} has been uploaded succesfully.`,'success',true);
		
		//Hide upload button if only one file allowed
		if(file_upload_wrap.querySelector('.file_upload').multiple == false){
			file_upload_wrap.querySelector('.upload_div').classList.add('hidden');
		}
	}

	upload.options.onError      = function(error) {
		console.error("Failed because: " + error);				
		return;
	}

	upload.start();
}

//Remove picture on button click
window.addEventListener("click", function(event) {
	var target = event.target;
	
	if (target.className.includes("remove_document")){
		event.preventDefault();
		remove_document(target);
	}
});

window.addEventListener("change", event => {
	var target = event.target;

	if (target.className.includes("file_upload")){
		event.preventDefault();
		file_upload(target);
	}
});