async function confirmPostDelete( event ) {
	event.preventDefault();
	parent = event.target.closest('#frontend_upload_form');

	var confirmed = await Swal.fire({
		title: 'Are you sure?',
		text: "Are you sure you want to remove this "+frontendpost.postType+"?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes, delete it!'
	});

	if (confirmed.isConfirmed) {
		var postId = parent.querySelector('[name="post_id"]').value;
		event.target.closest('form').querySelector('.loadergif').classList.remove('hidden');
	
		var formData = new FormData();
		formData.append('post_id', postId);
		
		var response = await FormSubmit.fetchRestApi('frontend_posting/remove_post', formData);

		if(response){
			Main.displayMessage(response);
		}
	}
};

async function refreshPostLock(){
	var postId = document.querySelector('[name="post_id"]');

	if(postId != null){
		var formData	= new FormData();
		formData.append('postid', postId.value);
		FormSubmit.fetchRestApi('frontend_posting/refresh_post_lock', formData);
	}
}

async function deletePostLock(){
	var postId = document.querySelector('[name="post_id"]');

	if(postId != null){
		var formData	= new FormData();
		formData.append('postid', postId.value);
		var response = await FormSubmit.fetchRestApi('frontend_posting/delete_post_lock', formData);
	}
}

async function changePostType(target){
	var response = await FormSubmit.submitForm(target, 'frontend_posting/change_post_type');

	if(response){
		Main.displayMessage(response);
	}
}

function switchforms(target){
	var parent 			= document.getElementById('frontend_upload_form');

	if(target == null){
		var postType		= location.search.replace('?type=', '');
	}else{
		var postType 		= target.value;
	}
				
	var submitButton 	= parent.querySelector('[name="submit_post"]');
	
	document.querySelector('#postform [name="post_type"]').value 	= postType;
	
	//Change button text
	parent.querySelectorAll('.replaceposttype').forEach(function(el){
		el.textContent = postType;
	});
	
	//Show the lite elements if there is no content
	if(tinymce.activeEditor == null || tinymce.activeEditor.getContent() == ""){
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		//show the button to show all fields again
		parent.querySelector('#showallfields').classList.add('hidden');
	}

	parent.querySelectorAll('#wp-post_content-media-buttons, .advancedpublishoptions, #advancedpublishoptionsbutton').forEach(el=>el.classList.remove('hidden'));
	
	switch(postType) {
		case 'page':
			//Show all page options
			parent.querySelectorAll('.page:not(.page-template-default)').forEach(el=>el.classList.remove('hidden'));

			//Hide other options
			parent.querySelectorAll('.post:not(.page), .recipe, .event, .location, .attachment').forEach(el=>el.classList.add('hidden'));
			
			break;
		case 'post':
			//Show all post options
			parent.querySelectorAll('.post').forEach(el=>el.classList.remove('hidden'));
			
			//Hide other options
			parent.querySelectorAll('.page:not(.post, .page-template-default), .recipe, .event, .location, .attachment').forEach(el=>el.classList.add('hidden'));
			
			break;
		case 'location':
			//Show all post options
			parent.querySelectorAll('.location').forEach(el=>el.classList.remove('hidden'));
			
			//Hide other options
			parent.querySelectorAll('.page, .post, .recipe, .event, .attachment').forEach(el=>el.classList.add('hidden'));
			
			//Hide the lite elements if there is no content
			if(tinymce.activeEditor == null || tinymce.activeEditor.getContent() == ""){
				parent.querySelectorAll('.lite').forEach(function(el){
					el.classList.add('hidden');
				});
				
				//show the button to show all fields again
				parent.querySelector('#showallfields').classList.remove('hidden');
			}
			
			break;
		case 'event':
			//Show all event options
			parent.querySelectorAll('.event').forEach(el=>el.classList.remove('hidden'));
			
			//Hide other options
			parent.querySelectorAll('.post, .page, .recipe, .location, .attachment').forEach(el=>el.classList.add('hidden'));
			
			break;
		case 'recipe':
			//Show all recipe options
			parent.querySelectorAll('.recipe').forEach(el=>el.classList.remove('hidden'));
			
			//Hide other options
			parent.querySelectorAll('.post, .page, .event, .location, .attachment').forEach(el=>el.classList.add('hidden'));
			break;
		case 'attachment':
			//Show all attachment options
			parent.querySelectorAll('.attachment').forEach(el=>el.classList.remove('hidden'));

			//Hide other options
			parent.querySelectorAll('.post, .page, .event, .location, .recipe, #wp-post_content-media-buttons').forEach(el=>el.classList.add('hidden'));

			// tick the box to always include the url
			parent.querySelector('[name="signal_url"]').checked=true;
			break;
		default:
			//Change button text
			submitButton.textContent = submitButton.textContent.replace('page','post');
	}
}

function showallfields(target){
	var parent = target.closest('#frontend_upload_form');
	if(parent.querySelector("#showallfields").classList.contains('show')){
		//Show the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		target.classList.remove('show');
		target.classList.add('hide');
		
		target.textContent = "Only show required fields" 
	}else{
		//Hide the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.add('hidden');
		});
		
		target.classList.add('show');
		target.classList.remove('hide');
		
		target.textContent = "Show all fields" 
	}
}

function addFeaturedImage(event) {
	event.preventDefault();
	
	var parent = event.target.closest('#frontend_upload_form');

	// if the file_frame has already been created, just reuse it
	if ( typeof file_frame == 'object' ) {
		file_frame.open();
		return;
	} 

/* 	var myCustomState = wp.media.controller.Library.extend({
		defaults :  _.defaults({
			id: 'my-custom-state',
			title: 'Upload Image',
			allowLocalEdits: true,
			displaySettings: true,
			filterable: 'all', // This is the property you need. Accepts 'all', 'uploaded', or 'unattached'.
			displayUserSettings: true,
			multiple : false,
		}, wp.media.controller.Library.prototype.defaults )
	});
	
	//Setup media frame
	frame = wp.media({
		button: {
			text: 'Select'
		},
		state: 'my-custom-state', // set the custom state as default state
		states: [
			new myCustomState() // add the state
		]
	}); */

	file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Select featured image' ,
		button: {
			text: 'Save featured image',
		},
		multiple: false
	});

	file_frame.on( 'select', function() {
		//Get the selected image
		attachment = file_frame.state().get('selection').first().toJSON();
		
		//Store the id
		parent.querySelector('[name="post_image_id"]').value = attachment.id;
		
		//Show the image
	
		document.getElementById('featured-image-div').classList.remove('hidden');

		var imgdiv = document.getElementById('featured_image_wrapper');
		
		//If already an image set, remove it
		if(imgdiv.querySelector('img') != null){
			imgdiv.querySelector('img').remove()
		}
		
		imgdiv.insertAdjacentHTML('beforeEnd','<img width="150" height="150" src="'+attachment.url+'">');
		
		//Change button text
		parent.querySelectorAll('[name="add-featured-image"]' ).forEach(function(el){
			el.innerHTML = el.innerHTML.replace("Add",'Replace');
		});
		
		var el = document.querySelector('#mec_fes_thumbnail');
		if(el != null){
			el.value = attachment.url;
		}
	});

	file_frame.open();
}

function catChanged(target){
	var parentId = target.closest('.infobox').dataset.parent;
	
	var parentDiv = target.closest('.categories');
	
	if(target.checked == true){
		//An recipetype is just selected, find all element with its value as parent attribute
		parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
			//Make this subcategory visible
			el.classList.remove('hidden');
			
			//Show the label
			parentDiv.querySelector('#subcategorylabel').classList.remove('hidden');
		});
		
	//If we just deselected a parent category
	}else if(parentId == undefined){
		//Hide the label if there is no category visible anymore
		if(parentDiv.querySelector('.childtypes input[type="checkbox"]:checked') == null){
			parentDiv.querySelector('#subcategorylabel').classList.add('hidden');
		
			//An recipetype is just deselected, find all element with its value as parent attribute
			parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
				//Make this subcategory invisible
				el.classList.add('hidden');
			});
		}
	}
}

async function addCatType(target){
	var response	= await FormSubmit.submitForm(target, 'frontend_posting/add_category');

	if(response){
		//Get the newly added category parent id
		var parentCat  	= target.closest('form').querySelector('[name="cat_parent"]').value;
		var postType	= target.closest('form').querySelector('[name="post_type"]').value;
		var catName		= target.closest('form').querySelector('[name="cat_name"]').value;
		
		//No parent category
		if(parentCat == ''){
			var parentDiv		= document.getElementById(postType+'_parenttypes');
			var parentData		= '';
		//There is a parent
		}else{
			var parentDiv	 	= document.getElementById(postType+'_childtypes');
			var parentData		= 'data-parent="'+parent_cat+'"';
			
			//Select parent if it is not checked already
			var parent = document.querySelector('.'+postType+'type[value="'+parent_cat+'"]');
			if(parent.checked == false){
				parent.click();
			}
		}
		
		//Add the new category as checkbox
		var html = `
		<div class="infobox" ${parentData}>
			<input type="checkbox" class="${postType}type" id="${postType}type[]" value="${response.id}" checked>
			<label class="option-label category-select">${catName}</label>
		</div>
		`
		parentDiv.insertAdjacentHTML('afterBegin', html);
		Main.hideModals();

		Main.displayMessage(`Succesfully added the ${catName} category`);
	}
}

async function submitPost(target){
	var response	= await FormSubmit.submitForm(target, 'frontend_posting/submit_post');
	if(response){
		Main.displayMessage(response);
	}
}

//Retrieve file contents over AJAX
async function readFileContents(attachmentId){
	var formData	= new FormData();
	formData.append('attachment_id', attachmentId);

	var response	= await FormSubmit.fetchRestApi('frontend_posting/get_attachment_contents', formData);

	if(response){
		// Get current content
		var content	= tinyMCE.activeEditor.getContent();
		//Add contents to editor
		tinymce.activeEditor.setContent(content+response);

		Swal.close();
	}
}
		
document.addEventListener("DOMContentLoaded",function() {
	console.log("Frontendposting.js loaded");
	
	//Update minutes
	var minutes = document.getElementById("minutes");
	if(minutes != null){
		setInterval(function() {
			minutes.innerHTML = parseInt(minutes.innerHTML)+1;
		}, 60000);
	}
	
	refreshPostLock();
	//Refresh the post lock every minute
	setInterval(refreshPostLock, 60000);
	
	//Remove post lock when move away from the page
	window.onbeforeunload = function(){
		if(document.getElementById('post_lock_warning') == null){
			deletePostLock();
		}
	};
	
	// run js if we open a specific post type
	if(location.search.includes('?type') || location.search.includes('?post_id')){
		switchforms(document.querySelector('#post_type_selector'));
	}
	
});

document.addEventListener("click", event=>{
	var target = event.target;

	if(target.name == 'submit_post' || (target.parentNode != null && target.parentNode.name == 'submit_post')){
		submitPost(target);
	}
	if(target.name == 'draft_post' || (target.parentNode != null && target.parentNode.name == 'draft_post')){
		//change action value
		target.closest('form').querySelector('[name="post_status"]').value = 'draft';
		
		submitPost(target);
	}

	if(target.name == 'delete_post'){
		confirmPostDelete(event);
	}

	if(target.name == 'change_post_type'){
		changePostType(target);
	}

	if(target.name == "add-featured-image"){
		addFeaturedImage(event);
	}

	if(target.matches('.remove_featured_image')){
		var parent	= document.getElementById('featured-image-div');
		parent.classList.add('hidden');
		parent.querySelector('img').remove();

		document.querySelector('[name="add-featured-image"]').textContent = 'Add featured image';
	}
	
	if(target.id == 'showallfields'){
		showallfields(target);
	}
	
	// SHow add category modal
	if(target.classList.contains('add_cat')){
		document.getElementById('add_'+target.dataset.type+'_type').classList.remove('hidden');
	}
	
	if(target.id == 'advancedpublishoptionsbutton'){
		var div = target.closest('form').querySelector('.advancedpublishoptions');
		if(div.classList.contains('hidden')){
			div.classList.remove('hidden');
			target.querySelector('span').textContent = 'Hide';
		}else{
			div.classList.add('hidden');
			target.querySelector('span').textContent = 'Show';
		}
	}
	
	if(target.name == 'signal'){
		var div = target.closest('#signalmessage').querySelector('.signalmessagetype');
		if(target.checked){
			div.classList.remove('hidden');
		}else{
			div.classList.add('hidden');
		}
	}

	if(target.name == 'enable_event_repeat'){
		document.querySelector('.repeat_wrapper').classList.toggle('hidden');
		var repeated	= target.parentNode.querySelector('[name="event[repeated]"]');
		if(repeated.value == 'yes'){
			repeated.value = '';
		}else{
			repeated.value = 'yes';
		}
	}

	if(target.classList.contains('selectall')){
		target.closest('.selector_wrapper').querySelectorAll('input[type="checkbox"]').forEach(el=>el.checked = target.checked);
	}

	if(target.name == 'event[repeat][repeat_type]'){
		//hide all 
		target.closest('.event').querySelectorAll('.repeat_type_option').forEach(el=>{
			el.querySelector('.repeat_type_option_specifics').classList.add('hidden');	
		});
		//show the selected
		target.closest('.repeat_type_option').querySelector('.repeat_type_option_specifics').classList.remove('hidden');
	}

	if(target.closest('.repeat_type_option') != null){
		document.querySelectorAll('.repeat_type_option_specifics:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		target.closest('.repeat_type_option').querySelector('.repeat_type_option_specifics').classList.remove('hidden');
	}

	if(target.matches('.add_category .form_submit')){
		addCatType(target);
	}

	if(target.matches('button.show-diff')){
		target.parentNode.querySelector('.post-diff-wrapper').classList.toggle('hidden');
	}

	if(target.id == 'insert-media-button'){
		if(typeof(wp.media.frame) == 'undefined'){
			setTimeout(
				function(){
					wp.media.frame.on('insert', function(e) {
						var selection = wp.media.frame.state().get('selection').first().toJSON(); 
						if (['.txt', '.doc', '.rtf'].some(v => selection.url.includes(v))) {
							Swal.fire({
								title: 'Question',
								text: 'Do you want to insert the contents of this file into the post?',
								icon: 'question',
								showCancelButton: true,
								confirmButtonColor: "#bd2919",
								cancelButtonColor: '#d33',
								confirmButtonText: 'Yes please',
								cancelButtonText: 'No thanks',
								hideClass: {
									popup: '',                     // disable popup fade-out animation
								  },
							}).then((result) => {
								Swal.fire({
									title: 'Please wait...',
									html: "<IMG src='"+sim.loadingGif+"' width=100 height=100>",
									showConfirmButton: false,
									showClass: {
										backdrop: 'swal2-noanimation', // disable backdrop animation
										popup: '',                     // disable popup animation
										icon: ''                       // disable icon animation
									  },
									
								});
								if (result.isConfirmed) {
									readFileContents(selection.id);
								}
							});
						}
					});
				},
				100
			);
		}
	}
});

document.addEventListener('change', event=>{
	var target = event.target;

	//listen to change of post type
	if(target.id == 'post_type_selector'){
		switchforms(target);
	}

	if(target.list != null){
		//find the relevant datalist option
		var datalist_op = target.list.querySelector("[value='"+target.value+"' i]");
		if(datalist_op != null){
			var value = datalist_op.dataset.value;

			var valEl	= target.closest('label').querySelector('.datalistvalue');
			if(valEl != null){
				valEl.value	= value;
			}
		}
	}
	
	//listen for recipe type select
	if(target.classList.contains('parent_cat')){
		catChanged(target);
	}

	if(target.name == 'event[allday]'){
		var startTime	= target.closest('.event').querySelector('[name="event[starttime]"]');
		var endTime		= target.closest('.event').querySelector('[name="event[endtime]"]');
		var endDate		= target.closest('.event').querySelector('[name="enddate_label"]');

		if(target.checked){
			startTime.classList.add('hidden');
			endTime.classList.add('hidden');
			endDate.classList.add('hidden');

			startTime.value	='00:00';
			endTime.value	= '23:59';
		}else{
			startTime.classList.remove('hidden');
			endTime.classList.remove('hidden');
			endDate.classList.remove('hidden');

			startTime.value	='';
			endTime.value	= '';
		}
	}

	if(target.name == 'event[startdate]'){
		var endDate		= target.closest('.event').querySelector('[name="event[enddate]"]');
		var start		= new Date(target.value);
		var end			= new Date(endDate.value);

		if(endDate.value == '' || start>end){
			endDate.value	= target.value;
		}
		var firstWeekday	= new Date(start.getFullYear(), start.getMonth(), 1).getDay();
		var offsetDate		= start.getDate() + firstWeekday - 1;
		var montWeek		= Math.floor(offsetDate / 7);

		document.querySelectorAll('.weeks [name="event[repeat][weeks][]"]')[montWeek].checked	= true;

		//add the month day number
		document.querySelector('.repeatdatetype span.monthday').textContent	= start.getUTCDate();
	}

	//daily,weekly,monthly,yearly selector changed
	if(target.name == 'event[repeat][type]'){
		var parent	= target.closest('.repeat_wrapper');

		//hide all what should be hidden
		parent.querySelectorAll('.hide').forEach(el=>el.classList.replace('hide','hidden'));

		switch(target.value){
			case 'daily':
				//show
				parent.querySelectorAll('.repeatinterval, .day_selector, .day_selector h4.checkbox, .day_selector .selectall_wrapper').forEach(el=>el.classList.replace('hidden', 'hide'));
				parent.querySelector('#repeattype').textContent	= 'days';

				//change radio to checkbox
				parent.querySelectorAll('.day_selector input[type="radio"]').forEach(el=>el.type = 'checkbox');
				break;
			case 'weekly':
				parent.querySelectorAll('.repeatinterval, .weeks, .weeks h4.checkbox, .weeks .selectall_wrapper').forEach(el=>el.classList.replace('hidden', 'hide')); 
				parent.querySelector('#repeattype').textContent	= 'week(s)';

				//change radio to checkbox
				parent.querySelectorAll('.weeks input[type="radio"]').forEach(el=>el.type = 'checkbox');
				break;
			case 'monthly':
				parent.querySelectorAll('.repeatdatetype').forEach(el=>el.classList.replace('hidden', 'hide')); 
				parent.querySelector('#repeattype').textContent		= 'month(s)';
				parent.querySelector('.monthoryeartext').textContent	= 'On a certain day and week of a month';

				parent.querySelector('.monthoryear').textContent	= 'a month';
				break;
			case 'yearly':
				parent.querySelectorAll('.repeatdatetype').forEach(el=>el.classList.replace('hidden', 'hide')); 
				parent.querySelector('#repeattype').textContent		= 'year(s)';
				parent.querySelector('.monthoryeartext').textContent	= 'On a certain day, week and month of a year';
				
				var start		= new Date(document.querySelector('[name="event[startdate]"]').value);
				parent.querySelector('.monthoryear').textContent	= start.toLocaleString('en', { month: 'long' })+' every year';
				break;
			case 'custom_days':
				parent.querySelectorAll('.custom_dates_selector').forEach(el=>el.classList.replace('hidden', 'hide')); 
				break;
		}
	}

	//we shoose how to repeat a month or year
	if(target.name == 'event[repeat][datetype]'){
		var parent	= target.closest('.repeat_wrapper');
		
		//hide all what should be hidden
		parent.querySelectorAll('.repeatinterval, .day_selector, .weeks, .months').forEach(el=>el.classList.replace('hide','hidden'));
		
		if(target.value == 'samedate'){
			parent.querySelectorAll('.repeatinterval').forEach(el=>el.classList.replace('hidden', 'hide'));
		}else if(parent.querySelector('[name="event[repeat][type]"]').value == 'monthly'){
			parent.querySelectorAll('.day_selector, .weeks, .months, .months h4.checkbox, .day_selector h4.radio, .weeks h4.radio').forEach(el=>el.classList.replace('hidden', 'hide'));

			parent.querySelectorAll('.day_selector h4.checkbox, .weeks h4.checkbox').forEach(el=>el.classList.replace('hide', 'hidden'));

			//change radio to checkbox
			parent.querySelectorAll('.months input[type="radio"]').forEach(el=>el.type = 'checkbox');

			//change checkbox to radio
			parent.querySelectorAll('.day_selector input[type="checkbox"], .weeks input[type="checkbox"]').forEach(el=>el.type = 'radio');
		}else{
			parent.querySelectorAll('.day_selector, .weeks, .months, .day_selector h4.radio, .weeks h4.radio, .months h4.radio').forEach(el=>el.classList.replace('hidden', 'hide'));

			parent.querySelectorAll('.day_selector h4.checkbox, .weeks h4.checkbox, .months h4.checkbox').forEach(el=>el.classList.replace('hide', 'hidden'));

			//change checkbox to radio
			parent.querySelectorAll('.day_selector input[type="checkbox"], .weeks input[type="checkbox"], .months input[type="checkbox"]').forEach(el=>el.type = 'radio');
		}
	}
});