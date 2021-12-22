console.log("Fileupload.js loaded");

if(window.tinyMCE != undefined){
	simnigeria = tinyMCEPopup.getWindowArg('simnigeria');
}

function file_upload(target){
	file_upload_wrap = target.closest('.file_upload_wrap');
	
	//Create a formdata element
	var form_data = new FormData();
	
	//Add the ajax action name
	form_data.append('action', 'upload_files');
	
	dataset_string = '';
	//Loop over the dataset attributes and add them to post
	target.parentNode.querySelectorAll('input').forEach(
		function(input){
			form_data.append(input.name, input.value);
			dataset_string += 'data-'+input.name+'="'+input.value+'" ';
		}
	);

	// Read selected files
	totalfiles = target.files.length;
	if (totalfiles > 0){
		//Add all the files to the formdata
		for (var index = 0; index < totalfiles; index++) {
			if(target.files[index].size > simnigeria.max_file_size){
				display_message('File to big, max file size is '+(parseInt(simnigeria.max_file_size)/1024/1024)+'MB','error');
				target.value = '';
				return;
			}else{
				form_data.append('files[]', target.files[index]);
			}
		}
	}else{
		alert("Please select some files before hitting the uplad button!");
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
	
	request.open('POST', simnigeria.ajax_url, true);
	
	//Show loading gif
	target.closest('.upload_div').querySelectorAll(".loadergif_wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".uploadmessage").forEach(el =>{
			//Change message text
			if (totalfiles > 1){
				el.textContent = "Uploading documents";
			}else{
				el.textContent = "Uploading document";
			}
		});
	});
	
	//Create a progressbar
	var progressbar = document.createElement('progress');
	progressbar.id = "upload_progress";
	progressbar.value="0";
	progressbar.max="100";
	file_upload_wrap.appendChild(progressbar);
	var progress_percentage = document.createElement('span');
	progress_percentage.id = "progress_percentage";
	progress_percentage.textContent="   0%"
	file_upload_wrap.appendChild(progress_percentage);
	
	//Send AJAX request
	request.send(form_data);
}

function file_upload_progress(e){
	if(e.lengthComputable){
		var max = e.total;
		var current = e.loaded;

		var Percentage = (current * 100)/max;
		Percentage = Math.round(Percentage*10)/10
		
		document.getElementById("upload_progress").value = Percentage;
		document.getElementById("progress_percentage").textContent = "   "+Percentage+"%";

		if(Percentage >= 99){
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
	if(file_upload_wrap.closest('.mce-container') != null){
		//close the modal
		window.tinyMCE.activeEditor.windowManager.close()
	}	
	
	var imgurls			= JSON.parse(result);
	
	for(var index = 0; index < imgurls.length; index++) {
		var src = imgurls[index]['url'];
		
		if(imgurls[index]['id'] != undefined){
			dataset_string += ' data-libraryid="'+imgurls[index]['id']+'"'
		}
		
		var filename = src.split("/")[src.split("/").length-1];
		
		var url = simnigeria.base_url+'/'+src;

		//Add link to tinymce
		if(file_upload_wrap.closest('.mce-container') != null){
			window.tinyMCE.activeEditor.execCommand( 'mceInsertContent', false, '<p><a href="'+url+'">'+filename+'</a></p>' );
		}else{
			var html = "<div class='document'>";
			html += "<input type='hidden' name='"+file_upload_wrap.querySelector('.file_upload').name.replace('_files','')+"' value='"+url+"'>";
			//is image
			if (src.toLowerCase().match(/\.(jpeg|jpg|gif|png)$/) != null){
				//Add the image
				html += '<a class="fileupload" href="'+url+'"><img src="'+url+'" alt="picture" style="width:150px;height:150px;"></a>';
			}else{
				//Add a link
				html += '<a class="fileupload" href="'+url+'">' + filename + '</a>';
			}
			
			//Add an remove button
			html += '<button type="button" class="remove_document button" data-url="' + src + '" ' + dataset_string +'>X</button>';
			html += '<img class="remove_document_loader hidden" src="' + simnigeria.loading_gif + '" style="height:40px;">';
			file_upload_wrap.querySelector('.documentpreview').insertAdjacentHTML('beforeend', html + '</div>');
		}
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

window['afterdocumentremove'] = function(result,responsdata){
	var docwrapper			= document.querySelector('.remove_document[data-url="'+responsdata.url+'"').closest('.document');
	var file_upload_wrap	= docwrapper.closest('.file_upload_wrap');
	
	//hide the loading gif
	docwrapper.querySelector('.remove_document_loader').classList.add('hidden');
		
	// If successful
	if (result.status >= 200 && result.status < 400) {
		try{
			//show the upload field
			file_upload_wrap.querySelector('.upload_div').classList.remove('hidden');
		}catch{
			//remove featured image id
			document.querySelector('[name="post_image_id"]').value = '';
		}
		
		//remove the document/picture
		docwrapper.remove();
		
		re = new RegExp('(.*)[0-9](.*)',"g");
		//reindex documents
		file_upload_wrap.querySelectorAll('.file_url').forEach((el,index)=>{
			el.name = el.name.replace(re, '$1'+index+'$2');
		});
	}
}

function remove_document(target){
	var data = new FormData();
	data.append("action","remove_document");
		
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
	
	sendAJAX(data);
}

//Remove picture on button click
window.addEventListener("click", function(event) {
	var target = event.target;
	
	if (target.className.includes("remove_document")){
		remove_document(target);
	}
});

window.addEventListener("change", event => {
	var target = event.target;

	if (target.className.includes("file_upload")){
		file_upload(target);
	}
});